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
        Schema::table('books', function (Blueprint $table) {
            // Thêm cột cho VNPay
            $table->string('vnpay_txn_ref', 50)->nullable()
                ->after('payment_method')
                ->comment('Mã giao dịch VNPay (format: {booking_id}_{timestamp})');

            $table->string('vnpay_transaction_no', 50)->nullable()
                ->after('vnpay_txn_ref')
                ->comment('Mã giao dịch từ VNPay (vnp_TransactionNo)');

            $table->string('vnpay_bank_code', 20)->nullable()
                ->after('vnpay_transaction_no')
                ->comment('Mã ngân hàng/ví thanh toán');

            $table->timestamp('vnpay_paid_at')->nullable()
                ->after('vnpay_bank_code')
                ->comment('Thời điểm thanh toán thành công qua VNPay');

            $table->string('vnpay_response_code', 10)->nullable()
                ->after('vnpay_paid_at')
                ->comment('Mã phản hồi từ VNPay (00=thành công)');

            $table->text('vnpay_callback_data')->nullable()
                ->after('vnpay_response_code')
                ->comment('Dữ liệu callback từ VNPay (JSON)');

            // Thêm index
            $table->index('vnpay_txn_ref');
            $table->index('vnpay_transaction_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['vnpay_txn_ref']);
            $table->dropIndex(['vnpay_transaction_no']);
            $table->dropColumn([
                'vnpay_txn_ref',
                'vnpay_transaction_no',
                'vnpay_bank_code',
                'vnpay_paid_at',
                'vnpay_response_code',
                'vnpay_callback_data'
            ]);
        });
    }
};
