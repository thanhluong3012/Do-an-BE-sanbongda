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
            $table->foreignId('tournament_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tournaments')
                ->onDelete('cascade')
                ->comment('NULL = booking bình thường, có giá trị = booking cho giải đấu');

            $table->enum('booking_type', ['regular', 'tournament'])
                ->default('regular')
                ->after('tournament_id')
                ->comment('Phân loại booking');

            // Index để query nhanh
            $table->index(['tournament_id', 'day_book']);
            $table->index(['field_id', 'day_book', 'booking_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['tournament_id']);
            $table->dropIndex(['tournament_id', 'day_book']);
            $table->dropIndex(['field_id', 'day_book', 'booking_type']);
            $table->dropColumn(['tournament_id', 'booking_type']);
        });
    }
};
