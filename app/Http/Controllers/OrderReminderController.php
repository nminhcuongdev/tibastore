<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderReminderController extends Controller
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:pickup,return'],
        ]);

        $this->inventory->syncOrder($order);

        return back()->with('status', 'Đã đồng bộ kho theo ngày của đơn hàng.');
    }

    public function dismiss(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:pickup,return'],
        ]);

        if ($data['type'] === 'pickup') {
            $order->update(['pickup_reminder_dismissed' => true]);
        }

        if ($data['type'] === 'return') {
            $order->update(['return_reminder_dismissed' => true]);
        }

        return back()->with('status', 'Đã tắt nhắc lại cho đơn hàng này.');
    }

    public function preferences(Request $request, \App\Services\OrderTurnaroundService $service): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:once,repeat'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);
        $reminders = $service->reminders(null, true)
            ->filter(fn ($reminder) => in_array($reminder['order']->id, array_map('intval', $data['order_ids']), true));
        $service->acknowledge($reminders, $request->user()->id, $data['mode']);

        return back()->with('status', $data['mode'] === 'once'
            ? 'Đã chọn chỉ nhắc một lần cho các đơn này.'
            : 'Đã hẹn nhắc lại sau 5 tiếng nếu đồ chưa về.');
    }
}
