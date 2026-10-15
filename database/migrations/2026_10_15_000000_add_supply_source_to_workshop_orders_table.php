<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nguồn hàng của dòng đặt: tự đặt xưởng may hay mua ngoài.
 *
 * Khác với cột source ("Nguồn đặt") đang ghi tự do tên người đặt hộ;
 * cột này chỉ nhận hai giá trị cố định nên lọc và thống kê được.
 * Dữ liệu cũ vốn là hàng đặt xưởng nên mặc định "xuong_may".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->string('supply_source', 20)->default('xuong_may')->after('product_code');
            $table->index('supply_source', 'workshop_orders_supply_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->dropIndex('workshop_orders_supply_source_index');
            $table->dropColumn('supply_source');
        });
    }
};
