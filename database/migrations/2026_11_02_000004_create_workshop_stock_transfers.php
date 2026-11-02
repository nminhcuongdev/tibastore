<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workshop_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workshop_order_id')->index();
            $table->string('product_code', 50)->index();
            $table->string('supply_source', 20);
            $table->string('order_name')->nullable();
            $table->string('supplier')->nullable();
            $table->string('previous_status', 20);
            $table->json('lines');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamp('undone_at')->nullable();
            $table->unsignedBigInteger('undone_by')->nullable();
            $table->boolean('can_undo')->default(true);
            $table->timestamps();
        });
        // Khôi phục lịch sử cũ từ dữ liệu đã khóa sau nhập; chỉ cho hoàn tác khi có phiếu nhập đối chiếu.
        DB::table('workshop_orders')->whereNotNull('stocked_at')->orderBy('id')->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                $plan = json_decode($order->purchase_plan ?? 'null', true);
                $lines = $plan['lines'] ?? json_decode($order->size_quantities ?? '[]', true);
                $grouped = collect($lines)->groupBy('product_id')->map(fn ($rows) => $rows->sum('quantity'));
                $snapshot = []; $verified = $grouped->isNotEmpty();
                if (DB::table('workshop_orders')->where('product_code', $order->product_code)->where('id', '!=', $order->id)
                    ->whereBetween('stocked_at', [Carbon::parse($order->stocked_at)->subSeconds(10), Carbon::parse($order->stocked_at)->addSeconds(10)])->exists()) {
                    $verified = false;
                }
                foreach ($grouped as $id => $quantity) {
                    $product = DB::table('products')->find($id);
                    $historyCount = DB::table('stock_import_histories')->where('product_id', $id)->where('quantity', $quantity)
                        ->whereBetween('created_at', [Carbon::parse($order->stocked_at)->subSeconds(10), Carbon::parse($order->stocked_at)->addSecond()])->count();
                    $verified = $verified && $historyCount === 1 && $product !== null;
                    $snapshot[] = ['product_id' => (int) $id, 'size' => $product->size ?? 'Đã xóa', 'quantity' => (int) $quantity];
                }
                DB::table('workshop_stock_transfers')->insert(['workshop_order_id' => $order->id, 'product_code' => $order->product_code,
                    'supply_source' => $order->supply_source, 'order_name' => $order->order_name, 'supplier' => $order->source,
                    'previous_status' => 'da_od', 'lines' => json_encode($snapshot), 'transferred_at' => $order->stocked_at,
                    'can_undo' => $verified, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('workshop_stock_transfers');
    }
};
