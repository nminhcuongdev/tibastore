<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->json('purchase_plan')->nullable());
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->json('purchase_plan')->nullable());
        Schema::table('product_expected_receipts', fn (Blueprint $table) => $table->unsignedBigInteger('workshop_order_id')->nullable()->index());
    }
    public function down(): void
    {
        Schema::table('product_expected_receipts', function (Blueprint $table) {
            $table->dropIndex(['workshop_order_id']);
            $table->dropColumn('workshop_order_id');
        });
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->dropColumn('purchase_plan'));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('purchase_plan'));
    }
};
