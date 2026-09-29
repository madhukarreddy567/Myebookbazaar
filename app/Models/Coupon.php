<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FLAT = 'flat';

    public const CURRENCY_INR = 'INR';
    public const CURRENCY_USD = 'USD';
    public const CURRENCY_BOTH = 'BOTH';

    public const APPLIES_ALL = 'all';
    public const APPLIES_BOOKS = 'books';
    public const APPLIES_CATEGORIES = 'categories';

    protected $fillable = [
        'code',
        'type',
        'value',
        'currency',
        'min_order_amount',
        'max_uses',
        'uses_count',
        'max_uses_per_user',
        'starts_at',
        'ends_at',
        'is_active',
        'applies_to',
        'applies_book_ids',
        'applies_category_ids',
        'first_order_only',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'applies_book_ids' => 'array',
            'applies_category_ids' => 'array',
            'first_order_only' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * True while the coupon is switched on and inside its date window.
     */
    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * True when this coupon may discount the given book:
     * - all: every book
     * - books: only the chosen book ids
     * - categories: only books in the chosen categories
     */
    public function appliesToBook(Book $book): bool
    {
        if ($this->applies_to === self::APPLIES_BOOKS) {
            return in_array($book->id, $this->applies_book_ids ?? [], true);
        }

        if ($this->applies_to === self::APPLIES_CATEGORIES) {
            return $book->category_id !== null
                && in_array($book->category_id, $this->applies_category_ids ?? [], true);
        }

        return true;
    }
}
