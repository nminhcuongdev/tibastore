<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workshop_orders', function (Blueprint $table) {
            $table->json('size_quantities')->nullable();
            $table->timestamp('stocked_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('workshop_orders', fn (Blueprint $table) => $table->dropColumn(['size_quantities', 'stocked_at']));
    }
};
