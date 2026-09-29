<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Combo;
use App\Services\PriceService;
use Illuminate\View\View;

/**
 * Phase 4: read-only offers overview — every book and combo with a
 * sale price configured, and whether its sale is currently live.
 * (Sale prices are edited on the book/combo forms.)
 */
class OfferController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    public function index(): View
    {
        $books = Book::query()
            ->where(function ($q) {
                $q->whereNotNull('sale_price_inr')->orWhereNotNull('sale_price_usd');
            })
            ->orderBy('title')
            ->get()
            ->map(function (Book $book) {
                $book->sale_live = $this->prices->saleIsActive($book)
                    && ($book->sale_price_inr !== null || $book->sale_price_usd !== null);

                return $book;
            });

        $combos = Combo::query()
            ->where(function ($q) {
                $q->whereNotNull('sale_price_inr')->orWhereNotNull('sale_price_usd');
            })
            ->orderBy('title')
            ->get()
            ->map(function (Combo $combo) {
                $combo->sale_live = $this->prices->comboSaleIsActive($combo);

                return $combo;
            });

        $switchOn = settings_enabled('features.sales_offers', true);

        return view('admin.offers.index', compact('books', 'combos', 'switchOn'));
    }
}
