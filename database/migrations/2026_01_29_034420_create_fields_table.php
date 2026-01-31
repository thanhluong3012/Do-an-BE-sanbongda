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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // Loại sân: 5 người, 7 người, 11 người
            $table->string('location'); // Địa chỉ chi tiết
            $table->string('district'); // Quận/Huyện
            $table->decimal('price', 10, 2); // Giá thuê
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->decimal('rating', 3, 2)->default(0); // Đánh giá từ 0-5
            $table->time('openTime'); // Giờ mở cửa
            $table->time('closeTime'); // Giờ đóng cửa
            $table->text('image')->nullable(); // URL hoặc path của hình ảnh
            $table->text('rule')->nullable(); // Quy định của sân
            $table->json('features')->nullable(); // Các tiện ích: ["Đèn chiếu sáng", "Phòng thay đồ", "Bãi đỗ xe"]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
