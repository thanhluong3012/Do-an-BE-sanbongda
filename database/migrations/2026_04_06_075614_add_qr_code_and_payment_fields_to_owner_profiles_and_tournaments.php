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
        // Thêm QR code image cho owner profiles
        Schema::table('owner_profiles', function (Blueprint $table) {
            $table->string('qr_code_image')->nullable()->after('code_id')->comment('Đường dẫn ảnh QR code ngân hàng của chủ sân');
        });

        // Thêm payment fields cho tournaments
        Schema::table('tournaments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'transfer'])->nullable()->after('organizer_id')->comment('Phương thức thanh toán: tiền mặt hoặc chuyển khoản');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->after('payment_method')->comment('Trạng thái thanh toán');
            $table->dateTime('payment_date')->nullable()->after('payment_status')->comment('Ngày thanh toán');
            $table->string('payment_proof')->nullable()->after('payment_date')->comment('Ảnh bill chuyển khoản (nếu có)');

            $table->index('payment_status');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa QR code từ owner profiles
        Schema::table('owner_profiles', function (Blueprint $table) {
            $table->dropColumn('qr_code_image');
        });

        // Xóa payment fields từ tournaments
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['payment_method', 'payment_status', 'payment_date', 'payment_proof']);
        });
    }
};
