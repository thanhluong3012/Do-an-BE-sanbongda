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
        Schema::create('tournament_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onDelete('cascade');

            $table->string('group_name', 10)->comment('A, B, C, D...');
            $table->integer('group_order');

            $table->timestamps();

            $table->unique(['tournament_id', 'group_name'], 'uq_tournament_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_groups');
    }
};
