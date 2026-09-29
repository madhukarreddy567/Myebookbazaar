<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google OAuth sign-ups don't provide a country, so the columns
     * become nullable. pricing_region keeps its INTL default for them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->change();
            $table->string('country_name', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable(false)->change();
            $table->string('country_name', 120)->nullable(false)->change();
        });
    }
};
