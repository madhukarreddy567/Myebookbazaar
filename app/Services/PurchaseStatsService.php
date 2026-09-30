<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Purchase statistics for books — Amazon-style "X bought this month" counts.
 *
 * All counts are based on PAID orders only (completed purchases).
 */
class PurchaseStatsService
{
    /**
     * Get purchase stats for a book.
     *
     * @return array{total: int, this_week: int, this_month: int, last_month: int}
     */
    public function forBook(Book $book): array
    {
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $baseQuery = function () use ($book) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.book_id', $book->id)
                ->where('orders.status', 'paid');
        };

        return [
            'total' => (clone $baseQuery())->count(),
            'this_week' => (clone $baseQuery())
                ->where('orders.paid_at', '>=', $weekStart)
                ->count(),
            'this_month' => (clone $baseQuery())
                ->where('orders.paid_at', '>=', $monthStart)
                ->count(),
            'last_month' => (clone $baseQuery())
                ->whereBetween('orders.paid_at', [$lastMonthStart, $lastMonthEnd])
                ->count(),
        ];
    }

    /**
     * Get top-selling books for the popular/bestseller section.
     *
     * @param int $limit Number of books to return.
     * @param string $period 'week', 'month', or 'all'.
     */
    public function topSellers(int $limit = 10, string $period = 'month'): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->where('orders.status', 'paid')
            ->where('books.is_active', true)
            ->select('books.id', DB::raw('COUNT(*) as sales_count'))
            ->groupBy('books.id')
            ->orderByDesc('sales_count')
            ->limit($limit);

        if ($period === 'week') {
            $query->where('orders.paid_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($period === 'month') {
            $query->where('orders.paid_at', '>=', Carbon::now()->startOfMonth());
        }

        $results = $query->get();

        return $results->map(function ($row) {
            $book = Book::find($row->id);
            return $book ? ['book' => $book, 'sales' => $row->sales_count] : null;
        })->filter()->values()->all();
    }

    /**
     * Check if a book qualifies as "popular" (bestseller threshold).
     * A book is popular if it has 10+ sales this month OR 50+ total sales.
     */
    public function isPopular(Book $book): bool
    {
        $stats = $this->forBook($book);

        return $stats['this_month'] >= 10 || $stats['total'] >= 50;
    }

    /**
     * Get the human-readable purchase count label (e.g., "127 bought this month").
     */
    public function purchaseLabel(Book $book): ?string
    {
        $stats = $this->forBook($book);

        if ($stats['this_month'] > 0) {
            return $stats['this_month'] . ' bought this month';
        }

        if ($stats['total'] > 0) {
            return $stats['total'] . ' bought so far';
        }

        return null;
    }
}
