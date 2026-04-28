<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            // Thêm payment_confirmed_at (thay thế payment_date)
            $table->dateTime('payment_confirmed_at')->nullable()->after('payment_proof');

            // Thêm payment_note cho ghi chú từ chối thanh toán
            $table->text('payment_note')->nullable()->after('payment_confirmed_at');
        });

        // Update payment_status values: unpaid -> pending
        DB::statement("ALTER TABLE tournament_registrations MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'");

        // Migrate data: unpaid -> pending
        DB::table('tournament_registrations')
            ->where('payment_status', 'unpaid')
            ->update(['payment_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback payment_status
        DB::statement("ALTER TABLE tournament_registrations MODIFY COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'");

        DB::table('tournament_registrations')
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'unpaid']);

        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_confirmed_at', 'payment_note']);
        });
    }
};
