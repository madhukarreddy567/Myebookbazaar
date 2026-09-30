<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GST on digital-only Indian orders (owner-decided 2026-09-30).
     * - gst_rate: the GST % in force when the order was created (stored
     *   so historical orders keep their rate when the setting changes).
     * - gst_amount: the GST portion extracted (inclusive) from the order
     *   total. The advertised price is always the final total; GST is
     *   never added on top.
     * Existing (pre-GST) orders get rate/amount 0 — no backfill.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->default(0)->after('tax_total');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gst_rate', 'gst_amount']);
        });
    }
};
