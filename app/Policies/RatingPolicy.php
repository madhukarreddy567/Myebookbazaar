<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Rating;
use App\Models\User;

/**
 * Verified-purchase-only rating rules.
 *
 * The client is never trusted: both the controller and these policy
 * methods re-derive eligibility from paid orders on the server.
 */
class RatingPolicy
{
    /**
     * A customer may create a rating only when they own the book through
     * a PAID order — and only once per book.
     */
    public function create(User $user, Book $book): bool
    {
        if (! $user->ownsBook($book)) {
            return false;
        }

        return ! Rating::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();
    }

    /**
     * Only the rating's own author may edit it.
     */
    public function update(User $user, Rating $rating): bool
    {
        return $rating->user_id === $user->id;
    }
}
