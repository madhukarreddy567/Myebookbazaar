<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2B: ebooks. Prices are stored for BOTH regions; the
     * PriceService picks the right one per visitor. PDFs live in private
     * storage (storage/app/private) and are never publicly reachable.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('author')->nullable();
            $table->string('edition', 50)->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->string('language', 50)->default('English');
            $table->string('cover_image')->nullable();
            $table->decimal('price_inr', 10, 2);
            $table->decimal('price_usd', 10, 2);
            $table->decimal('sale_price_inr', 10, 2)->nullable();
            $table->decimal('sale_price_usd', 10, 2)->nullable();
            $table->dateTime('sale_starts_at')->nullable();
            $table->dateTime('sale_ends_at')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('isbn', 50)->nullable();
            $table->date('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
