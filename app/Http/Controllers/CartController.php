<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Services\CouponService;
use App\Services\PriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected PriceService $prices,
        protected CouponService $coupons,
    ) {}

    /**
     * Cart page. Login is required before checkout (Phase 3 builds it).
     */
    public function index(): View
    {
        $user = auth()->user();
        $items = Cart::forVisitor($user, session()->getId())->get();

        // Drop rows whose book went inactive/deleted since adding.
        $items = $items->filter(fn (Cart $row) => $row->book && $row->book->is_active);

        foreach ($items as $row) {
            $quote = $this->prices->getPrice($row->book, $user);
            $row->price_quote = $quote;
            $row->price_display = $this->prices->format($quote['amount'], $quote['currency']);
        }

        $summary = $this->prices->total(
            $items->map(fn (Cart $row) => $row->book),
            $user
        );

        // Phase 4: coupon state for the cart-level coupon form.
        $couponsEnabled = $this->coupons->enabled();
        $couponCode = $couponsEnabled ? session('coupon_code') : null;

        return view('cart.index', compact('items', 'summary', 'couponsEnabled', 'couponCode'));
    }

    /**
     * Add a book to the cart. Guests need the guest_cart switch on.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ]);

        $book = Book::query()->where('id', $data['book_id'])->where('is_active', true)->first();

        if (! $book) {
            return back()->withErrors(['book' => 'That ebook is not available right now.']);
        }

        $user = auth()->user();

        if (! $user && ! settings_enabled('features.guest_cart', true)) {
            return redirect()->route('login')
                ->with('status', 'Please log in to add ebooks to your cart.');
        }

        Cart::addBook($book, $user, session()->getId());

        return back()->with('status', 'Added to your cart.');
    }

    /**
     * Remove one line from the cart. Only the visitor's own rows.
     */
    public function destroy(Cart $cart): RedirectResponse
    {
        $this->ownsRow($cart);

        $cart->delete();

        return back()->with('status', 'Removed from your cart.');
    }

    /**
     * A customer may only touch their own cart rows (or their session's
     * rows as a guest). Anything else is a 404, not a hint.
     */
    protected function ownsRow(Cart $cart): void
    {
        $user = auth()->user();

        $owned = $user
            ? $cart->user_id === $user->id
            : $cart->user_id === null && $cart->session_id === session()->getId();

        abort_unless($owned, 404);
    }
}
