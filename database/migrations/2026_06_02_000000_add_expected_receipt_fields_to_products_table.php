<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('expected_receive_date')->nullable()->after('stock_quantity');
            $table->unsignedInteger('expected_receive_quantity')->default(0)->after('expected_receive_date');
            $table->timestamp('expected_received_at')->nullable()->after('expected_receive_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'expected_receive_date',
                'expected_receive_quantity',
                'expected_received_at',
            ]);
        });
    }
};
