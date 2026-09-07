<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ghi lại số lượng của từng dòng hàng ĐANG bị trừ khỏi kho.
 *
 * Trước đây hệ thống chỉ lưu "đơn có đang trừ kho hay không" qua hai mốc
 * stock_decreased_at / stock_returned_at, còn trừ bao nhiêu thì suy ngược từ
 * số lượng dòng hàng. Cách suy ngược đó sai khi đơn đã kiểm thiếu, và cũng
 * không mô tả được trạng thái "hàng đã về kho nhưng chưa kiểm".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('stock_held')->default(0)->after('returned_quantity');
        });

        // ---- Điền giá trị cho dữ liệu cũ, đúng bằng số đang thực bị trừ ----
        // Dòng "chưa chốt size" chưa bao giờ giữ kho nên để 0.
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.size_pending', false)
            ->select([
                'order_items.id',
                'order_items.quantity',
                'order_items.returned_quantity',
                'orders.stock_decreased_at',
                'orders.stock_returned_at',
            ])
            ->get();

        foreach ($rows as $row) {
            $dangONgoaiKho = $row->stock_decreased_at !== null && $row->stock_returned_at === null;

            if ($dangONgoaiKho) {
                $held = (int) $row->quantity;
            } elseif ($row->returned_quantity !== null) {
                // Đơn đã kiểm: phần thiếu đã không được hoàn lại, coi như vẫn đang giữ.
                $held = max(0, (int) $row->quantity - (int) $row->returned_quantity);
            } else {
                $held = 0;
            }

            if ($held > 0) {
                DB::table('order_items')->where('id', $row->id)->update(['stock_held' => $held]);
            }
        }

        // ---- Trả hàng về kho cho các đơn đang ở trạng thái "Đã trả về" ----
        // Từ nay "Đã trả về" nghĩa là hàng đã nằm trong kho, không còn giữ chỗ nữa.
        $canTraLai = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'da_tra_ve')
            ->where('order_items.stock_held', '>', 0)
            ->select(['order_items.id', 'order_items.product_id', 'order_items.stock_held'])
            ->get();

        foreach ($canTraLai as $row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->increment('stock_quantity', (int) $row->stock_held);

            DB::table('order_items')->where('id', $row->id)->update(['stock_held' => 0]);
        }

        DB::table('orders')
            ->where('status', 'da_tra_ve')
            ->whereNotNull('stock_decreased_at')
            ->whereNull('stock_returned_at')
            ->update(['stock_returned_at' => now()]);
    }

    public function down(): void
    {
        // Đưa hàng của các đơn "Đã trả về" ra ngoài kho lại cho khớp quy ước cũ.
        $canTruLai = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'da_tra_ve')
            ->where('order_items.size_pending', false)
            ->select(['order_items.id', 'order_items.product_id', 'order_items.quantity'])
            ->get();

        foreach ($canTruLai as $row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->decrement('stock_quantity', (int) $row->quantity);
        }

        DB::table('orders')
            ->where('status', 'da_tra_ve')
            ->update(['stock_returned_at' => null]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('stock_held');
        });
    }
};
