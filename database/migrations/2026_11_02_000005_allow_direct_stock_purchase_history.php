<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE workshop_stock_transfers MODIFY workshop_order_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Giữ lịch sử nhập trực tiếp khi quay về cấu trúc cũ.
        DB::table('workshop_stock_transfers')->whereNull('workshop_order_id')->update(['workshop_order_id' => 0]);
        DB::statement('ALTER TABLE workshop_stock_transfers MODIFY workshop_order_id BIGINT UNSIGNED NOT NULL');
    }
};
