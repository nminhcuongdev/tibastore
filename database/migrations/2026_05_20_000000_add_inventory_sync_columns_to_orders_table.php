<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_decreased_at')->nullable()->after('return_reminder_dismissed');
            $table->timestamp('stock_returned_at')->nullable()->after('stock_decreased_at');
        });

        $now = now();

        DB::table('orders')
            ->where('status', 'da_gui')
            ->update(['stock_decreased_at' => $now]);

        DB::table('orders')
            ->where('status', 'thanh_cong')
            ->update([
                'stock_decreased_at' => $now,
                'stock_returned_at' => $now,
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stock_decreased_at', 'stock_returned_at']);
        });
    }
};
