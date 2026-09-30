<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Book extends Model
{
    /**
     * Default attribute values for new books.
     */
    protected $attributes = [
        'preview_enabled' => true,
        'preview_pages' => 5,
        'is_popular' => false,
        'popular_score' => 0,
    ];

    /**
     * Model-level safety net: a slug is always generated from the title
     * when none is supplied (unique). The admin panel generates slugs
     * itself; this covers any other creation path (seeders, API).
     */
    protected static function booted(): void
    {
        static::saving(function (Book $book) {
            if (trim((string) $book->slug) === '') {
                $book->slug = static::generateUniqueSlug((string) $book->title, $book->id);
            }
        });

        // Auto-generate SKU on creation when not supplied.
        static::created(function (Book $book) {
            if (! $book->sku) {
                app(\App\Services\SkuService::class)->ensureSku($book);
            }
        });
    }

    /**
     * Unique slug from a title, appending -2, -3, … on collision.
     * $ignoreId skips the record itself when regenerating on update.
     */
    public static function generateUniqueSlug(string $title, $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'sku',
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
        'original_filename',
        'isbn',
        'published_at',
        'is_active',
        'is_featured',
        'preview_enabled',
        'preview_pages',
        'is_popular',
        'popular_score',
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
            'preview_enabled' => 'boolean',
            'preview_pages' => 'integer',
            'is_popular' => 'boolean',
            'popular_score' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Attach the public rating summary (visible reviews only) as
     * `rating_average` (float|null) and `ratings_count` (int) attributes,
     * in a single query — no N+1 on book lists.
     */
    public function scopeWithRatingSummary(Builder $query): Builder
    {
        return $query
            ->withAvg(['ratings as rating_average' => fn (Builder $r) => $r->visible()], 'stars')
            ->withCount(['ratings as ratings_count' => fn (Builder $r) => $r->visible()]);
    }

    /**
     * Public cover URL (covers are the allowed public images).
     * Returns null when the book has no uploaded cover OR when the
     * file is missing from disk (e.g. wiped by a redeploy without a
     * persistent volume) — views then show the CSS placeholder or the
     * displayCoverUrl() fallback instead of a broken <img>.
     */
    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->cover_image)) {
            return null;
        }

        return asset('storage/'.$this->cover_image);
    }

    /**
     * Cover URL for customer-facing views: the uploaded cover wins;
     * the Fit at Home book falls back to the Stitch 3D mockup so the
     * page never shows a broken image or an empty placeholder.
     */
    public function displayCoverUrl(): ?string
    {
        return $this->coverUrl()
            ?: (str_contains($this->slug ?? '', 'fit-at-home') ? asset('images/stitch/book-mockup-3d.jpg') : null);
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

    /**
     * Filename served to the customer: the original upload name when known,
     * otherwise a slug of the title. Sanitized for Content-Disposition
     * (basename + no control characters).
     */
    public function downloadFilename(): string
    {
        $original = $this->original_filename ? basename((string) $this->original_filename) : '';
        $original = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $original));

        return $original !== '' ? $original : str($this->title)->slug().'.pdf';
    }
}
