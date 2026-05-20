<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Thay đổi: Bỏ 'transfer', thay bằng 'vietqr'
     * Payment methods: ['cash', 'vietqr']
     */
    public function up(): void
    {
        // Bước 1: Chuyển payment_method sang VARCHAR tạm thời
        DB::statement("ALTER TABLE books MODIFY COLUMN payment_method VARCHAR(20) NULL");
        DB::statement("ALTER TABLE tournaments MODIFY COLUMN payment_method VARCHAR(20) NULL");

        // Bước 2: Update tất cả giá trị 'transfer' và các giá trị khác thành 'vietqr'
        DB::table('books')
            ->whereIn('payment_method', ['transfer', 'vnpay'])
            ->update(['payment_method' => 'vietqr']);

        DB::table('tournaments')
            ->whereIn('payment_method', ['transfer', 'vnpay'])
            ->update(['payment_method' => 'vietqr']);

        // Bước 3: Chuyển về ENUM với 2 giá trị
        DB::statement("ALTER TABLE books MODIFY COLUMN payment_method ENUM('cash', 'vietqr') NULL COMMENT 'Phương thức thanh toán: cash = tiền mặt, vietqr = chuyển khoản QR'");
        DB::statement("ALTER TABLE tournaments MODIFY COLUMN payment_method ENUM('cash', 'vietqr') NULL COMMENT 'Phương thức thanh toán: cash = tiền mặt, vietqr = chuyển khoản QR'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Đổi lại 'vietqr' thành 'transfer'
        DB::table('books')
            ->where('payment_method', 'vietqr')
            ->update(['payment_method' => 'transfer']);

        DB::table('tournaments')
            ->where('payment_method', 'vietqr')
            ->update(['payment_method' => 'transfer']);

        // Đổi enum về cũ
        DB::statement("ALTER TABLE books MODIFY COLUMN payment_method ENUM('cash', 'transfer') NULL COMMENT 'Phương thức thanh toán: cash = tiền mặt, transfer = chuyển khoản'");
        DB::statement("ALTER TABLE tournaments MODIFY COLUMN payment_method ENUM('cash', 'transfer') NULL COMMENT 'Phương thức thanh toán: cash = tiền mặt, transfer = chuyển khoản'");
    }
};
