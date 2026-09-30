<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Rating;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Customer ratings — verified purchases only.
 *
 * Eligibility is enforced twice, never from the client:
 *   1. RatingPolicy::create (paid-order ownership + one rating per book)
 *   2. the unique(user_id, book_id) DB constraint as a final backstop.
 */
class RatingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Submit a first rating for a book.
     */
    public function store(Request $request, Book $book): RedirectResponse
    {
        $this->authorize('create', [Rating::class, $book]);

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            Rating::create([
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
                'stars' => $validated['stars'],
                'review' => $validated['review'] ?? null,
            ]);
        } catch (QueryException $e) {
            // Race backstop: the unique(user_id, book_id) constraint fired
            // because two requests slipped past the policy together.
            if ($e->getCode() === '23000') {
                return redirect()
                    ->route('books.show', $book->slug)
                    ->withErrors(['stars' => 'You have already reviewed this book — edit your existing review instead.']);
            }

            throw $e;
        }

        return redirect()
            ->route('books.show', $book->slug)
            ->with('status', 'Thanks! Your review has been posted.');
    }

    /**
     * Edit the customer's own rating.
     */
    public function update(Request $request, Book $book, Rating $rating): RedirectResponse
    {
        // The rating must belong to the book in the URL — no cross-book
        // edits by guessing IDs.
        abort_if($rating->book_id !== $book->id, 404);

        $this->authorize('update', $rating);

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        $rating->update([
            'stars' => $validated['stars'],
            'review' => $validated['review'] ?? null,
        ]);

        return redirect()
            ->route('books.show', $book->slug)
            ->with('status', 'Your review has been updated.');
    }
}
