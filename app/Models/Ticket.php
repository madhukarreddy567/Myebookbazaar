<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer support ticket.
 *
 * Status flow:
 *   open -> awaiting_customer (admin replied, ball is with the customer)
 *        -> awaiting_support (customer replied, ball is with support)
 *        -> closed (admin closed it; a new customer reply reopens it to
 *           awaiting_support).
 */
class Ticket extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_AWAITING_CUSTOMER = 'awaiting_customer';
    public const STATUS_AWAITING_SUPPORT = 'awaiting_support';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_AWAITING_CUSTOMER,
        self::STATUS_AWAITING_SUPPORT,
        self::STATUS_CLOSED,
    ];

    public const CATEGORIES = [
        'order' => 'Order & payment',
        'download' => 'Download problem',
        'refund' => 'Refund request',
        'account' => 'Account & sign-in',
        'other' => 'Something else',
    ];

    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'order_id',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_AWAITING_CUSTOMER => 'Awaiting you',
            self::STATUS_AWAITING_SUPPORT => 'Awaiting support',
            self::STATUS_CLOSED => 'Closed',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }
}
