<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->string('stock_receipt_method', 20)->nullable());
        // Chỉ phân loại lịch sử có bằng chứng đã cộng kho. Lịch sử rỗng có thể
        // là nhập tay hoặc thao tác cũ, nên giữ nguyên chưa rõ cách nhập.
        DB::table('workshop_orders')->whereNotNull('stocked_at')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $entry = DB::table('workshop_stock_transfers')->where('workshop_order_id', $order->id)
                    ->whereNull('undone_at')->orderByDesc('id')->first();
                if ($entry && !empty(json_decode($entry->lines, true))) {
                    DB::table('workshop_orders')->where('id', $order->id)->update(['stock_receipt_method' => 'pushed']);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->dropColumn('stock_receipt_method'));
    }
};
