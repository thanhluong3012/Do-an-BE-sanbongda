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
            // Trạng thái thanh toán
            $table->enum('payment_status', ['pending', 'paid', 'failed'])
                ->default('pending')
                ->after('total_price')
                ->comment('Trạng thái thanh toán: pending = chờ thanh toán, paid = đã thanh toán, failed = từ chối');

            // Phương thức thanh toán
            $table->enum('payment_method', ['cash', 'transfer'])
                ->nullable()
                ->after('payment_status')
                ->comment('Phương thức thanh toán: cash = tiền mặt, transfer = chuyển khoản');

            // Ảnh bill chuyển khoản
            $table->string('payment_proof')->nullable()
                ->after('payment_method')
                ->comment('Đường dẫn ảnh bill chuyển khoản');

            // Thời gian xác nhận thanh toán
            $table->timestamp('payment_confirmed_at')->nullable()
                ->after('payment_proof')
                ->comment('Thời gian owner xác nhận thanh toán');

            // Ghi chú thanh toán
            $table->text('payment_note')->nullable()
                ->after('payment_confirmed_at')
                ->comment('Ghi chú của owner về thanh toán');

            // Index để query nhanh
            $table->index(['payment_status', 'day_book']);
            $table->index(['field_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'day_book']);
            $table->dropIndex(['field_id', 'payment_status']);
            $table->dropColumn([
                'payment_status',
                'payment_method',
                'payment_proof',
                'payment_confirmed_at',
                'payment_note'
            ]);
        });
    }
};
