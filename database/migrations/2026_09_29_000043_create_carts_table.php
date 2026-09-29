<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2B: shopping carts. A row belongs to either a logged-in user
     * (user_id) or a guest session (session_id) — never both at once.
     * Digital goods: quantity is kept for compatibility but is always 1.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('added_at')->useCurrent();

            $table->index(['user_id', 'book_id']);
            $table->index(['session_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
