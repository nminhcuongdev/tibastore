<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ghi chú chung của cả đơn hàng.
 *
 * Khác với order_items.note (ghi chú riêng của từng mã hàng) và
 * orders.check_note (chỉ ghi lúc kiểm đơn), đây là chỗ ghi những dặn dò
 * chung của đơn và được hiện thẳng ngoài danh sách.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('note')->nullable()->after('order_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
