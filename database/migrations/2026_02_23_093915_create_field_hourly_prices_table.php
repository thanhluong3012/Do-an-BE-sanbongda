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
        Schema::create('field_hourly_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_cluster_id')
                ->constrained('field_clusters')
                ->onDelete('cascade')
                ->comment('Thuộc cụm sân');
            $table->enum('day_type', ['weekday', 'weekend'])
                ->comment('Loại ngày: ngày thường hoặc cuối tuần');
            $table->string('field_type', 50)
                ->comment('Loại sân: 5v5, 7v7, 11v11');
            $table->decimal('price_hour', 12, 2)
                ->comment('Giá theo giờ');
            $table->timestamps();

            $table->unique(
                ['field_cluster_id', 'day_type', 'field_type'],
                'uq_field_hourly_prices_cluster_day_field'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_hourly_prices');
    }
};
