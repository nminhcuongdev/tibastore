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
            $this->decreaseStocks($this->itemsForStock($order));

            $order->forceFill([
                'stock_decreased_at' => now(),
                'stock_returned_at' => null,
            ])->save();

            return;
        }

        if (! $shouldBeOut && $currentlyOut) {
            $this->increaseStocks($this->itemsForStock($order));

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
        $productIds = collect($items)
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
        $messages = [];

        foreach ($items as $index => $item) {
            $productId = (int) $item['product_id'];
            $availableQuantity = (int) ($availability[$productId] ?? 0);

            if ((int) $item['quantity'] > $availableQuantity) {
                $messages["items.{$index}.quantity"] = "Số lượng không được vượt quá tồn dự kiến trong khoảng ngày đã chọn ({$availableQuantity}).";
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
        $expectedReceiptQuantities = $this->expectedReceiptQuantitiesForDate($productIds->all(), $start);
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

    private function expectedReceiptQuantitiesForDate(array $productIds, Carbon $date): array
    {
        return ProductExpectedReceipt::query()
            ->whereIn('product_id', $productIds)
            ->whereDate('expected_receive_date', '<=', $date)
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

    private function date($value): Carbon
    {
        return $value instanceof Carbon
            ? $value->copy()->startOfDay()
            : Carbon::parse($value)->startOfDay();
    }
}
