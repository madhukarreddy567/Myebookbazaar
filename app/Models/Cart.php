<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    /** No updated_at: carts are append/remove only. */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'book_id',
        'quantity',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cart rows for the current visitor: their user rows when logged in,
     * otherwise the guest rows for this session.
     */
    public static function forVisitor(?User $user, string $sessionId)
    {
        $query = static::query()->with('book');

        if ($user) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereNull('user_id')->where('session_id', $sessionId);
    }

    /**
     * Add a book to the visitor's cart. Digital goods: one row per
     * user/session + book, quantity always 1.
     */
    public static function addBook(Book $book, ?User $user, string $sessionId): self
    {
        $attributes = $user
            ? ['user_id' => $user->id, 'book_id' => $book->id]
            : ['user_id' => null, 'session_id' => $sessionId, 'book_id' => $book->id];

        $row = static::query()->firstOrNew($attributes);
        $row->quantity = 1;
        if ($user) {
            $row->session_id = null;
        } else {
            $row->session_id = $sessionId;
        }
        $row->added_at = $row->added_at ?? now();
        $row->save();

        return $row;
    }

    /**
     * Move a guest session's cart rows onto the user at login.
     * Books already in the user's cart are not duplicated.
     */
    public static function mergeGuestToUser(string $guestSessionId, User $user): void
    {
        DB::transaction(function () use ($guestSessionId, $user) {
            $guestRows = static::query()
                ->whereNull('user_id')
                ->where('session_id', $guestSessionId)
                ->get();

            $ownedBookIds = static::query()
                ->where('user_id', $user->id)
                ->pluck('book_id')
                ->all();

            foreach ($guestRows as $row) {
                if (in_array($row->book_id, $ownedBookIds, true)) {
                    $row->delete();
                    continue;
                }

                $row->user_id = $user->id;
                $row->session_id = null;
                $row->save();
                $ownedBookIds[] = $row->book_id;
            }
        });
    }
}
