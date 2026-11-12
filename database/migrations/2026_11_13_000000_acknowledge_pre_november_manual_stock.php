<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('workshop_orders')->where('status', 'da_ve')->whereNull('stock_receipt_method')
            ->whereRaw('COALESCE(returned_date, ordered_date, created_at) < ?', ['2026-11-01'])
            ->orderBy('id')->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        $row = DB::table('workshop_orders')->where('id', $order->id)->lockForUpdate()->first();
                        if (!$row || $row->status !== 'da_ve' || $row->stock_receipt_method !== null) return;
                        $recordedAt = now();
                        DB::table('workshop_orders')->where('id', $row->id)->update([
                            'stock_receipt_method' => 'manual', 'stocked_at' => $row->stocked_at ?? $recordedAt,
                        ]);
                        DB::table('product_expected_receipts')->where('workshop_order_id', $row->id)
                            ->whereNull('received_at')->update(['received_at' => $recordedAt]);
                        DB::table('workshop_stock_transfers')->insert([
                            'workshop_order_id' => $row->id, 'product_code' => $row->product_code,
                            'supply_source' => $row->supply_source, 'order_name' => $row->order_name,
                            'supplier' => $row->source, 'previous_status' => $row->status,
                            'lines' => '[]', 'user_id' => null, 'transferred_at' => $recordedAt,
                            'can_undo' => false, 'created_at' => $recordedAt, 'updated_at' => $recordedAt,
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Ghi nhận dữ liệu hàng cũ theo xác nhận của chủ cửa hàng;
        // không mở lại dự kiến hoặc thay đổi tồn khi rollback mã nguồn.
    }
};
