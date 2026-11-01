<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('draft_invoices', function (Blueprint $table) {
            $table->string('discount_type', 10)->default('fixed');
            $table->decimal('discount_value', 16, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('draft_invoices', fn (Blueprint $table) => $table->dropColumn(['discount_type', 'discount_value']));
    }
};
