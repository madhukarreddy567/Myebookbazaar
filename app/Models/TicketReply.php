<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message inside a support ticket thread. Append-only: replies are
 * never edited or deleted, so the thread doubles as the audit trail.
 */
class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_admin',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Display name for the thread: "Support" for admin replies, the
     * customer's name otherwise.
     */
    public function authorName(): string
    {
        if ($this->is_admin) {
            return 'Support';
        }

        return $this->user?->name ?? 'Customer';
    }
}
