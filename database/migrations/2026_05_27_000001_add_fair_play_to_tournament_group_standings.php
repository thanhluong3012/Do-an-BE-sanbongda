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
        Schema::table('tournament_group_standings', function (Blueprint $table) {
            $table->integer('fair_play_points')->default(0)->comment('Điểm Fair-play (âm, càng thấp càng tốt)')->after('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_group_standings', function (Blueprint $table) {
            $table->dropColumn('fair_play_points');
        });
    }
};
