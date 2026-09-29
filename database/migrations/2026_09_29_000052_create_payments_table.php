<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3: payment attempts. razorpay_order_id and
     * razorpay_payment_id are unique so replayed webhooks can never
     * double-count a payment (idempotency at the database level).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('razorpay_order_id')->unique()->nullable();
            $table->string('razorpay_payment_id')->unique()->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('status', 16)->default('created'); // created, authorized, captured, failed
            $table->json('raw_webhook')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
