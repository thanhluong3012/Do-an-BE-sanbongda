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
        Schema::table('owner_profiles', function (Blueprint $table) {
            // Thông tin tài khoản ngân hàng để tạo VietQR động
            $table->string('bank_id', 10)->nullable()->after('qr_code_image')
                ->comment('Mã ngân hàng (BIN Code) - VD: 970422 cho MB Bank');

            $table->string('account_no', 50)->nullable()->after('bank_id')
                ->comment('Số tài khoản ngân hàng');

            $table->string('account_name', 255)->nullable()->after('account_no')
                ->comment('Tên chủ tài khoản (viết HOA không dấu)');

            // Index để tìm kiếm nhanh
            $table->index('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_profiles', function (Blueprint $table) {
            $table->dropIndex(['bank_id']);
            $table->dropColumn(['bank_id', 'account_no', 'account_name']);
        });
    }
};
