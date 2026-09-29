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
     * Downloads used for one book inside one order.
     */
    public static function usedCount(int $orderId, int $bookId): int
    {
        return self::query()
            ->where('order_id', $orderId)
            ->where('book_id', $bookId)
            ->count();
    }
}
