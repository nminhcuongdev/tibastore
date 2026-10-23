<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderTurnaroundService
{
    public function __construct(private OrderInventoryService $inventory)
    {
    }

    public function returns(array $productIds, string $pickupDate, ?int $excludeOrderId = null, array $pendingCodes = []): Collection
    {
        $codes = Product::whereIn('id', $productIds)->pluck('code')->all();
        return OrderItem::query()
            ->with(['order', 'product'])
            ->where(function ($query) use ($productIds, $codes, $pendingCodes) {
                $query->whereIn('product_id', $productIds)
                    ->orWhere(fn ($q) => $q->where('size_pending', true)->whereHas('product', fn ($p) => $p->whereIn('code', $codes)))
                    ->orWhereHas('product', fn ($p) => $p->whereIn('code', $pendingCodes));
            })
            ->whereHas('order', function ($query) use ($pickupDate, $excludeOrderId) {
                $query->whereDate('return_date', $pickupDate)
                    ->whereDate('pickup_date', '<', $pickupDate)
                    ->whereNotIn('status', ['huy_don', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
                    ->when($excludeOrderId !== null, fn ($q) => $q->where('id', '!=', $excludeOrderId));
            })->get();
    }

    public function reminders(?Carbon $today = null, bool $allUpcoming = false): Collection
    {
        $today = ($today ?? now('Asia/Ho_Chi_Minh'))->copy()->startOfDay();
        $orders = Order::with('items.product')
            ->whereDate('pickup_date', '>=', $today->toDateString())
            ->when(! $allUpcoming, fn ($query) => $query->whereDate('pickup_date', '<=', $today->copy()->addDay()->toDateString()))
            ->whereNotIn('status', ['huy_don', 'da_gui', Order::RETURNED_STATUS, Order::CHECKED_STATUS])
            ->orderBy('pickup_date')->orderBy('id')->get();
        $reminders = collect();

        foreach ($orders as $order) {
            $items = $order->items;
            $ids = $items->pluck('product_id')->unique()->all();
            $pendingCodes = $items->filter(fn ($item) => $item->size_pending)->map(fn ($item) => $item->product?->code)->filter()->all();
            $returnItems = $this->returns($ids, $order->pickup_date->toDateString(), $order->id, $pendingCodes);
            $returns = $returnItems->reject(fn ($item) => $item->size_pending)->groupBy('product_id');
            foreach ($items->groupBy(fn ($item) => $item->product?->code) as $code => $lines) {
                $matching = $returnItems->filter(fn ($item) => $item->product?->code === $code);
                $uncertain = $lines->contains(fn ($item) => $item->size_pending)
                    ? $matching : $matching->filter(fn ($item) => $item->size_pending);
                if ($uncertain->isNotEmpty()) {
                    $reminders->push([
                        'order' => $order, 'product' => $lines->first()->product,
                        'shortage' => null, 'size_pending' => true,
                        'needed_quantity' => $lines->sum('quantity'),
                        'returns' => $uncertain,
                    ]);
                }
            }
            if ($returns->isEmpty()) {
                continue;
            }
            $strict = $this->inventory->projectedAvailableQuantities(
                $ids, $order->pickup_date->toDateString(), $order->return_date->toDateString(), $order->id, false
            );
            foreach ($items->groupBy('product_id') as $productId => $lines) {
                $lines = $lines->reject(fn ($item) => $item->size_pending);
                if ($lines->isEmpty()) {
                    continue;
                }
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

    public function dueReminders(Collection $reminders, int $userId): Collection
    {
        $preferences = DB::table('order_reminder_preferences')->where('user_id', $userId)
            ->whereIn('order_id', $reminders->pluck('order.id')->unique())->get()->keyBy('order_id');
        $tomorrow = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();

        return $reminders->filter(function ($reminder) use ($preferences, $tomorrow) {
            $order = $reminder['order'];
            $date = $order->pickup_date->toDateString();
            if ($date > $tomorrow) {
                return false;
            }
            $preference = $preferences->get($order->id);
            if (! $preference || $preference->pickup_date !== $date) {
                return true;
            }
            return $preference->mode !== 'once' && Carbon::parse($preference->remind_after)->lte(now());
        });
    }

    public function acknowledge(Collection $reminders, int $userId, string $mode): void
    {
        foreach ($reminders->pluck('order')->unique('id') as $order) {
            DB::table('order_reminder_preferences')->updateOrInsert(
                ['user_id' => $userId, 'order_id' => $order->id],
                ['pickup_date' => $order->pickup_date->toDateString(), 'mode' => $mode,
                    'remind_after' => $mode === 'repeat' ? now()->addHours(5) : null]
            );
        }
    }
}
