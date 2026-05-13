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
        Schema::create('tournament_group_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_group_id')->constrained('tournament_groups')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->unsignedSmallInteger('seed_number')->nullable()->comment('Hạt giống trong bảng, 1 = mạnh nhất');
            $table->timestamps();

            $table->unique(['tournament_group_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_group_teams');
    }
};