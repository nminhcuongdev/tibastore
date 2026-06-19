<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_expected_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('expected_receive_date');
            $table->unsignedInteger('expected_receive_quantity');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        DB::table('products')
            ->whereNotNull('expected_receive_date')
            ->where('expected_receive_quantity', '>', 0)
            ->orderBy('id')
            ->get([
                'id',
                'expected_receive_date',
                'expected_receive_quantity',
                'expected_received_at',
            ])
            ->each(function ($product) {
                DB::table('product_expected_receipts')->insert([
                    'product_id' => $product->id,
                    'expected_receive_date' => $product->expected_receive_date,
                    'expected_receive_quantity' => $product->expected_receive_quantity,
                    'received_at' => $product->expected_received_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_expected_receipts');
    }
};
