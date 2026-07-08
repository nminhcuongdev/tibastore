<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('total_amount')->default(0)->after('payment_status');
            $table->unsignedBigInteger('shipping_fee')->default(0)->after('total_amount');
            $table->unsignedBigInteger('payment_1')->default(0)->after('shipping_fee');
            $table->unsignedBigInteger('payment_2')->default(0)->after('payment_1');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'shipping_fee', 'payment_1', 'payment_2']);
        });
    }
};
