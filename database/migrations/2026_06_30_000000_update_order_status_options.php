<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trạng thái đơn không còn tự đổi theo ngày; bộ trạng thái mới do người dùng tự cập nhật.
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'chua_cho_size'");

        // Map trạng thái cũ "Lên đơn" sang bước đầu của quy trình mới.
        DB::table('orders')
            ->where('status', 'len_don')
            ->update(['status' => 'chua_cho_size']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'len_don'");

        DB::table('orders')
            ->where('status', 'chua_cho_size')
            ->update(['status' => 'len_don']);
    }
};
