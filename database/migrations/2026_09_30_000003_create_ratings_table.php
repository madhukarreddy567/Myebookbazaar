<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verified-purchase-only book ratings.
 *
 * - One rating per user per book (unique constraint).
 * - Eligibility (paid order ownership) is enforced in the RatingPolicy
 *   and RatingController — never trusted from the client.
 * - is_visible lets admins hide abusive reviews without deleting them;
 *   only visible reviews count toward averages shown on the store.
 * - No ratings are ever seeded: zero fake content, by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->text('review')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
            $table->index(['book_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
