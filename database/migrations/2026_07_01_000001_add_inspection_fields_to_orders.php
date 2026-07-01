<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('check_note')->nullable()->after('payment_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Số lượng thực nhận lại vào kho khi kiểm đơn (null nghĩa là chưa kiểm).
            $table->unsignedInteger('returned_quantity')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('check_note');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });
    }
};
