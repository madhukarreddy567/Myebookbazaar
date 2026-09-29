<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Book;
use App\Models\Category;
use App\Models\Combo;
use App\Services\PriceService;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    /**
     * Homepage: hero, featured books, categories, banners.
     */
    public function index(): View
    {
        $user = auth()->user();

        $featured = Book::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (Book $book) => $this->withPrice($book, $user));

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Phase 4: combos section (top 3 active combos, when the switch is on).
        $combos = collect();
        if (settings_enabled('features.combos', false)) {
            $combos = Combo::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(3)
                ->get()
                ->map(function (Combo $combo) use ($user) {
                    $quote = $this->prices->getComboPrice($combo, $user);
                    $combo->price_quote = $quote;
                    $combo->price_display = $this->prices->format($quote['amount'], $quote['currency']);
                    $combo->price_regular_display = $quote['regular_amount'] !== null
                        ? $this->prices->format($quote['regular_amount'], $quote['currency'])
                        : null;

                    return $combo;
                });
        }

        // Phase 4: offers strip — books with a live sale price (when the switch is on).
        $offers = collect();
        if (settings_enabled('features.sales_offers', true)) {
            $offers = Book::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNotNull('sale_price_inr')->orWhereNotNull('sale_price_usd');
                })
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->filter(fn (Book $book) => $this->prices->saleIsActive($book)
                    && $this->prices->getPrice($book, $user)['on_sale'])
                ->map(fn (Book $book) => $this->withPrice($book, $user))
                ->values();
        }

        return view('home', compact('featured', 'categories', 'banners', 'combos', 'offers'));
    }

    /**
     * Attach the visitor-correct price quote to a book for the view.
     */
    protected function withPrice(Book $book, $user): Book
    {
        $book->price_quote = $this->prices->getPrice($book, $user);
        $book->price_display = $this->prices->format(
            $book->price_quote['amount'],
            $book->price_quote['currency']
        );
        $book->price_regular_display = $book->price_quote['regular_amount'] !== null
            ? $this->prices->format($book->price_quote['regular_amount'], $book->price_quote['currency'])
            : null;

        return $book;
    }
}
