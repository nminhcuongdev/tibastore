<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            // Dùng chung cho nhiều loại bản ghi (sản phẩm, đơn hàng...).
            $table->string('loggable_type', 100);
            $table->unsignedBigInteger('loggable_id');
            $table->string('event', 20);
            // Ảnh chụp tên/mã của bản ghi tại thời điểm ghi log, để sau này
            // bản ghi bị xóa thì lịch sử vẫn còn nhận ra là của cái gì.
            $table->string('subject_label', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 255)->nullable();
            // [{field, label, old, new}, ...]
            $table->json('change_set')->nullable();
            $table->timestamps();

            $table->index(['loggable_type', 'loggable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
