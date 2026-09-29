<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // Country is chosen once at registration and is read-only afterwards.
            $table->string('country_code', 2);
            $table->string('country_name', 120);
            // Pricing region derived from country: IN = India, INTL = everyone else.
            $table->enum('pricing_region', ['IN', 'INTL'])->default('INTL');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->index('country_code');
            $table->index('pricing_region');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
