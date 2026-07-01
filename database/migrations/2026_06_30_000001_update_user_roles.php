<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hai quyền chính thức: admin và cộng tác viên. Mặc định người dùng mới là cộng tác viên.
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'cong_tac_vien'");

        // Map quyền mặc định cũ ('user') sang cộng tác viên.
        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'cong_tac_vien']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");

        DB::table('users')
            ->where('role', 'cong_tac_vien')
            ->update(['role' => 'user']);
    }
};
