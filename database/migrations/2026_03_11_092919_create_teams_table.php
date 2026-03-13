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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Đội trưởng');

            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->string('logo')->nullable();

            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
