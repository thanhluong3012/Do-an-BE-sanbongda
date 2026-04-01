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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade')
                ->comment('Đội bóng');

            // Thông tin cầu thủ
            $table->string('player_name', 255)->comment('Tên cầu thủ');
            $table->string('code_id', 20)->nullable()->comment('CCCD/CMND');
            $table->integer('jersey_number')->nullable()->comment('Số áo: 1-99');
            $table->string('position', 50)->nullable()->comment('Vị trí: Thủ môn, Hậu vệ, Tiền vệ, Tiền đạo');

            // Thống kê (để tracking sau này)
            $table->integer('total_goals')->default(0)->comment('Tổng bàn thắng');
            $table->integer('yellow_cards')->default(0)->comment('Thẻ vàng');
            $table->integer('red_cards')->default(0)->comment('Thẻ đỏ');

            $table->timestamps();

            // Constraints
            $table->unique(['team_id', 'jersey_number'], 'uq_team_jersey');
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
