<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductExpectedReceipt;
use App\Models\WorkshopOrder;
use Illuminate\Validation\ValidationException;

class OrderPurchasePlanService
{
    public function sync(Order $order): void
    {
        $order->load('items.product');
        $active = [];
        $groups = $order->items->filter(fn ($item) => $item->fulfillment_source !== 'stock' && !empty($item->purchase_plan))
            ->groupBy(fn ($item) => $item->product->code.'|'.$item->fulfillment_source);
        if ($order->status === 'huy_don') {
            $groups = collect();
        }
        foreach ($groups as $items) {
            $first = $items->first();
            $plan = $first->purchase_plan;
            unset($plan['applied_lines'], $plan['updated_once']);
            foreach ($items as $item) {
                $itemPlan = $item->purchase_plan;
                unset($itemPlan['applied_lines'], $itemPlan['updated_once']);
                if ($itemPlan !== $plan) {
                    throw ValidationException::withMessages(['items' => 'Các dòng cùng mã và nguồn od phải dùng cùng kế hoạch mua.']);
                }
            }
            $products = Product::whereIn('id', collect($plan['lines'])->pluck('product_id'))->get()->keyBy('id');
            $quantities = [];
            foreach ($plan['lines'] as $line) {
                $product = $products->get($line['product_id']);
                if (!$product || $product->code !== $first->product->code) {
                    throw ValidationException::withMessages(['items' => 'Size cần mua phải thuộc đúng mã hàng đang chọn.']);
                }
                $quantities[$product->id] = ($quantities[$product->id] ?? 0) + (int) $line['quantity'];
            }
            $workshop = WorkshopOrder::firstOrNew(['customer_order_id' => $order->id,
                'product_code' => $first->product->code, 'supply_source' => $first->fulfillment_source]);
            if ($workshop->exists && $workshop->purchase_plan && ($workshop->stocked_at
                || ProductExpectedReceipt::where('workshop_order_id', $workshop->id)->whereNotNull('received_at')->exists())) {
                if ($workshop->purchase_plan !== $plan) {
                    throw ValidationException::withMessages(['items' => 'Kế hoạch od đã nhận hàng không thể sửa. Hãy tạo dòng đặt bổ sung.']);
                }
                $active[] = $workshop->id;
                continue;
            }
            $workshop->fill(['order_name' => $order->order_name, 'purchase_plan' => $plan,
                'quantity' => array_sum($quantities), 'returned_date' => $plan['expected_date'],
                'size_note' => collect($quantities)->map(fn ($qty, $id) => $products[$id]->size.': '.$qty)->implode(', ')]);
            if (!$workshop->exists) {
                $workshop->fill(['status' => WorkshopOrder::STATUS_NOT_YET, 'unit_price' => 0,
                    'ordered_date' => now('Asia/Ho_Chi_Minh')->toDateString()]);
            }
            $workshop->save();
            $active[] = $workshop->id;
            ProductExpectedReceipt::where('workshop_order_id', $workshop->id)->whereNull('received_at')
                ->whereNotIn('product_id', array_keys($quantities))->delete();
            foreach ($quantities as $productId => $quantity) {
                $receipt = ProductExpectedReceipt::firstOrNew(['workshop_order_id' => $workshop->id, 'product_id' => $productId]);
                if (!$receipt->received_at) {
                    $receipt->fill(['expected_receive_date' => $plan['expected_date'], 'expected_receive_quantity' => $quantity])->save();
                }
            }
        }
        // Chỉ dọn các dòng do kế hoạch này tạo, chưa nhận hàng; giữ nguyên lịch sử đã nhận.
        $obsolete = WorkshopOrder::where('customer_order_id', $order->id)->whereNotNull('purchase_plan')
            ->whereNotIn('id', $active)->where('status', '!=', WorkshopOrder::STATUS_ARRIVED)->get();
        foreach ($obsolete as $item) {
            if (!ProductExpectedReceipt::where('workshop_order_id', $item->id)->whereNotNull('received_at')->exists()) {
                ProductExpectedReceipt::where('workshop_order_id', $item->id)->whereNull('received_at')->delete();
                $item->delete();
            }
        }
        // Kiểm lại sau khi đã đồng bộ dự kiến, để phần mua thêm thực sự đủ bù thiếu tồn.
        if ($order->status !== 'huy_don') {
            app(OrderInventoryService::class)->assertItemsAvailable($order->items->toArray(),
                $order->pickup_date->toDateString(), $order->return_date->toDateString(), $order->id, $order->return_period);
            $warning = app(WorkshopDuplicateService::class)->warningForOrder($order->id);
            if ($warning) session()->flash('workshop_duplicate_warning', $warning);
        }
    }
}
