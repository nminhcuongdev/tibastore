<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personal_edits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('section', 30);
            $table->json('changes');
            $table->timestamp('undone_at')->nullable();
            $table->timestamp('redo_invalidated_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'section', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_edits');
    }
};
