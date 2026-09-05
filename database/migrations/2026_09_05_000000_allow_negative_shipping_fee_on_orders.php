<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cho phép tiền ship âm (ví dụ shop bù/giảm tiền ship cho khách).
 *
 * Cột đang là UNSIGNED nên MySQL sẽ từ chối số âm ngay ở tầng CSDL, bỏ mỗi
 * validate ở tầng ứng dụng là chưa đủ. Dùng SQL thô thay cho ->change() để
 * khỏi phải thêm doctrine/dbal chỉ vì một lần đổi kiểu cột.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite lưu INTEGER không phân biệt dấu nên vốn đã nhận số âm, không cần đổi.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders MODIFY shipping_fee BIGINT NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Đưa các giá trị âm về 0 trước, nếu không cột UNSIGNED sẽ không nhận.
            DB::table('orders')->where('shipping_fee', '<', 0)->update(['shipping_fee' => 0]);

            DB::statement('ALTER TABLE orders MODIFY shipping_fee BIGINT UNSIGNED NOT NULL DEFAULT 0');
        }
    }
};
