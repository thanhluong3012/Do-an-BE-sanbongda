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
        Schema::create('tournament_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onDelete('cascade');

            $table->string('round_name', 50);
            $table->enum('round_type', [
                'group_stage',
                'round_of_32',
                'round_of_16',
                'quarter_final',
                'semi_final',
                'third_place',
                'final'
            ]);
            $table->integer('round_order');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->boolean('is_knockout')->default(false);

            $table->timestamps();

            $table->index(['tournament_id', 'round_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_rounds');
    }
};
