<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WorkshopOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkshopArrivalReminderService
{
    public function reminders(?Carbon $today = null): Collection
    {
        $today = ($today ?? now('Asia/Ho_Chi_Minh'))->copy()->startOfDay();
        $normalize = fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');
        $pending = WorkshopOrder::with('product')
            ->whereIn('status', [WorkshopOrder::STATUS_NOT_YET, WorkshopOrder::STATUS_ORDERED])
            ->whereIn('supply_source', array_keys(WorkshopOrder::supplySources()))
            ->whereNotNull('order_name')->get()
            ->filter(fn ($item) => $normalize($item->order_name) !== '');
        if ($pending->isEmpty()) {
            return collect();
        }

        $orders = Order::with(['items.product', 'product'])
            ->whereIn(DB::raw('LOWER(TRIM(order_name))'), $pending->map(fn ($item) => $normalize($item->order_name))->unique()->all())
            ->whereDate('pickup_date', '<=', $today->copy()->addDays(2)->toDateString())
            ->whereNotIn('status', ['huy_don', 'da_gui', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->orderBy('pickup_date')->orderBy('id')->get();
        $byName = $pending->groupBy(fn ($item) => $normalize($item->order_name));
        $reminders = collect();
        foreach ($orders as $order) {
            $codes = $order->items->pluck('product.code');
            if ($order->product) {
                $codes->push($order->product->code);
            }
            foreach ($byName->get($normalize($order->order_name), collect()) as $item) {
                if ($item->product_code !== WorkshopOrder::NO_PRODUCT_CODE
                    && ! $codes->contains(fn ($code) => $normalize($code) === $normalize($item->product_code))) {
                    continue;
                }
                $reminders->push(['order' => $order, 'item' => $item,
                    'overdue' => $order->pickup_date->toDateString() < $today->toDateString()]);
            }
        }

        return $reminders;
    }
}
