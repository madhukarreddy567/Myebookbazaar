<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Services\PriceService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Phase 4: public combo (bundle) pages.
 *
 * - /combos lists active combos when the feature switch is on.
 * - Buying a combo stores it in the session and hands off to the
 *   normal checkout pipeline (one order, one item per book).
 * - Owning EVERY book in the combo blocks the purchase with a clear
 *   message naming the owned books. Owning SOME books only shows a
 *   "You already own X" warning and the purchase is allowed.
 */
class ComboController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    public function index(): View
    {
        $user = auth()->user();

        $combos = Combo::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Combo $combo) => $this->withPrice($combo, $user));

        return view('combos.index', compact('combos'));
    }

    public function show(string $slug): View
    {
        $user = auth()->user();

        $combo = Combo::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $combo = $this->withPrice($combo, $user);

        // Books the visitor already owns (for the "you already own X"
        // warning) and whether they own the whole combo (blocked).
        $ownedBooks = $user ? $combo->booksOwnedBy($user) : collect();
        $ownsAll = $user ? $combo->userOwnsAllBooks($user) : false;

        return view('combos.show', compact('combo', 'ownedBooks', 'ownsAll'))
            ->with('seo', app(SeoService::class));
    }

    /**
     * Start a combo purchase. Login is required; the combo id is kept
     * in the session and checkout builds the order from it.
     * Owning ALL books blocks the purchase; owning SOME only warns.
     */
    public function buy(string $slug): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login')
                ->with('status', 'Please log in to buy this combo.');
        }

        $combo = Combo::query()->where('slug', $slug)->where('is_active', true)->first();

        if (! $combo || $combo->books()->isEmpty()) {
            return back()->withErrors(['combo' => 'This combo is not available right now.']);
        }

        $owned = $combo->booksOwnedBy($user);

        if ($owned->isNotEmpty() && $combo->userOwnsAllBooks($user)) {
            $titles = $this->ownedTitles($owned);

            return redirect()->route('library')->withErrors([
                'combo' => 'You already own '.$titles.' from this combo, so it cannot be bought again. Your ebooks are in your library below.',
            ]);
        }

        session(['checkout_combo_id' => $combo->id]);

        if ($owned->isNotEmpty()) {
            $titles = $this->ownedTitles($owned);

            return redirect()->route('checkout')->with(
                'combo_warning',
                'You already own '.$titles.' from this combo. You can still buy the full combo at the listed price.'
            );
        }

        return redirect()->route('checkout');
    }

    protected function ownedTitles($owned): string
    {
        return $owned->pluck('title')->map(fn ($t) => '“'.$t.'”')->join(', ');
    }

    protected function withPrice(Combo $combo, $user): Combo
    {
        $quote = $this->prices->getComboPrice($combo, $user);

        $combo->price_quote = $quote;
        $combo->price_display = $this->prices->format($quote['amount'], $quote['currency']);
        $combo->price_regular_display = $quote['regular_amount'] !== null
            ? $this->prices->format($quote['regular_amount'], $quote['currency'])
            : null;
        $combo->combo_books = $combo->books();

        return $combo;
    }
}
