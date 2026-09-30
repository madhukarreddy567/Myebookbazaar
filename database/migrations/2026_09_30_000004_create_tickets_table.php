<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets (customer + admin inbox).
 *
 * - status: open / awaiting_customer / awaiting_support / closed.
 * - order_id is nullable: tickets may (but need not) reference one of
 *   the customer's own orders, so support can see download tracking
 *   for refund-validity checks at a glance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 150);
            $table->string('category', 20);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
