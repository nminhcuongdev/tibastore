<?php

namespace App\Services;

use App\Models\WorkshopOrder;

class WorkshopDuplicateService
{
    public function duplicates(): array
    {
        $normalize = fn ($value) => mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)), 'UTF-8');
        $groups = WorkshopOrder::query()->select('id', 'product_code', 'order_name')->get()
            ->filter(fn ($row) => $normalize($row->order_name) !== ''
                && $normalize($row->product_code) !== '' && $row->product_code !== WorkshopOrder::NO_PRODUCT_CODE)
            ->groupBy(fn ($row) => json_encode([$normalize($row->order_name), $normalize($row->product_code)]));
        $duplicates = [];
        foreach ($groups as $rows) {
            if ($rows->count() < 2) continue;
            foreach ($rows as $row) {
                $duplicates[$row->id] = $rows->where('id', '!=', $row->id)->pluck('id')->values()->all();
            }
        }
        return $duplicates;
    }

    public function warningForOrder(int $orderId): ?array
    {
        $duplicates = $this->duplicates();
        $ids = WorkshopOrder::where('customer_order_id', $orderId)->pluck('id');
        foreach ($ids as $id) {
            if (isset($duplicates[$id])) return ['focus' => $id,
                'message' => 'Cảnh báo: phần od của đơn này trùng Tên đơn và Mã hàng với dòng đặt hàng khác. Các dòng nghi trùng được tô đỏ; hãy kiểm tra trước khi đặt hoặc nhập kho.'];
        }
        return null;
    }
}
