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
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onDelete('cascade');
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');

            $table->dateTime('registration_date')->useCurrent();
            $table->enum('registration_status', ['pending', 'approved', 'rejected', 'withdrawn'])
                ->default('pending');

            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_method', 50)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('payment_proof')->nullable()->comment('Ảnh chuyển khoản');

            $table->text('notes')->nullable()->comment('Ghi chú của đội');
            $table->text('admin_notes')->nullable()->comment('Ghi chú của BTC');
            $table->text('rejected_reason')->nullable();

            $table->timestamps();

            $table->unique(['tournament_id', 'team_id'], 'uq_tournament_team_registration');
            $table->index('registration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
