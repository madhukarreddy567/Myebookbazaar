<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Category;
use App\Services\PriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    /**
     * Catalogue: search, category filter, sort. Only active books.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $region = $this->prices->regionForVisitor($user);
        $priceColumn = $region === 'IN' ? 'price_inr' : 'price_usd';

        $books = Book::query()
            ->where('is_active', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('author', 'like', "%{$q}%")
                        ->orWhere('short_description', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($c) => $c->where('slug', $request->input('category')));
            })
            ->when($request->input('sort') === 'price_asc', fn ($query) => $query->orderBy($priceColumn))
            ->when($request->input('sort') === 'price_desc', fn ($query) => $query->orderByDesc($priceColumn))
            ->when(! in_array($request->input('sort'), ['price_asc', 'price_desc'], true),
                fn ($query) => $query->orderByDesc('published_at')->orderByDesc('id'))
            ->with('category')
            ->paginate(12)
            ->withQueryString();

        $books->getCollection()->transform(fn (Book $book) => $this->withPrice($book, $user));

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.books', compact('books', 'categories'));
    }

    /**
     * Book detail page.
     */
    public function show(string $slug): View
    {
        $user = auth()->user();

        $book = Book::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();

        $this->withPrice($book, $user);

        $related = Book::query()
            ->where('is_active', true)
            ->where('id', '!=', $book->id)
            ->when($book->category_id, fn ($q) => $q->where('category_id', $book->category_id))
            ->orderBy('sort_order')
            ->limit(4)
            ->get()
            ->map(fn (Book $b) => $this->withPrice($b, $user));

        return view('books.show', compact('book', 'related'));
    }

    /**
     * Buy now: add to cart and jump straight to the cart.
     * Login is still required before checkout (Phase 3).
     */
    public function buyNow(string $slug): RedirectResponse
    {
        $book = Book::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = auth()->user();

        if (! $user && ! settings_enabled('features.guest_cart', true)) {
            return redirect()->route('login')
                ->with('status', 'Please log in to buy this ebook.');
        }

        Cart::addBook($book, $user, session()->getId());

        return redirect()->route('cart.index')
            ->with('status', 'Added to your cart.');
    }

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
