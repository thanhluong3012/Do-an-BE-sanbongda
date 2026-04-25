<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Người nhận thông báo
            $table->string('type'); // tournament_approved, tournament_rejected, etc.
            $table->string('title'); // Tiêu đề ngắn gọn
            $table->text('message'); // Nội dung thông báo
            $table->json('data')->nullable(); // Dữ liệu bổ sung (tournament_id, registration_id, etc.)
            $table->boolean('is_read')->default(false); // Đã đọc chưa
            $table->timestamp('read_at')->nullable(); // Thời điểm đọc
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
