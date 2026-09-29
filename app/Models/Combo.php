<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Combo extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'book_ids',
        'combo_price_inr',
        'combo_price_usd',
        'sale_price_inr',
        'sale_price_usd',
        'sale_starts_at',
        'sale_ends_at',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'book_ids' => 'array',
            'combo_price_inr' => 'decimal:2',
            'combo_price_usd' => 'decimal:2',
            'sale_price_inr' => 'decimal:2',
            'sale_price_usd' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The active books in this combo, in the admin's chosen order.
     */
    public function books(): Collection
    {
        $ids = $this->book_ids ?? [];

        if (empty($ids)) {
            return collect();
        }

        $books = Book::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $books->get($id))->filter();
    }

    /**
     * True when the customer already bought ANY book in this combo.
     * Kept for the "you already own X" warning; it no longer blocks.
     */
    public function userOwnsAnyBook(User $user): bool
    {
        $ids = $this->book_ids ?? [];

        if (empty($ids)) {
            return false;
        }

        return OrderItem::query()
            ->whereIn('book_id', $ids)
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', Order::STATUS_PAID))
            ->exists();
    }

    /**
     * True only when the customer already owns EVERY book in the
     * combo — the only case that blocks buying it again.
     */
    public function userOwnsAllBooks(User $user): bool
    {
        $ids = $this->book_ids ?? [];

        if (empty($ids)) {
            return false;
        }

        $owned = OrderItem::query()
            ->whereIn('book_id', $ids)
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', Order::STATUS_PAID))
            ->distinct()
            ->count('book_id');

        return $owned >= count($ids);
    }

    /**
     * Which of the combo's books the customer already owns (for the
     * "you already own X" message).
     */
    public function booksOwnedBy(User $user): Collection
    {
        $ids = $this->book_ids ?? [];

        if (empty($ids)) {
            return collect();
        }

        $ownedIds = OrderItem::query()
            ->whereIn('book_id', $ids)
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', Order::STATUS_PAID))
            ->distinct()
            ->pluck('book_id')
            ->all();

        return Book::query()->whereIn('id', $ownedIds)->get();
    }
}
