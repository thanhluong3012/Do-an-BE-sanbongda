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
        Schema::create('tournament_group_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_group_id')
                ->constrained('tournament_groups')
                ->onDelete('cascade');
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');

            $table->integer('matches_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('losses')->default(0);

            $table->integer('goals_for')->default(0)->comment('Bàn thắng');
            $table->integer('goals_against')->default(0)->comment('Bàn thua');
            $table->integer('goal_difference')->default(0)->comment('Hiệu số');

            $table->integer('points')->default(0)->comment('Điểm số');
            $table->integer('ranking')->nullable()->comment('Thứ hạng trong bảng');

            $table->boolean('is_qualified')->default(false)->comment('Có đi tiếp không');

            $table->timestamps();

            $table->unique(['tournament_group_id', 'team_id'], 'uq_group_team_standing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_group_standings');
    }
};
