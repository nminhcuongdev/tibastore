<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\StockImportHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    /**
     * Đồng bộ các việc theo ngày KHÔNG liên quan trạng thái đơn:
     * hiện chỉ còn nhập kho theo phiếu nhập dự kiến đến hạn.
     * Trạng thái đơn không còn tự đổi theo ngày — người dùng tự cập nhật.
     */
    public function syncDueOrders(?Carbon $today = null): void
    {
        $today = $this->date($today ?? now());

        $this->syncDueExpectedReceipts($today);
    }

    public function syncOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            $this->applyStatusAdjustment($lockedOrder);
        });
    }

    /**
     * Đồng bộ kho theo trạng thái hiện tại của đơn:
     * - Trạng thái "đã gửi"/"đã trả về": hàng phải nằm ngoài kho (trừ kho).
     * - Các trạng thái còn lại (kể cả "đã kiểm"): hàng nằm trong kho (cộng lại nếu đang nợ).
     * Idempotent nhờ hai mốc stock_decreased_at / stock_returned_at.
     */
    public function applyStatusAdjustment(Order $order): void
    {
        $order->loadMissing('items');

        $shouldBeOut = $order->requiresStockOut();
        $currentlyOut = $order->stock_decreased_at !== null && $order->stock_returned_at === null;

        if ($shouldBeOut && ! $currentlyOut) {
            // Chỉ trừ đúng số hàng đang thực nằm trong kho của đơn.
            // Nếu đơn từng được kiểm (hoàn lại một phần) thì chỉ còn lại phần đã nhận.
            $this->decreaseStocks($this->inStockItems($order));
            $this->clearInspectionData($order);

            $order->forceFill([
                'stock_decreased_at' => now(),
                'stock_returned_at' => null,
            ])->save();

            return;
        }

        if (! $shouldBeOut && $currentlyOut) {
            // Trạng thái "đã kiểm": chỉ hoàn số lượng thực nhận lại (đã set trước khi gọi).
            // Các trạng thái khác: hoàn đủ toàn bộ số lượng đơn.
            $this->increaseStocks($this->inStockItems($order));

            $order->forceFill([
                'stock_returned_at' => now(),
            ])->save();
        }
    }

    public function resetAdjustment(Order $order): void
    {
        $order->loadMissing('items');

        if ($order->stock_decreased_at !== null && $order->stock_returned_at === null) {
            $this->increaseStocks($this->itemsForStock($order));
        }

        $order->forceFill([
            'stock_decreased_at' => null,
            'stock_returned_at' => null,
        ])->save();
    }

    public function assertItemsAvailable(
        array $items,
        string $pickupDate,
        string $returnDate,
        ?int $excludeOrderId = null
    ): void {
        // Item "chưa chốt size" không giữ kho theo size cụ thể nên bỏ qua khi kiểm tồn.
        $checkableItems = collect($items)->reject(fn ($item) => ! empty($item['size_pending']));

        $productIds = $checkableItems
            ->pluck('product_id')
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values()
            ->all();

        $availability = $this->projectedAvailableQuantities(
            $productIds,
            $pickupDate,
            $returnDate,
            $excludeOrderId
        );

        // Cộng dồn số lượng theo từng sản phẩm — nhiều dòng cùng mã-size (giá khác nhau)
        // vẫn phải kiểm theo TỔNG số lượng so với tồn dự kiến.
        $totalByProduct = [];
        $firstIndexByProduct = [];

        foreach ($items as $index => $item) {
            if (! empty($item['size_pending'])) {
                continue;
            }

            $productId = (int) $item['product_id'];
            $totalByProduct[$productId] = ($totalByProduct[$productId] ?? 0) + (int) $item['quantity'];

            if (! isset($firstIndexByProduct[$productId])) {
                $firstIndexByProduct[$productId] = $index;
            }
        }

        // Lấy mã + size để ghi thông báo cụ thể.
        $products = Product::whereIn('id', array_keys($totalByProduct))
            ->get(['id', 'code', 'size'])
            ->keyBy('id');

        $fromLabel = $this->date($pickupDate)->format('d/m/Y');
        $toLabel = $this->date($returnDate)->format('d/m/Y');

        $messages = [];

        foreach ($totalByProduct as $productId => $totalQuantity) {
            $availableQuantity = (int) ($availability[$productId] ?? 0);

            if ($totalQuantity > $availableQuantity) {
                $index = $firstIndexByProduct[$productId];
                $product = $products->get($productId);
                $code = $product?->code ?? 'N/A';
                $size = $product?->size ?? 'N/A';

                $messages["items.{$index}.quantity"] = "Mã {$code} - size {$size}: đặt {$totalQuantity} nhưng tồn dự kiến chỉ {$availableQuantity} trong khoảng ngày lấy {$fromLabel} → trả {$toLabel}.";
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function projectedAvailableQuantities(
        array $productIds,
        string $pickupDate,
        string $returnDate,
        ?int $excludeOrderId = null
    ): array {
        $productIds = collect($productIds)
            ->filter()
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        $start = $this->date($pickupDate);
        $end = $this->date($returnDate);

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $endExclusive = $end->copy()->addDay();
        $products = Product::whereIn('id', $productIds->all())
            ->get(['id', 'stock_quantity'])
            ->keyBy('id');
        $expectedReceiptQuantities = $this->pendingExpectedReceiptQuantities($productIds->all());
        $openQuantities = $this->openStockQuantities($productIds->all());
        $reservedQuantities = $this->maxReservedQuantities(
            $productIds->all(),
            $start,
            $endExclusive,
            $excludeOrderId
        );
        $availability = [];

        foreach ($productIds as $productId) {
            $product = $products->get($productId);
            $totalPhysicalQuantity = (int) ($product?->stock_quantity ?? 0)
                + (int) ($openQuantities[$productId] ?? 0)
                + (int) ($expectedReceiptQuantities[$productId] ?? 0);
            $reservedQuantity = (int) ($reservedQuantities[$productId] ?? 0);

            $availability[$productId] = max(0, $totalPhysicalQuantity - $reservedQuantity);
        }

        return $availability;
    }

    /**
     * Tồn khả dụng theo TỪNG NGÀY cho nhiều sản phẩm, trong khoảng [from, to].
     * Khả dụng ngày D = (tồn hiện tại + hàng đang ở ngoài kho) + hàng nhập dự kiến về <= D
     *                   - số lượng đơn đang thuê phủ ngày D (pickup <= D <= return).
     * Trả về: [productId => [ 'Y-m-d' => số_khả_dụng ]]  (có thể âm nếu đặt vượt).
     */
    public function dailyAvailability(array $productIds, Carbon $from, Carbon $to): array
    {
        $productIds = collect($productIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($productIds === []) {
            return [];
        }

        $from = $this->date($from);
        $to = $this->date($to);

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        $stock = Product::whereIn('id', $productIds)
            ->pluck('stock_quantity', 'id')
            ->map(fn ($q) => (int) $q)
            ->all();
        $open = $this->openStockQuantities($productIds);

        // Số lượng đơn phủ từng ngày, theo từng sản phẩm.
        $reserved = [];
        $bookings = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->whereDate('orders.pickup_date', '<=', $to->toDateString())
            ->whereDate('orders.return_date', '>=', $from->toDateString())
            ->get([
                'order_items.product_id',
                'order_items.quantity',
                'orders.pickup_date',
                'orders.return_date',
            ]);

        foreach ($bookings as $booking) {
            $productId = (int) $booking->product_id;
            $quantity = (int) $booking->quantity;
            $start = $this->date($booking->pickup_date);
            $end = $this->date($booking->return_date);

            if ($start->lt($from)) {
                $start = $from->copy();
            }

            if ($end->gt($to)) {
                $end = $to->copy();
            }

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateKey = $d->toDateString();
                $reserved[$productId][$dateKey] = ($reserved[$productId][$dateKey] ?? 0) + $quantity;
            }
        }

        // Hàng nhập dự kiến (chưa nhận) theo từng sản phẩm.
        $receiptsByProduct = [];
        ProductExpectedReceipt::query()
            ->whereIn('product_id', $productIds)
            ->whereNull('received_at')
            ->where('expected_receive_quantity', '>', 0)
            ->get(['product_id', 'expected_receive_date', 'expected_receive_quantity'])
            ->each(function ($receipt) use (&$receiptsByProduct) {
                $receiptsByProduct[(int) $receipt->product_id][] = [
                    'date' => $this->date($receipt->expected_receive_date)->toDateString(),
                    'quantity' => (int) $receipt->expected_receive_quantity,
                ];
            });

        $result = [];

        foreach ($productIds as $productId) {
            $owned = (int) ($stock[$productId] ?? 0) + (int) ($open[$productId] ?? 0);
            $result[$productId] = [];

            foreach ($dates as $dateKey) {
                $received = 0;

                foreach (($receiptsByProduct[$productId] ?? []) as $receipt) {
                    if ($receipt['date'] <= $dateKey) {
                        $received += $receipt['quantity'];
                    }
                }

                $result[$productId][$dateKey] = $owned + $received - (int) ($reserved[$productId][$dateKey] ?? 0);
            }
        }

        return $result;
    }

    private function syncDueExpectedReceipts(Carbon $today): void
    {
        ProductExpectedReceipt::query()
            ->whereDate('expected_receive_date', '<=', $today)
            ->where('expected_receive_quantity', '>', 0)
            ->whereNull('received_at')
            ->chunkById(50, function ($receipts) use ($today) {
                foreach ($receipts as $receipt) {
                    DB::transaction(function () use ($receipt, $today) {
                        $lockedReceipt = ProductExpectedReceipt::whereKey($receipt->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (
                            $lockedReceipt->received_at !== null
                            || $lockedReceipt->expected_receive_quantity <= 0
                            || $this->date($lockedReceipt->expected_receive_date)->gt($today)
                        ) {
                            return;
                        }

                        $lockedProduct = Product::whereKey($lockedReceipt->product_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $previousQuantity = (int) $lockedProduct->stock_quantity;
                        $receivedQuantity = (int) $lockedReceipt->expected_receive_quantity;
                        $newQuantity = $previousQuantity + $receivedQuantity;

                        $lockedProduct->forceFill([
                            'stock_quantity' => $newQuantity,
                        ])->save();

                        $lockedReceipt->forceFill(['received_at' => now()])->save();

                        StockImportHistory::create([
                            'product_id' => $lockedProduct->id,
                            'user_id' => null,
                            'quantity' => $receivedQuantity,
                            'previous_quantity' => $previousQuantity,
                            'new_quantity' => $newQuantity,
                        ]);
                    });
                }
            });
    }

    /**
     * Tổng số lượng hàng nhập dự kiến (chưa nhận) theo từng sản phẩm.
     * Tính toàn bộ phiếu nhập dự kiến, không phụ thuộc ngày — cho phép lên đơn
     * trong khoảng hàng nhập dự kiến sẽ về.
     */
    private function pendingExpectedReceiptQuantities(array $productIds): array
    {
        return ProductExpectedReceipt::query()
            ->whereIn('product_id', $productIds)
            ->where('expected_receive_quantity', '>', 0)
            ->whereNull('received_at')
            ->selectRaw('product_id, SUM(expected_receive_quantity) as expected_quantity')
            ->groupBy('product_id')
            ->pluck('expected_quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    private function openStockQuantities(array $productIds): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->whereNotNull('orders.stock_decreased_at')
            ->whereNull('orders.stock_returned_at')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as open_quantity')
            ->groupBy('order_items.product_id')
            ->pluck('open_quantity', 'order_items.product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    private function maxReservedQuantities(
        array $productIds,
        Carbon $start,
        Carbon $endExclusive,
        ?int $excludeOrderId = null
    ): array {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->when($excludeOrderId !== null, fn ($query) => $query->where('orders.id', '!=', $excludeOrderId))
            ->whereDate('orders.pickup_date', '<', $endExclusive)
            ->whereDate('orders.return_date', '>=', $start)
            ->get([
                'order_items.product_id',
                'order_items.quantity',
                'orders.pickup_date',
                'orders.return_date',
            ]);

        $initialReserved = array_fill_keys($productIds, 0);
        $events = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $quantity = (int) $row->quantity;
            $bookingStart = $this->date($row->pickup_date);
            $bookingEndExclusive = $this->date($row->return_date)->addDay();

            if ($bookingStart->lt($start) && $bookingEndExclusive->gt($start)) {
                $initialReserved[$productId] += $quantity;
            }

            if ($bookingStart->gte($start) && $bookingStart->lt($endExclusive)) {
                $events[$productId][$bookingStart->toDateString()][] = $quantity;
            }

            if ($bookingEndExclusive->gt($start) && $bookingEndExclusive->lt($endExclusive)) {
                $events[$productId][$bookingEndExclusive->toDateString()][] = -1 * $quantity;
            }
        }

        $reservedQuantities = [];

        foreach ($productIds as $productId) {
            $runningReserved = (int) ($initialReserved[$productId] ?? 0);
            $maxReserved = $runningReserved;
            $productEvents = $events[$productId] ?? [];

            ksort($productEvents);

            foreach ($productEvents as $deltas) {
                $runningReserved += array_sum($deltas);
                $maxReserved = max($maxReserved, $runningReserved);
            }

            $reservedQuantities[$productId] = max(0, $maxReserved);
        }

        return $reservedQuantities;
    }

    private function decreaseStock(int $productId, int $quantity): void
    {
        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

        if ($product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Số lượng đơn hàng vượt quá tồn kho hiện tại.',
            ]);
        }

        $product->decrement('stock_quantity', $quantity);
    }

    private function increaseStock(int $productId, int $quantity): void
    {
        Product::whereKey($productId)->lockForUpdate()->firstOrFail()
            ->increment('stock_quantity', $quantity);
    }

    private function decreaseStocks(array $items): void
    {
        foreach ($this->stockTotals($items) as $productId => $quantity) {
            $this->decreaseStock((int) $productId, (int) $quantity);
        }
    }

    private function increaseStocks(array $items): void
    {
        foreach ($this->stockTotals($items) as $productId => $quantity) {
            $this->increaseStock((int) $productId, (int) $quantity);
        }
    }

    private function stockTotals(array $items): array
    {
        return collect($items)
            ->groupBy('product_id')
            ->map(fn (Collection $productItems) => $productItems->sum('quantity'))
            ->all();
    }

    private function itemsForStock(Order $order): array
    {
        if ($order->items->isNotEmpty()) {
            return $order->items
                ->reject(fn (OrderItem $item) => $item->size_pending)
                ->map(fn (OrderItem $item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])
                ->values()
                ->all();
        }

        return [[
            'product_id' => $order->product_id,
            'quantity' => $order->quantity,
        ]];
    }

    /**
     * Số lượng của đơn đang thực nằm trong kho theo từng sản phẩm:
     * - Nếu đơn đã được kiểm (có số lượng nhận lại): dùng số đã nhận lại.
     * - Ngược lại: dùng đủ số lượng đơn.
     */
    private function inStockItems(Order $order): array
    {
        $order->loadMissing('items');

        $hasReturned = $order->items->isNotEmpty()
            && $order->items->contains(fn (OrderItem $item) => $item->returned_quantity !== null);

        if ($hasReturned) {
            return $order->items
                ->reject(fn (OrderItem $item) => $item->size_pending)
                ->map(fn (OrderItem $item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (int) ($item->returned_quantity ?? 0),
                ])
                ->values()
                ->all();
        }

        return $this->itemsForStock($order);
    }

    /**
     * Xóa dữ liệu kiểm đơn (số nhận lại + ghi chú) khi đơn rời trạng thái đã kiểm
     * để quay lại ngoài kho, tránh dùng nhầm số cũ cho lần kiểm sau.
     */
    private function clearInspectionData(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->returned_quantity !== null) {
                $item->forceFill(['returned_quantity' => null])->save();
            }
        }

        if ($order->check_note !== null) {
            $order->forceFill(['check_note' => null])->save();
        }
    }

    private function date($value): Carbon
    {
        return $value instanceof Carbon
            ? $value->copy()->startOfDay()
            : Carbon::parse($value)->startOfDay();
    }
}
