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
        Schema::create('field_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->constrained('owner_profiles')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->text('address');
            $table->string('phone', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_clusters');
    }
};
