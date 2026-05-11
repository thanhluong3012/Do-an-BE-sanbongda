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
            // Thêm field lưu người xác nhận thanh toán (owner/admin)
            $table->unsignedBigInteger('confirmed_by_user_id')
                ->nullable()
                ->after('payment_confirmed_at')
                ->comment('ID của user (owner/admin) xác nhận thanh toán');

            // Foreign key tới bảng users
            $table->foreign('confirmed_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by_user_id']);
            $table->dropColumn('confirmed_by_user_id');
        });
    }
};
