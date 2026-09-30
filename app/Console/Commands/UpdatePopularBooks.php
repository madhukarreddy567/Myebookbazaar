<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\PurchaseStatsService;
use Illuminate\Console\Command;

/**
 * Recalculates popular/bestseller flags for all books.
 *
 * A book becomes "popular" when it hits the sales threshold
 * (10+ this month OR 50+ total). Popular books appear in the
 * Popular section AND keep showing in their primary category.
 *
 * Run via scheduler (hourly) or manually.
 */
class UpdatePopularBooks extends Command
{
    protected $signature = 'books:update-popular';
    protected $description = 'Recalculate popular/bestseller flags based on sales';

    public function handle(PurchaseStatsService $stats): int
    {
        $updated = 0;

        Book::where('is_active', true)->chunk(100, function ($books) use ($stats, &$updated) {
            foreach ($books as $book) {
                $isPopular = $stats->isPopular($book);
                $bookStats = $stats->forBook($book);

                // Popular score: weighted (recent sales matter more).
                $score = ($bookStats['this_week'] * 3)
                    + ($bookStats['this_month'] * 2)
                    + $bookStats['total'];

                if ($book->is_popular !== $isPopular || $book->popular_score !== $score) {
                    $book->is_popular = $isPopular;
                    $book->popular_score = $score;
                    $book->saveQuietly();
                    $updated++;
                }
            }
        });

        $this->info("Updated {$updated} books.");

        return self::SUCCESS;
    }
}
