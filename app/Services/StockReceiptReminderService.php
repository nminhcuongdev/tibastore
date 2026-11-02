<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductExpectedReceipt;
use App\Models\WorkshopOrder;
use Illuminate\Support\Carbon;

class StockReceiptReminderService
{
    public function reminders(?Carbon $today = null): array
    {
        $date = ($today ?? now('Asia/Ho_Chi_Minh'))->toDateString();
        $workshop = WorkshopOrder::with('product')->whereNull('stocked_at')
            ->where(function ($query) use ($date) {
                $query->whereDate('returned_date', '<=', $date)
                    ->orWhere('status', WorkshopOrder::STATUS_ARRIVED);
            })
            ->where(function ($query) {
                $query->whereNull('customer_order_id')->orWhereNotIn('customer_order_id',
                    Order::where('status', 'huy_don')->select('id'));
            })
            // Legacy receipts already received must never prompt a second stock entry.
            ->where(function ($query) {
                $query->whereNotIn('id', ProductExpectedReceipt::whereNotNull('workshop_order_id')
                    ->whereNotNull('received_at')->select('workshop_order_id'))
                    ->orWhereIn('id', ProductExpectedReceipt::whereNotNull('workshop_order_id')
                        ->whereNull('received_at')->where('expected_receive_quantity', '>', 0)->select('workshop_order_id'));
            })
            ->orderByRaw('returned_date IS NULL')->orderBy('returned_date')->orderBy('id')->get();
        $manual = ProductExpectedReceipt::with('product')->whereNull('workshop_order_id')
            ->whereNull('received_at')->where('expected_receive_quantity', '>', 0)
            ->whereDate('expected_receive_date', '<=', $date)
            ->whereHas('product', fn ($query) => $query->whereNull('discontinued_at'))
            ->orderBy('expected_receive_date')->orderBy('id')->get();
        return ['workshop' => $workshop, 'manual' => $manual, 'today' => $date];
    }
}
