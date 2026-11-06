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
            $table->date('dispatch_date')->nullable();
            $table->date('actual_return_date')->nullable();
        });

        DB::table('orders')->whereIn('region', ['ha_noi', 'tinh_mb'])->update([
            'dispatch_date' => DB::raw('pickup_date'),
            'actual_return_date' => DB::raw('return_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['dispatch_date', 'actual_return_date']));
    }
};
