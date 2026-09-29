<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Combo;
use App\Services\PriceService;
use Illuminate\View\View;

/**
 * Phase 4: public offers page — every book (and combo) with an active
 * timed sale price. When the sales switch is off, sale prices are
 * ignored everywhere, so this page is empty by construction.
 */
class OfferController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    public function index(): View
    {
        $user = auth()->user();

        $books = Book::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('sale_price_inr')->orWhereNotNull('sale_price_usd');
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Book $book) => $this->prices->saleIsActive($book)
                && $this->prices->getPrice($book, $user)['on_sale'])
            ->map(function (Book $book) use ($user) {
                $quote = $this->prices->getPrice($book, $user);
                $book->price_quote = $quote;
                $book->price_display = $this->prices->format($quote['amount'], $quote['currency']);
                $book->price_regular_display = $this->prices->format($quote['regular_amount'], $quote['currency']);

                return $book;
            })
            ->values();

        $combos = Combo::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('sale_price_inr')->orWhereNotNull('sale_price_usd');
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Combo $combo) => $this->prices->comboSaleIsActive($combo)
                && $this->prices->getComboPrice($combo, $user)['on_sale'])
            ->map(function (Combo $combo) use ($user) {
                $quote = $this->prices->getComboPrice($combo, $user);
                $combo->price_quote = $quote;
                $combo->price_display = $this->prices->format($quote['amount'], $quote['currency']);
                $combo->price_regular_display = $quote['regular_amount'] !== null
                    ? $this->prices->format($quote['regular_amount'], $quote['currency'])
                    : null;
                $combo->combo_books = $combo->books();

                return $combo;
            })
            ->values();

        return view('offers.index', compact('books', 'combos'));
    }
}
