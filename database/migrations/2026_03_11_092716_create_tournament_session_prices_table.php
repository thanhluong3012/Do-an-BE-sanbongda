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
        Schema::create('tournament_session_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_cluster_id')
                ->constrained('field_clusters')
                ->onDelete('cascade');
            $table->string('field_type', 50)->comment('5v5, 7v7, 11v11');
            $table->enum('day_type', ['weekday', 'weekend'])->comment('Ngày thường/Cuối tuần');
            $table->enum('session_type', ['morning', 'afternoon', 'evening', 'full_day'])
                ->comment('Buổi: sáng 6-12h, chiều 12-18h, tối 18-23h, cả ngày 6-23h');
            $table->decimal('price_per_session', 15, 2)->comment('Giá 1 sân/1 buổi');
            $table->timestamps();

            $table->unique(
                ['field_cluster_id', 'field_type', 'day_type', 'session_type'],
                'uq_tournament_session_prices'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_session_prices');
    }
};