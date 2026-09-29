<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: book bundles sold at one combo price. Buying a combo
     * creates one order item per book (so every book stays
     * individually downloadable), with the combo price split across
     * the lines.
     */
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('book_ids');
            $table->decimal('combo_price_inr', 10, 2);
            $table->decimal('combo_price_usd', 10, 2);
            $table->decimal('sale_price_inr', 10, 2)->nullable();
            $table->decimal('sale_price_usd', 10, 2)->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
