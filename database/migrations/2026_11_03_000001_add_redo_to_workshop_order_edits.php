<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workshop_order_edits', function (Blueprint $table) {
            $table->timestamp('redo_invalidated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workshop_order_edits', fn (Blueprint $table) => $table->dropColumn('redo_invalidated_at'));
    }
};
