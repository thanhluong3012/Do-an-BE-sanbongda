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
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name')->nullable()->comment('Tên doanh nghiệp/sân');
            $table->text('address')->nullable()->comment('Địa chỉ kinh doanh');
            $table->string('image_code')->nullable()->comment('Giấy phép kinh doanh');
            $table->string('code_id', 20)->nullable()->comment('Căn cước công dân');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
