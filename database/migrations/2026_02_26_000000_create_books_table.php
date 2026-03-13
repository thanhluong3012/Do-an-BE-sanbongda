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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('field_id')
                ->constrained('fields')
                ->onDelete('cascade');
            $table->date('day_book')->comment('Ngày đặt sân');
            $table->time('start_time')->comment('Giờ bắt đầu');
            $table->time('end_time')->comment('Giờ kết thúc');
            $table->decimal('total_price', 10, 2)->comment('Tổng giá tiền');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
