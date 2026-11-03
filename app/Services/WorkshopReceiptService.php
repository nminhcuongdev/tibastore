<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductExpectedReceipt;
use App\Models\WorkshopOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkshopReceiptService
{
    public function syncForecast(WorkshopOrder $workshop): void
    {
        $quantities = collect($workshop->size_quantities)->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
        ProductExpectedReceipt::where('workshop_order_id', $workshop->id)->whereNull('received_at')
            ->whereNotIn('product_id', $quantities->keys())->delete();
        foreach ($quantities as $id => $quantity) {
            $receipt = ProductExpectedReceipt::firstOrNew(['workshop_order_id' => $workshop->id, 'product_id' => $id]);
            if (!$receipt->received_at) {
                $receipt->fill(['expected_receive_date' => $workshop->returned_date ?? $workshop->purchase_plan['expected_date'],
                    'expected_receive_quantity' => $quantity])->save();
            }
        }
    }

    public function updateCustomerOrder(WorkshopOrder $workshop): void
    {
        DB::transaction(function () use ($workshop) {
            $order = Order::whereKey($workshop->customer_order_id)->lockForUpdate()->firstOrFail();
            $workshop = WorkshopOrder::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            if (!$workshop->purchase_plan || !$workshop->size_quantities) {
                throw ValidationException::withMessages(['size_quantities' => 'Hãy chỉnh và lưu size, số lượng thực nhận trước.']);
            }
            $order->load('items.product');
            $items = $order->items->filter(fn ($item) => $item->product?->code === $workshop->product_code
                && $item->fulfillment_source === $workshop->supply_source);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['order' => 'Không còn mã và nguồn hàng tương ứng trong đơn khách.']);
            }
            $inventory = app(OrderInventoryService::class);
            $inventory->resetAdjustment($order);
            $template = $items->first();
            $previous = collect($workshop->purchase_plan['lines'])->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
            $next = collect($workshop->size_quantities)->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
            foreach ($previous->keys()->merge($next->keys())->unique() as $id) {
                $delta = ($next[$id] ?? 0) - ($previous[$id] ?? 0);
                $matching = $items->where('product_id', $id);
                if ($delta > 0) {
                    $target = $matching->first();
                    if ($target) $target->update(['quantity' => $target->quantity + $delta]);
                    else $order->items()->create(['product_id' => $id, 'quantity' => $delta, 'size_pending' => false,
                        'rental_price' => $template->rental_price, 'rental_days' => $template->rental_days,
                        'fulfillment_source' => $workshop->supply_source]);
                } elseif ($delta < 0) {
                    $remaining = -$delta;
                    foreach ($matching->reverse() as $target) {
                        $used = min($remaining, $target->quantity);
                        $target->quantity -= $used;
                        $remaining -= $used;
                        if ($target->quantity > 0) $target->save(); else $target->delete();
                        if (!$remaining) break;
                    }
                }
            }
            $plan = $workshop->purchase_plan;
            $plan['lines'] = $workshop->size_quantities;
            $plan['expected_date'] = $workshop->returned_date?->toDateString() ?? $plan['expected_date'];
            $order->load('items.product');
            foreach ($order->items as $item) {
                if ($item->product?->code === $workshop->product_code && $item->fulfillment_source === $workshop->supply_source) {
                    $item->update(['purchase_plan' => $plan + ['applied_lines' => $plan['lines'], 'updated_once' => true]]);
                }
            }
            $workshop->update(['purchase_plan' => $plan]);
            $total = $order->items->sum(fn ($item) => (int) round($item->rental_price * $item->quantity * (float) $item->rental_days));
            $order->total_amount = $total;
            if ($order->discount_percent !== null) $order->discount_amount = (int) round($total * $order->discount_percent / 100);
            $mirror = $order->items->firstWhere('product_id', $order->product_id) ?? $order->items->first();
            $order->product_id = $mirror->product_id;
            $order->quantity = $mirror->quantity;
            $order->save();
            app(OrderPurchasePlanService::class)->sync($order);
            $inventory->applyStatusAdjustment($order->fresh());
        });
    }
}
