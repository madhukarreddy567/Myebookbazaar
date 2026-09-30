<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    /** Append-only: no updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'order_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Successfully SERVED downloads for one book inside one order.
     * Blocked attempts (over the limit, missing file) are logged with
     * downloaded_at = NULL and never count against the limit.
     */
    public static function usedCount(int $orderId, int $bookId): int
    {
        return self::query()
            ->where('order_id', $orderId)
            ->where('book_id', $bookId)
            ->whereNotNull('downloaded_at')
            ->count();
    }

    /**
     * Every logged attempt for one book inside one order, served or not.
     */
    public static function attemptsCount(int $orderId, int $bookId): int
    {
        return self::query()
            ->where('order_id', $orderId)
            ->where('book_id', $bookId)
            ->count();
    }
}
