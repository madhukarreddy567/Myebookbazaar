@extends('layouts.app')

@section('title', 'Books — MyEbookBazaar')
@section('meta_description', 'Browse all ebooks on MyEbookBazaar. Fitness, diet, skills and money guides at honest prices.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <p class="st-crumb">
        <a href="{{ route('home') }}">Home</a><span class="sep">›</span>
        <strong class="text-[#101E28]">Books</strong>
    </p>

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">Book Catalog</h1>
    <p class="st-muted mt-1.5 max-w-2xl text-[15px]">Short, practical ebooks. Search, filter and sort your way.</p>

    {{-- Search + sort --}}
    <form method="GET" action="{{ route('books') }}" class="mt-6 st-card p-4">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="st-input-wrap flex-1">
                <x-ui.icon name="search" class="st-input-icon" />
                <label for="f-search" class="sr-only">Search ebooks</label>
                <input id="f-search" name="q" type="search" value="{{ request('q') }}" placeholder="Search ebooks…"
                       class="st-input has-icon !min-h-[48px]" autocomplete="off">
            </div>
            <div class="md:w-52">
                <label for="f-sort" class="sr-only">Sort by</label>
                <select id="f-sort" name="sort" class="st-input !min-h-[48px]" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                </select>
            </div>
        </div>
        {{-- Category chips --}}
        <div class="chip-scroll mt-3" role="list" aria-label="Filter by category">
            <a href="{{ route('books', array_filter(['q' => request('q'), 'sort' => request('sort')])) }}"
               class="chip @if(!request('category')) chip-active @endif" role="listitem"
               @if(!request('category')) aria-current="true" @endif>All</a>
            @foreach($categories as $cat)
            <a href="{{ route('books', array_filter(['category' => $cat->slug, 'q' => request('q'), 'sort' => request('sort')])) }}"
               class="chip @if(request('category') === $cat->slug) chip-active @endif" role="listitem"
               @if(request('category') === $cat->slug) aria-current="true" @endif>{{ $cat->name }}</a>
            @endforeach
        </div>
        @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
    </form>

    {{-- Results --}}
    @if($books->isNotEmpty())
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 mt-6">
        @foreach($books as $book)
        @php
            $cover = $book->coverUrl() ?: (str_contains($book->slug, 'fit-at-home') ? asset('images/stitch/book-mockup-3d.jpg') : null);
            $onSale = $book->price_quote['on_sale'] ?? false;
        @endphp
        <article class="st-book-card">
            <a href="{{ route('books.show', $book->slug) }}" class="st-book-cover block" tabindex="-1" aria-hidden="true">
                @if($cover)
                    <img src="{{ $cover }}" alt="" loading="lazy">
                @endif
                @if($onSale)
                    <span class="st-badge-sale">Sale</span>
                @else
                    <span class="st-badge-free">Instant PDF</span>
                @endif
            </a>
            <div class="st-book-body">
                @if($book->category)
                <p class="st-book-cat">{{ $book->category->name }}</p>
                @endif
                <h2 class="st-book-title line-clamp-2">
                    <a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a>
                </h2>
                @if($book->author)
                <p class="st-book-meta">by {{ $book->author }}</p>
                @endif
                <p class="st-book-meta">
                    @if($book->pages){{ $book->pages }} pages · @endif PDF ebook
                </p>
                <p class="st-book-price">
                    <span class="now">{{ $book->price_display }}</span>
                    @if($book->price_regular_display)
                    <span class="book-card-oldprice">{{ $book->price_regular_display }}</span>
                    @endif
                </p>
                <p class="mt-1.5"><x-ui.rating-stars :average="$book->rating_average ?? null" :count="$book->ratings_count ?? 0" star-class="w-3.5 h-3.5" /></p>
                <form method="POST" action="{{ route('cart.store') }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <button type="submit" class="st-add-cart w-full">
                        <x-ui.icon name="cart" class="w-4 h-4" /> Add to Cart
                    </button>
                </form>
            </div>
        </article>
        @endforeach
    </div>
    <div class="mt-8">
        {{ $books->links() }}
    </div>
    @else
    <div class="st-card st-empty mt-6">
        <x-ui.empty-state icon="book" title="No books found"
            text="Try a different search or category."
            :action-url="route('books')" action-label="Clear filters" />
    </div>
    @endif
</div>
@endsection
