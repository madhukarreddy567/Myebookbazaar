<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Download tracking: EVERY download attempt is logged (not just served
     * downloads) with IP + user agent. downloaded_at is stamped only when
     * the file is actually served, so it stays the "first successful
     * download" signal for the admin order view and refund-validity checks.
     *
     * MySQL 8 only: the nullability change uses a native ALTER TABLE
     * (doctrine/dbal is not installed).
     */
    public function up(): void
    {
        Schema::table('download_logs', function (Blueprint $table) {
            $table->string('user_agent', 1024)->nullable()->after('ip_address');
        });

        DB::statement('ALTER TABLE `download_logs` MODIFY `downloaded_at` TIMESTAMP NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE `download_logs` SET `downloaded_at` = NOW() WHERE `downloaded_at` IS NULL');

        DB::statement('ALTER TABLE `download_logs` MODIFY `downloaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        Schema::table('download_logs', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
