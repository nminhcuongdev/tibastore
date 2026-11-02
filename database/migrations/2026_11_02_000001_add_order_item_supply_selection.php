<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfillment_source', 20)->default('stock');
        });
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_order_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->dropIndex(['customer_order_id']);
            $table->dropColumn('customer_order_id');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('fulfillment_source');
        });
    }
};
