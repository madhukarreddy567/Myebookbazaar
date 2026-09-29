<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'subtitle',
        'description',
        'short_description',
        'author',
        'edition',
        'pages',
        'language',
        'cover_image',
        'price_inr',
        'price_usd',
        'sale_price_inr',
        'sale_price_usd',
        'sale_starts_at',
        'sale_ends_at',
        'file_path',
        'file_size',
        'isbn',
        'published_at',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_inr' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'sale_price_inr' => 'decimal:2',
            'sale_price_usd' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'published_at' => 'date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Public cover URL (covers are the allowed public images).
     * Returns null when the book has no uploaded cover — views then show
     * the CSS placeholder.
     */
    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return asset('storage/'.$this->cover_image);
    }

    /**
     * True when a PDF has been uploaded to private storage.
     * The file is NEVER reachable by a public URL (Phase 3 serves it
     * through signed, expiring download links).
     */
    public function hasFile(): bool
    {
        return $this->file_path !== null && $this->file_path !== '';
    }
}
