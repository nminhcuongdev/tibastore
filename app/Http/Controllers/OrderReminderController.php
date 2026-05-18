<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReminderController extends Controller
{
    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:pickup,return'],
        ]);

        DB::transaction(function () use ($data, $order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($data['type'] === 'pickup' && $lockedOrder->status === 'len_don') {
                $this->decreaseStock((int) $lockedOrder->product_id, (int) $lockedOrder->quantity);

                $lockedOrder->update([
                    'status' => 'da_gui',
                    'pickup_reminder_dismissed' => true,
                ]);
            }

            if ($data['type'] === 'return' && $lockedOrder->status === 'da_gui') {
                $this->increaseStock((int) $lockedOrder->product_id, (int) $lockedOrder->quantity);

                $lockedOrder->update([
                    'status' => 'thanh_cong',
                    'return_reminder_dismissed' => true,
                ]);
            }
        });

        return back()->with('status', 'Đã cập nhật trạng thái đơn hàng.');
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

    private function decreaseStock(int $productId, int $quantity): void
    {
        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

        if ($product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng đơn hàng vượt quá tồn kho hiện tại.',
            ]);
        }

        $product->decrement('stock_quantity', $quantity);
    }

    private function increaseStock(int $productId, int $quantity): void
    {
        Product::whereKey($productId)->lockForUpdate()->firstOrFail()
            ->increment('stock_quantity', $quantity);
    }
}
