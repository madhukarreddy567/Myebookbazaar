<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A verified-purchase book rating.
 *
 * Eligibility (the user owns the book through a PAID order) is enforced
 * server-side by RatingPolicy + RatingController. Display code only ever
 * reads the visible() scope — hidden (abusive) reviews are excluded from
 * averages and counts everywhere on the store.
 */
class Rating extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'stars',
        'review',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Reviews the public store may display (hidden ones are excluded).
     *
     * @param  Builder<Rating>  $query
     * @return Builder<Rating>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
