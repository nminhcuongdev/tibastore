<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\WorkshopOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderInventoryService
{
    /**
     * Kept for existing callers. Expected receipts are forecasts only: dates and
     * order statuses never receive stock. Stock enters through an explicit transfer
     * or a manual product stock adjustment.
     */
    public function syncDueOrders(?Carbon $today = null): void
    {
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
    /**
     * Đưa kho về đúng trạng thái của đơn.
     *
     * Mỗi dòng hàng lưu sẵn stock_held = số đang thực bị trừ khỏi kho, nên ở đây
     * chỉ cần tính số ĐÁNG LẼ phải giữ rồi cộng/trừ đúng phần chênh. Nhờ vậy chạy
     * lại bao nhiêu lần cũng không lệch, và không phải suy ngược xem trước đó đã
     * trừ bao nhiêu.
     */
    public function applyStatusAdjustment(Order $order): void
    {
        $order->loadMissing('items');

        $isOut = $order->requiresStockOut();

        if ($isOut) {
            // Rời trạng thái "đã kiểm" để gửi lại: bỏ số nhận lại cũ, tránh dùng nhầm.
            $this->clearInspectionData($order);
        }

        foreach ($order->items as $item) {
            if (!$isOut && !$order->allowsCodeDispatch() && $item->dispatched_at) {
                $item->update(['dispatched_at' => null]);
            }
            $this->setHeldQuantity($item, $this->targetHeldQuantity($item, $isOut || $item->dispatched_at !== null));
        }

        if ($isOut) {
            $order->forceFill([
                'stock_decreased_at' => $order->stock_decreased_at ?? now(),
                'stock_returned_at' => null,
            ])->save();

            return;
        }

        if ($order->stock_decreased_at !== null && $order->stock_returned_at === null) {
            $order->forceFill(['stock_returned_at' => now()])->save();
        }
    }

    public function resetAdjustment(Order $order): void
    {
        $order->loadMissing('items');

        // Trả lại đúng số đang giữ của từng dòng, không suy ngược từ số lượng đơn.
        foreach ($order->items as $item) {
            $this->setHeldQuantity($item, 0);
        }

        $order->forceFill([
            'stock_decreased_at' => null,
            'stock_returned_at' => null,
        ])->save();
    }

    /**
     * Số lượng của một dòng đáng lẽ phải đang bị trừ khỏi kho:
     * - Đang cho thuê (đơn ở trạng thái giữ kho): cả dòng.
     * - Hàng đã về, chưa kiểm: 0 — nằm trong kho, cho thuê tiếp được ngay.
     * - Đã kiểm: phần thiếu (mất/hỏng), vì phần đó không bao giờ về lại kho.
     */
    private function targetHeldQuantity(OrderItem $item, bool $isOut): int
    {
        // Dòng "chưa chốt size" không gắn với size cụ thể nên không giữ kho.
        if ($item->size_pending) {
            return 0;
        }

        if ($isOut) {
            return (int) $item->quantity;
        }

        if ($item->returned_quantity === null) {
            return 0;
        }

        return max(0, (int) $item->quantity - (int) $item->returned_quantity);
    }

    private function setHeldQuantity(OrderItem $item, int $target): void
    {
        $current = (int) $item->stock_held;
        $delta = $target - $current;

        if ($delta === 0) {
            return;
        }

        if ($delta > 0) {
            $this->decreaseStock((int) $item->product_id, $delta);
        } else {
            $this->increaseStock((int) $item->product_id, -$delta);
        }

        $item->forceFill(['stock_held' => $target])->save();
    }

    public function assertItemsAvailable(
        array $items,
        string $pickupDate,
        string $returnDate,
        ?int $excludeOrderId = null,
        ?string $returnPeriod = null
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
            $excludeOrderId,
            true,
            $returnPeriod
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

    /**
     * Cho phép trả buổi sáng và lấy tiếp cùng ngày khi lên đơn. Chế độ strict
     * vẫn tính cả ngày trả để phát hiện hàng cần đòi về sớm. Đơn lấy/trả trong
     * một ngày vẫn giữ hàng cả ngày; lúc gửi luôn kiểm tra tồn thực tế.
     */
    public function projectedAvailableQuantities(
        array $productIds,
        string $pickupDate,
        string $returnDate,
        ?int $excludeOrderId = null,
        bool $allowSameDayTurnaround = true,
        ?string $returnPeriod = null
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
        if ($allowSameDayTurnaround && $end->gt($start)) {
            $endExclusive = $end->copy();
        }
        if ($returnPeriod !== null) {
            $endExclusive = $end->copy()->addDays($returnPeriod === 'toi' ? 2 : 1);
        }
        $products = Product::whereIn('id', $productIds->all())
            ->get(['id', 'stock_quantity'])
            ->keyBy('id');
        $expectedReceiptQuantities = $this->incomingByProduct($productIds->all(), $start->toDateString())
            ->map(fn ($rows) => $rows->sum('quantity'))->all();
        $openQuantities = $this->openStockQuantities($productIds->all());
        $reservedQuantities = $this->maxReservedQuantities(
            $productIds->all(),
            $start,
            $endExclusive,
            $excludeOrderId,
            $allowSameDayTurnaround
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
            ->whereNotIn('orders.status', ['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->where(fn ($query) => $query->whereDate('orders.pickup_date', '<=', $to->toDateString())
                ->orWhereDate('order_items.dispatched_at', '<=', $to->toDateString()))
            ->whereDate('orders.return_date', '>=', $from->copy()->subDay()->toDateString())
            ->get([
                'order_items.product_id',
                'order_items.quantity',
                'order_items.dispatched_at',
                'orders.pickup_date',
                'orders.return_date',
                'orders.return_period',
            ]);

        foreach ($bookings as $booking) {
            $productId = (int) $booking->product_id;
            $quantity = (int) $booking->quantity;
            $start = $this->date($booking->pickup_date);
            if ($booking->dispatched_at) $start = $start->min($this->date($booking->dispatched_at));
            $end = $this->date($booking->return_date);
            if ($booking->return_period === 'toi') {
                $end->addDay();
            }

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
        $receiptsByProduct = $this->incomingByProduct($productIds, $to->toDateString());

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

    public function incomingByProduct(array $productIds, string $through): \Illuminate\Support\Collection
    {
        $receipts = ProductExpectedReceipt::query()
            ->whereIn('product_id', $productIds)
            ->where('expected_receive_quantity', '>', 0)
            ->where(fn ($query) => $query->whereNull('received_at')->orWhereNotNull('workshop_order_id'))
            ->with('workshopOrder')->get();
        $rows = $receipts->whereNull('received_at')
            ->filter(fn ($receipt) => !$receipt->workshop_order_id || ($receipt->workshopOrder && !$receipt->workshopOrder->stocked_at))
            ->map(fn ($receipt) => [
            'product_id' => (int) $receipt->product_id,
            'date' => ($receipt->workshopOrder?->returned_date ?? $receipt->expected_receive_date)->toDateString(),
            'quantity' => (int) $receipt->expected_receive_quantity,
            'workshop_id' => $receipt->workshop_order_id,
        ]);
        $registered = $receipts->filter(fn ($receipt) => $receipt->workshop_order_id)
            ->keyBy(fn ($receipt) => $receipt->workshop_order_id.':'.$receipt->product_id);
        $codes = Product::whereIn('id', $productIds)->pluck('code', 'id');
        $orders = WorkshopOrder::whereIn('product_code', $codes->unique()->values())
            ->whereIn('status', [WorkshopOrder::STATUS_ORDERED, WorkshopOrder::STATUS_ARRIVED])
            ->whereNull('stocked_at')->whereDate('returned_date', '<=', $through)->get();
        foreach ($orders as $order) {
            $lines = collect($order->size_quantities ?? $order->purchase_plan['lines'] ?? [])->groupBy('product_id');
            foreach ($lines as $id => $sizes) {
                if ($codes->get($id) !== $order->product_code || $registered->has($order->id.':'.$id)) continue;
                $rows->push(['product_id' => (int) $id, 'date' => $order->returned_date->toDateString(),
                    'quantity' => (int) $sizes->sum('quantity'), 'workshop_id' => $order->id]);
            }
        }
        return $rows->filter(fn ($row) => $row['date'] <= $through)->groupBy('product_id')->map->values();
    }

    public function bookingShortages(string $code): \Illuminate\Support\Collection
    {
        $products = Product::where('code', $code)->pluck('size', 'id');
        $orders = Order::whereDate('pickup_date', '>=', now('Asia/Ho_Chi_Minh')->toDateString())
            ->whereNotIn('status', ['huy_don', 'da_gui', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->withWhereHas('items', fn ($query) => $query->whereIn('product_id', $products->keys())->where('size_pending', false))
            ->orderBy('pickup_date')->orderBy('id')->get();

        return $orders->flatMap(fn ($order) => $this->shortagesForOrder($order))->values();
    }

    public function shortagesForOrder(Order $order): \Illuminate\Support\Collection
    {
        if (!$order->exists || in_array($order->status, ['huy_don', 'da_gui', Order::RETURNED_STATUS, Order::CHECKED_STATUS], true)
            || $order->pickup_date->toDateString() < now('Asia/Ho_Chi_Minh')->toDateString()) return collect();

        $order->loadMissing('items.product');
        $items = $order->items->reject('size_pending')->groupBy('product_id');
        $pickup = $order->pickup_date->toDateString();
        $available = $this->projectedAvailableQuantities($items->keys()->all(), $pickup,
            $order->return_date->toDateString(), $order->id, true, $order->return_period);

        return $items->map(function ($lines, $id) use ($order, $available, $pickup) {
            $shortage = $lines->sum('quantity') - ($available[$id] ?? 0);
            if ($shortage <= 0) return null;
            $product = $lines->first()->product;
            return ['order_name' => $order->order_name, 'pickup' => $pickup, 'size' => $product?->size,
                'code' => $product?->code, 'shortage' => $shortage, 'url' => route('orders.edit', $order)];
        })->filter()->values();
    }

    /**
     * Hàng đang cho thuê: đã trừ khỏi kho nhưng sẽ quay về.
     *
     * Cộng lại phần này để ra tổng hàng shop thực sự sở hữu. Chỉ tính đơn đang ở
     * trạng thái giữ kho — phần bị trừ do kiểm thiếu cũng nằm ở stock_held nhưng
     * là hàng đã mất, không bao giờ về nên không được tính là đang sở hữu.
     */
    private function openStockQuantities(array $productIds): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where(fn ($query) => $query->whereIn('orders.status', Order::STOCK_OUT_STATUSES)->orWhereNotNull('order_items.dispatched_at'))
            ->selectRaw('order_items.product_id, SUM(order_items.stock_held) as open_quantity')
            ->groupBy('order_items.product_id')
            ->pluck('open_quantity', 'order_items.product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * Hàng đã về kho nhưng chưa kiểm: đã cộng vào tồn, cho thuê tiếp được ngay,
     * nhưng chưa ai xác nhận còn đủ và còn lành. Dùng để hiển thị cảnh báo mềm.
     *
     * @return array<int,int> product_id => số lượng
     */
    public function pendingInspectionQuantities(array $productIds = []): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($productIds !== [], fn ($query) => $query->whereIn('order_items.product_id', $productIds))
            ->where('orders.status', Order::RETURNED_STATUS)
            ->where('order_items.size_pending', false)
            ->whereNull('order_items.returned_quantity')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as pending_quantity')
            ->groupBy('order_items.product_id')
            ->pluck('pending_quantity', 'order_items.product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * Như trên nhưng gộp theo mã hàng, cho màn kho vốn hiển thị theo mã.
     *
     * @return array<string,int> code => số lượng
     */
    public function pendingInspectionQuantitiesByCode(): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', Order::RETURNED_STATUS)
            ->where('order_items.size_pending', false)
            ->whereNull('order_items.returned_quantity')
            ->selectRaw('products.code, SUM(order_items.quantity) as pending_quantity')
            ->groupBy('products.code')
            ->pluck('pending_quantity', 'products.code')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    /**
     * Hàng đang cho thuê theo mã: đã trừ khỏi kho và sẽ quay về.
     *
     * Lấy theo stock_held nên phản ánh đúng số đang thực bị trừ, không phải
     * số lượng ghi trên đơn.
     *
     * @return array<string,int> code => số lượng
     */
    public function rentedOutQuantitiesByProduct(array $productIds): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->where(fn ($query) => $query->whereIn('orders.status', Order::STOCK_OUT_STATUSES)->orWhereNotNull('order_items.dispatched_at'))
            ->selectRaw('order_items.product_id, SUM(order_items.stock_held) as rented_quantity')
            ->groupBy('order_items.product_id')->pluck('rented_quantity', 'order_items.product_id')
            ->map(fn ($quantity) => (int) $quantity)->all();
    }

    public function rentedOutQuantitiesByCode(): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where(fn ($query) => $query->whereIn('orders.status', Order::STOCK_OUT_STATUSES)->orWhereNotNull('order_items.dispatched_at'))
            ->selectRaw('products.code, SUM(order_items.stock_held) as rented_quantity')
            ->groupBy('products.code')
            ->pluck('rented_quantity', 'products.code')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }

    private function maxReservedQuantities(
        array $productIds,
        Carbon $start,
        Carbon $endExclusive,
        ?int $excludeOrderId = null,
        bool $allowSameDayTurnaround = true
    ): array {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->whereIn('order_items.product_id', $productIds)
            ->where('order_items.size_pending', false)
            ->when($excludeOrderId !== null, fn ($query) => $query->where('orders.id', '!=', $excludeOrderId))
            ->where(fn ($query) => $query->whereDate('orders.pickup_date', '<', $endExclusive)
                ->orWhereDate('order_items.dispatched_at', '<', $endExclusive))
            ->whereDate('orders.return_date', '>=', $start->copy()->subDay())
            ->get([
                'order_items.product_id',
                'order_items.quantity',
                'order_items.dispatched_at',
                'orders.pickup_date',
                'orders.return_date',
                'orders.return_period',
            ]);

        $initialReserved = array_fill_keys($productIds, 0);
        $events = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $quantity = (int) $row->quantity;
            $bookingStart = $this->date($row->pickup_date);
            if ($row->dispatched_at) $bookingStart = $bookingStart->min($this->date($row->dispatched_at));
            $bookingEndExclusive = $this->date($row->return_date)->addDay();
            if ($allowSameDayTurnaround && $this->date($row->return_date)->gt($bookingStart)) {
                $bookingEndExclusive = $this->date($row->return_date);
            }
            if ($row->return_period !== null) {
                $bookingEndExclusive = $this->date($row->return_date)->addDays($row->return_period === 'toi' ? 2 : 1);
            }

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
            // Ghi ro ma - size - con bao nhieu, de biet duong xu ly thay vi doan.
            throw ValidationException::withMessages([
                'items' => "Mã {$product->code} - size {$product->size}: cần {$quantity} nhưng tồn kho hiện chỉ còn {$product->stock_quantity}.",
            ]);
        }

        $product->decrement('stock_quantity', $quantity);
    }

    private function increaseStock(int $productId, int $quantity): void
    {
        Product::whereKey($productId)->lockForUpdate()->firstOrFail()
            ->increment('stock_quantity', $quantity);
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
