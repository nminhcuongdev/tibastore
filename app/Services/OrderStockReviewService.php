<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class OrderStockReviewService
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function shortages(): Collection
    {
        $result = collect();
        // Include overdue saved orders too; only completed/cancelled orders leave this review.
        $orders = Order::whereNotIn('status', ['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->with('items.product')->orderByRaw('COALESCE(dispatch_date, pickup_date)')->orderBy('id');

        foreach ($orders->lazy(100) as $order) {
            $items = $order->items->reject(fn ($item) => $item->size_pending)->groupBy('product_id');
            $start = $order->dispatch_date ?? $order->pickup_date;
            $end = $order->actual_return_date ?? $order->return_date;
            if (!$start || !$end || $items->isEmpty()) {
                continue;
            }

            // Forecast purchases do not count as physically received stock.
            $available = $this->inventory->projectedAvailableQuantities(
                $items->keys()->all(), $start->toDateString(), $end->toDateString(),
                $order->id, true, $order->return_period, false
            );
            $lines = $items->map(function ($group, $productId) use ($available) {
                $product = $group->first()->product;
                $quantity = (int) $group->sum('quantity');
                $capacity = (int) ($available[$productId] ?? 0);
                if (!$product || $quantity <= $capacity) {
                    return null;
                }

                return ['product' => $product, 'quantity' => $quantity,
                    'available' => $capacity, 'shortage' => $quantity - $capacity];
            })->filter()->values();

            if ($lines->isNotEmpty()) {
                $result->push(['order' => $order, 'lines' => $lines]);
            }
        }

        return $result;
    }
}
