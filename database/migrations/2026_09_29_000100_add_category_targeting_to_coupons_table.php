<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 refinements: coupons can target whole categories and can be
 * limited to a customer's first order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->json('applies_category_ids')->nullable()->after('applies_book_ids');
            $table->boolean('first_order_only')->default(false)->after('applies_category_ids');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['applies_category_ids', 'first_order_only']);
        });
    }
};
