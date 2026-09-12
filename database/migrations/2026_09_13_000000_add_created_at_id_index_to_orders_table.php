<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh sách đơn đổi sắp xếp mặc định từ updated_at sang created_at (đơn mới tạo
 * lên đầu, cập nhật trạng thái không làm đơn nhảy lên). Index ghép (created_at, id)
 * khớp đúng thứ tự "ORDER BY created_at DESC, id DESC" nên gồm cả cột phân định.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'orders_created_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_id_index');
        });
    }
};
