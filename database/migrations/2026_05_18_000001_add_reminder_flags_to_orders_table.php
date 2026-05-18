<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('pickup_reminder_dismissed')->default(false)->after('status');
            $table->boolean('return_reminder_dismissed')->default(false)->after('pickup_reminder_dismissed');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_reminder_dismissed', 'return_reminder_dismissed']);
        });
    }
};
