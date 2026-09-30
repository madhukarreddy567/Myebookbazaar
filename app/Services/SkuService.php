<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;

/**
 * Auto-generates SKU codes for books.
 *
 * Format: {CATEGORY}-{NUMBER}-{EDITION}
 * Examples:
 *   FIT-001-E1  (Fitness category, 1st book, Edition 1)
 *   SKL-012-E2  (Skills category, 12th book, Edition 2)
 *   MON-003     (Money category, 3rd book, no edition)
 *
 * - Category code: first 3 letters of category name, uppercase.
 * - Number: sequential within the category, zero-padded to 3 digits.
 * - Edition: "E" + edition number extracted from the edition field.
 */
class SkuService
{
    /**
     * Generate the next SKU for a book in the given category.
     */
    public function generateFor(Book $book): string
    {
        $categoryCode = $this->categoryCode($book->category);
        $number = $this->nextNumberInCategory($book->category_id, $book->id);
        $editionSuffix = $this->editionSuffix($book->edition);

        $sku = sprintf('%s-%03d', $categoryCode, $number);

        if ($editionSuffix) {
            $sku .= '-' . $editionSuffix;
        }

        // Ensure uniqueness (in case of race conditions or manual edits).
        $base = $sku;
        $i = 2;
        while (Book::where('sku', $sku)->where('id', '!=', $book->id)->exists()) {
            $sku = $base . '-' . $i++;
        }

        return $sku;
    }

    /**
     * Assign a SKU to a book if it doesn't have one.
     */
    public function ensureSku(Book $book): string
    {
        if ($book->sku) {
            return $book->sku;
        }

        $book->sku = $this->generateFor($book);
        $book->saveQuietly();

        return $book->sku;
    }

    /**
     * Regenerate SKU when category or edition changes.
     */
    public function regenerateFor(Book $book): string
    {
        $book->sku = $this->generateFor($book);
        $book->saveQuietly();

        return $book->sku;
    }

    protected function categoryCode(?Category $category): string
    {
        if (! $category || ! $category->name) {
            return 'GEN';
        }

        // Take first 3 alphanumeric characters, uppercase.
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $category->name);
        $code = strtoupper(substr($clean, 0, 3));

        return $code !== '' ? $code : 'GEN';
    }

    protected function nextNumberInCategory(?int $categoryId, ?int $excludeBookId = null): int
    {
        $query = Book::where('category_id', $categoryId);

        if ($excludeBookId) {
            $query->where('id', '!=', $excludeBookId);
        }

        // Count existing books in category + 1.
        // We use max ID ordering to keep numbers stable.
        return $query->count() + 1;
    }

    protected function editionSuffix(?string $edition): ?string
    {
        if (! $edition) {
            return null;
        }

        // Extract number from "Edition 1", "Ed. 2", "1st Edition", etc.
        if (preg_match('/(\d+)/', $edition, $matches)) {
            return 'E' . $matches[1];
        }

        return null;
    }
}
