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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('format', ['round_robin', 'knockout', 'group_knockout']);
            $table->integer('max_teams');
            $table->integer('min_teams');
            $table->dateTime('registration_start');
            $table->dateTime('registration_end');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'open', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('field_cluster_id')
                ->constrained('field_clusters')
                ->onDelete('cascade');
            $table->foreignId('organizer_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamps();

            $table->index('status');
            $table->index('format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
