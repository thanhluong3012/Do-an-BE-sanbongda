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
        Schema::create('tournament_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onDelete('cascade');

            $table->date('match_date');
            $table->enum('session_type', ['morning', 'afternoon', 'evening', 'full_day']);
            $table->enum('day_type', ['weekday', 'weekend'])->comment('Tự động tính từ match_date');

            $table->integer('number_of_fields')->comment('Số sân thuê trong buổi này');
            $table->decimal('price_per_field', 15, 2)->comment('Giá 1 sân/buổi (lưu lại)');
            $table->decimal('session_cost', 15, 2)->comment('Chi phí = số sân × giá');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tournament_id', 'match_date', 'session_type'], 'uq_tournament_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_schedules');
    }
};
