<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trạng thái đơn không còn tự đổi theo ngày; bộ trạng thái mới do người dùng tự cập nhật.
        // SQLite khong ho tro ALTER COLUMN ... SET DEFAULT. Production chay MySQL
        // nen chi phat cau lenh nay o driver ho tro; phan cap nhat du lieu ben duoi
        // van chay o moi driver, nho vay chay duoc migration tren may local.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'chua_cho_size'");
        }

        // Map trạng thái cũ "Lên đơn" sang bước đầu của quy trình mới.
        DB::table('orders')
            ->where('status', 'len_don')
            ->update(['status' => 'chua_cho_size']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'len_don'");
        }

        DB::table('orders')
            ->where('status', 'chua_cho_size')
            ->update(['status' => 'len_don']);
    }
};
