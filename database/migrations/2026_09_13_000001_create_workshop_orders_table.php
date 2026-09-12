<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Theo dõi hàng đặt xưởng: đặt mã nào, ai đặt, bao giờ xưởng trả.
 * Mã hàng bắt buộc là mã đã có trong kho; ảnh và tên lấy theo mã đó nên
 * không lưu lại ở đây, tránh dữ liệu lệch khi kho đổi ảnh/tên.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50);
            $table->string('size_note')->nullable();
            $table->string('order_name')->nullable();
            $table->text('buyer_note')->nullable();
            $table->string('source')->nullable();
            $table->date('ordered_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->string('buyer')->nullable();
            $table->string('status', 20)->default('chua_ve');
            $table->timestamps();

            $table->index('product_code', 'workshop_orders_product_code_index');
            $table->index('status', 'workshop_orders_status_index');
            $table->index('ordered_date', 'workshop_orders_ordered_date_index');
            $table->index('returned_date', 'workshop_orders_returned_date_index');
            // Sắp xếp mặc định của danh sách.
            $table->index(['created_at', 'id'], 'workshop_orders_created_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_orders');
    }
};
