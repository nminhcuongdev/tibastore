<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiền của hàng đặt xưởng: số lượng đặt và đơn giá.
 *
 * Thành tiền (đơn giá × số lượng) không lưu ở đây mà tính khi hiển thị,
 * để không bao giờ lệch với hai cột gốc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('size_note');
            // Dùng bigInteger có dấu cho thoáng: sau này cần ghi nhận khoản
            // giảm giá/bù trừ âm thì không phải đổi kiểu cột như bên tiền ship.
            $table->bigInteger('unit_price')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_price']);
        });
    }
};
