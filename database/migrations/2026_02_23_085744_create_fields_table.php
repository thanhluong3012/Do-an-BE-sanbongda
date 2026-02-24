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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_cluster_id')
                ->constrained('field_clusters')
                ->onDelete('cascade');
            $table->string('field_type', 50)->comment('Loại sân: 5v5/7v7/11v11');
            $table->enum('status', ['available', 'maintenance', 'inactive'])
                ->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
