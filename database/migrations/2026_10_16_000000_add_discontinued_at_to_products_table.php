<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép "ngừng dùng" một mã hàng thay vì xóa hẳn.
 *
 * Mã đã từng lên đơn không xóa được (khóa ngoại RESTRICT) vì xóa đi sẽ làm
 * hỏng đơn cũ và báo cáo. Mốc này đánh dấu mã thôi dùng: biến khỏi kho và
 * khỏi gợi ý lên đơn, nhưng đơn cũ vẫn tra cứu được bình thường.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('discontinued_at')->nullable()->after('image_path');
            $table->index('discontinued_at', 'products_discontinued_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_discontinued_at_index');
            $table->dropColumn('discontinued_at');
        });
    }
};
