<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin review moderation — hide/unhide or delete abusive reviews.
 * Every action is audit-logged. Ratings are never created or faked here.
 */
class RatingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Rating::with(['user', 'book'])->latest();

        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], '', (string) $request->string('q')).'%';
            $query->where(function ($w) use ($q) {
                $w->where('review', 'like', $q)
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', $q)
                        ->orWhere('email', 'like', $q))
                    ->orWhereHas('book', fn ($b) => $b->where('title', 'like', $q));
            });
        }

        if ($request->filled('visibility') && in_array((string) $request->string('visibility'), ['visible', 'hidden'], true)) {
            $query->where('is_visible', (string) $request->string('visibility') === 'visible');
        }

        if ($request->filled('stars') && in_array((int) $request->input('stars'), [1, 2, 3, 4, 5], true)) {
            $query->where('stars', (int) $request->input('stars'));
        }

        $ratings = $query->paginate(15)->withQueryString();

        return view('admin.reviews.index', [
            'ratings' => $ratings,
            'filters' => $request->only(['q', 'visibility', 'stars']),
            'totalCount' => Rating::count(),
            'hiddenCount' => Rating::where('is_visible', false)->count(),
        ]);
    }

    /**
     * Hide (or unhide) a review. Hidden reviews stay in the database but
     * are excluded from every store display and average.
     */
    public function toggle(Rating $rating): RedirectResponse
    {
        $old = ['is_visible' => $rating->is_visible];
        $rating->update(['is_visible' => ! $rating->is_visible]);

        Audit::record(
            $rating->is_visible ? 'ratings.unhidden' : 'ratings.hidden',
            'rating',
            $rating->id,
            $old,
            ['is_visible' => $rating->is_visible, 'book_id' => $rating->book_id, 'user_id' => $rating->user_id]
        );

        return back()->with('status', $rating->is_visible
            ? 'Review is visible on the store again.'
            : 'Review hidden from the store.');
    }

    /**
     * Permanently delete a review (abuse). Audit-logged with its content
     * snapshot before deletion.
     */
    public function destroy(Rating $rating): RedirectResponse
    {
        Audit::record('ratings.deleted', 'rating', $rating->id, [
            'book_id' => $rating->book_id,
            'user_id' => $rating->user_id,
            'stars' => $rating->stars,
            'review' => mb_substr((string) $rating->review, 0, 500),
            'is_visible' => $rating->is_visible,
        ]);

        $rating->delete();

        return back()->with('status', 'Review deleted.');
    }
}
