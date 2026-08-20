<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng orders trước đây chỉ có index của khoá ngoại product_id, nên mọi bộ lọc
 * và sắp xếp ở màn danh sách đơn đều phải quét toàn bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Sắp xếp mặc định của danh sách đơn.
            $table->index(['updated_at', 'id'], 'orders_updated_at_id_index');

            // Các cột được lọc / sắp xếp trực tiếp.
            $table->index('status', 'orders_status_index');
            $table->index('source', 'orders_source_index');
            $table->index('region', 'orders_region_index');
            $table->index('closer_name', 'orders_closer_name_index');
            $table->index('order_name', 'orders_order_name_index');
            $table->index('pickup_date', 'orders_pickup_date_index');
            $table->index('event_date', 'orders_event_date_index');
            $table->index('return_date', 'orders_return_date_index');
            $table->index('created_at', 'orders_created_at_index');

            // Dùng cho phần kiểm tồn: lọc đơn đang giữ hàng ngoài kho.
            $table->index(['stock_decreased_at', 'stock_returned_at'], 'orders_stock_state_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_updated_at_id_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_source_index');
            $table->dropIndex('orders_region_index');
            $table->dropIndex('orders_closer_name_index');
            $table->dropIndex('orders_order_name_index');
            $table->dropIndex('orders_pickup_date_index');
            $table->dropIndex('orders_event_date_index');
            $table->dropIndex('orders_return_date_index');
            $table->dropIndex('orders_created_at_index');
            $table->dropIndex('orders_stock_state_index');
        });
    }
};
