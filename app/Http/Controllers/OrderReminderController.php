<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
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
            $lockedOrder = Order::whereKey($order->id)->with('items')->lockForUpdate()->firstOrFail();

            if ($data['type'] === 'pickup' && $lockedOrder->status === 'len_don') {
                $this->decreaseStocks($this->itemsForStock($lockedOrder));

                $lockedOrder->update([
                    'status' => 'da_gui',
                    'pickup_reminder_dismissed' => true,
                ]);
            }

            if ($data['type'] === 'return' && $lockedOrder->status === 'da_gui') {
                $this->increaseStocks($this->itemsForStock($lockedOrder));

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
            ->map(fn ($productItems) => $productItems->sum('quantity'))
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
}
