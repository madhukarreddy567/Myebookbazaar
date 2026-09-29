<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7: backups table for the auto-updater's pre-update and scheduled
 * backups. Rows are append-only in spirit — the UI never edits them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255);
            $table->string('type', 32)->default('manual'); // manual|auto|pre-update
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('version', 64)->nullable();
            $table->string('commit_sha', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
