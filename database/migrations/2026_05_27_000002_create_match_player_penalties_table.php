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
        Schema::create('match_player_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')
                ->constrained('matches')
                ->onDelete('cascade');
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');
            $table->foreignId('player_id')
                ->constrained('team_members')
                ->onDelete('cascade');
            $table->enum('card_type', ['yellow', 'red'])->comment('yellow hoặc red');
            $table->integer('minute')->nullable()->comment('Phút bị thẻ');
            $table->timestamps();

            $table->index(['match_id', 'team_id']);
            $table->index(['player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_player_penalties');
    }
};
