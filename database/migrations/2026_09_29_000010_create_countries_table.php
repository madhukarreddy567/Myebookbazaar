<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso_code', 2)->unique();
            $table->string('name', 120);
            $table->string('phone_code', 12);
            // No timestamps: reference data seeded from a static array.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
