<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_import_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('previous_quantity');
            $table->unsignedInteger('new_quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_import_histories');
    }
};
