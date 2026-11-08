<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where(fn ($query) => $query->whereNull('dispatch_date')->orWhereNull('actual_return_date'))
            ->update([
                'dispatch_date' => DB::raw('COALESCE(dispatch_date, pickup_date)'),
                'actual_return_date' => DB::raw('COALESCE(actual_return_date, return_date)'),
            ]);
    }

    public function down(): void
    {
        // Không thể phân biệt ngày vừa bổ sung với ngày người dùng đã nhập.
    }
};
