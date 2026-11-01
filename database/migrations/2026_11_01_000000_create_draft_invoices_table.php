<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('draft_invoices', function (Blueprint $table) {
            $table->id();
            $table->date('invoice_date');
            $table->string('customer_name');
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_code', 100)->nullable();
            $table->json('items');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_invoices');
    }
};
