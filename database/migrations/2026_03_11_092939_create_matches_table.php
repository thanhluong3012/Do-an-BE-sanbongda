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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')
                ->constrained('tournaments')
                ->onDelete('cascade');
            $table->foreignId('tournament_round_id')
                ->constrained('tournament_rounds')
                ->onDelete('cascade');
            $table->foreignId('tournament_group_id')
                ->nullable()
                ->constrained('tournament_groups')
                ->onDelete('set null')
                ->comment('Chỉ có khi là vòng bảng');

            // Đội thi đấu
            $table->foreignId('home_team_id')
                ->nullable()
                ->constrained('teams')
                ->onDelete('set null')
                ->comment('NULL = TBA (To Be Announced)');
            $table->foreignId('away_team_id')
                ->nullable()
                ->constrained('teams')
                ->onDelete('set null');

            // Cho knockout: biết đội từ đâu
            $table->foreignId('home_from_match_id')
                ->nullable()
                ->constrained('matches')
                ->onDelete('set null')
                ->comment('Đội nhà = thắng trận nào');
            $table->foreignId('away_from_match_id')
                ->nullable()
                ->constrained('matches')
                ->onDelete('set null')
                ->comment('Đội khách = thắng trận nào');
            $table->string('home_from_position', 50)->nullable()->comment('VD: Nhất bảng A');
            $table->string('away_from_position', 50)->nullable();

            // Thông tin trận đấu
            $table->foreignId('field_id')
                ->constrained('fields')
                ->onDelete('restrict')
                ->comment('Sân thi đấu cụ thể');
            $table->date('match_date');
            $table->time('match_time');
            $table->integer('match_number')->nullable()->comment('Số thứ tự trận');

            // Kết quả
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            // Penalty (nếu hòa trong knockout)
            $table->integer('home_penalty_score')->nullable();
            $table->integer('away_penalty_score')->nullable();

            $table->foreignId('winner_team_id')
                ->nullable()
                ->constrained('teams')
                ->onDelete('set null');

            // Thông tin khác
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'postponed', 'cancelled'])
                ->default('scheduled');
            $table->string('referee_name')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['match_date', 'match_time']);
            $table->index('status');
            $table->index(['field_id', 'match_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
