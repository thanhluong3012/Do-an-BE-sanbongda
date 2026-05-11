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
        Schema::table('books', function (Blueprint $table) {
            // Ngăn race condition: không thể có 2 booking cùng sân + ngày + giờ bắt đầu
            $table->unique(['field_id', 'day_book', 'start_time'], 'unique_field_day_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropUnique('unique_field_day_start');
        });
    }
};
