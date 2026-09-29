<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6: real TOTP two-factor authentication for admin accounts.
 * - two_factor_secret: base32 TOTP secret, encrypted with Laravel Crypt
 *   before storage (handled by the Admin model mutator).
 * - two_factor_enabled_at: null until the admin confirms a valid code.
 * - two_factor_recovery_codes: JSON array of bcrypt-hashed codes,
 *   single-use (consumed on use).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->timestamp('two_factor_enabled_at')->nullable()->after('two_factor_secret');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled_at', 'two_factor_recovery_codes']);
        });
    }
};
