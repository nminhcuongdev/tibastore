<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OrderTurnaroundService
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function returns(array $productIds, string $pickupDate, ?int $excludeOrderId = null): Collection
    {
        return OrderItem::query()
            ->with(['order', 'product'])
            ->whereIn('product_id', $productIds)
            ->where('size_pending', false)
            ->whereHas('order', function ($query) use ($pickupDate, $excludeOrderId) {
                $query->whereDate('return_date', $pickupDate)
                    ->whereDate('pickup_date', '<', $pickupDate)
                    ->whereNotIn('status', ['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
                    ->when($excludeOrderId !== null, fn ($q) => $q->where('id', '!=', $excludeOrderId));
            })->get();
    }

    public function reminders(?Carbon $today = null): Collection
    {
        $today = ($today ?? now('Asia/Ho_Chi_Minh'))->copy()->startOfDay();
        $orders = Order::with('items.product')
            ->whereDate('pickup_date', '>=', $today->toDateString())
            ->whereDate('pickup_date', '<=', $today->copy()->addDay()->toDateString())
            ->whereNotIn('status', ['huy_don', 'da_gui', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->orderBy('pickup_date')->orderBy('id')->get();
        $reminders = collect();

        foreach ($orders as $order) {
            $items = $order->items->reject(fn ($item) => $item->size_pending);
            $ids = $items->pluck('product_id')->unique()->all();
            $returns = $this->returns($ids, $order->pickup_date->toDateString(), $order->id)->groupBy('product_id');
            if ($returns->isEmpty()) {
                continue;
            }
            $strict = $this->inventory->projectedAvailableQuantities(
                $ids, $order->pickup_date->toDateString(), $order->return_date->toDateString(), $order->id, false
            );
            foreach ($items->groupBy('product_id') as $productId => $lines) {
                $shortage = max(0, $lines->sum('quantity') - ($strict[$productId] ?? 0));
                if ($shortage === 0 || ! $returns->has($productId)) {
                    continue;
                }
                $reminders->push([
                    'order' => $order,
                    'product' => $lines->first()->product,
                    'shortage' => $shortage,
                    'returns' => $returns[$productId],
                ]);
            }
        }

        return $reminders;
    }
}
