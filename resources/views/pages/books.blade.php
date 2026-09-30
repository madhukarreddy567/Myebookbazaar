@extends('layouts.stitch')

@php
    $searchQuery = trim((string) request('q', ''));
    $isSearching = $searchQuery !== '';
@endphp

@section('title', $isSearching ? "Search results for '{$searchQuery}' — MyEbookBazaar" : 'Books — MyEbookBazaar')
@section('meta_description', 'Browse all ebooks on MyEbookBazaar. Fitness, diet, skills and money guides at honest prices. Instant PDF download.')

@section('content')
<div class="st-page w-full bg-surface">
<div class="max-w-[1120px] mx-auto px-4 sm:px-6 py-6">
<div class="flex flex-col w-full">

<!-- Ambient backdrop accents -->
<div class="relative w-full">
    <div class="absolute -top-10 right-0 w-80 h-80 rounded-full bg-secondary-container/40 blur-3xl pointer-events-none -z-10"></div>
    <div class="absolute top-48 left-12 w-64 h-64 rounded-full bg-primary-fixed/30 blur-2xl pointer-events-none -z-10"></div>

    @if($books->isNotEmpty())
    <!-- Editorial header -->
    <header class="pt-2 pb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-6 border-b border-surface-container-high/60">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-primary-container inline-block"></span>
                <span class="font-label-sm text-label-sm text-secondary tracking-widest uppercase">Verified Digital Library</span>
            </div>
            @if($isSearching)
            <p class="font-label-md text-label-md text-secondary mb-1">
                <a href="{{ route('home') }}" class="hover:text-on-surface transition-colors">Home</a>
                <span class="text-outline-variant mx-1">/</span>
                <a href="{{ route('books') }}" class="hover:text-on-surface transition-colors">Catalog</a>
                <span class="text-outline-variant mx-1">/</span>
                <span class="text-on-surface font-semibold">Search results</span>
            </p>
            <h1 class="font-headline-lg text-headline-lg sm:font-display-lg sm:text-display-lg text-on-surface tracking-tight font-bold">
                Results for &ldquo;{{ $searchQuery }}&rdquo;
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mt-2">
                {{ $books->total() }} {{ \Illuminate\Support\Str::plural('match', $books->total()) }} found. Practical digital guides with instant PDF access.
            </p>
            @else
            <h1 class="font-headline-lg text-headline-lg sm:font-display-lg sm:text-display-lg text-on-surface tracking-tight font-bold">
                All Ebooks
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mt-2">
                Practical digital guides written for everyday Indian lifestyle.
            </p>
            @endif
        </div>
        <!-- Shelf count pill (real paginator total) -->
        <div class="flex items-center gap-4 bg-surface-container-lowest p-2 pr-5 rounded-full border border-surface-container-high shadow-sm self-start sm:self-auto">
            <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[20px]">menu_book</span>
            </div>
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant leading-none">Curated Shelf</span>
                <span class="font-headline-sm text-headline-sm text-on-surface font-bold leading-tight">{{ $books->total() }} {{ \Illuminate\Support\Str::plural('Title', $books->total()) }}</span>
            </div>
        </div>
    </header>

    <!-- Filter + search row (GET to the catalog route) -->
    <section class="mt-8 mb-10" aria-label="Search and filter">
        <form method="GET" action="{{ route('books') }}" id="catalog-filter">
            @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <div class="bg-surface-container-lowest p-4 rounded-xl border border-surface-container-high shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                <div class="relative flex-1 min-w-[260px]">
                    <span class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-secondary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <label for="catalog-search" class="sr-only">Search ebooks</label>
                    <input id="catalog-search" name="q" type="search" value="{{ request('q') }}" placeholder="Search by title or topic..."
                           class="w-full h-12 pl-11 pr-4 bg-surface rounded-DEFAULT border border-surface-container-high font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all"
                           autocomplete="off">
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative min-w-[160px] flex-1 sm:flex-none">
                        <label for="catalog-sort" class="sr-only">Sort books</label>
                        <select id="catalog-sort" name="sort" aria-label="Sort books" onchange="document.getElementById('catalog-filter').submit()"
                                class="w-full appearance-none h-12 pl-4 pr-10 bg-surface rounded-DEFAULT border border-surface-container-high font-body-sm text-body-sm text-on-surface font-medium cursor-pointer focus:outline-none focus:border-primary-container transition-all">
                            <option value="newest" @selected(request('sort', 'newest') === 'newest')>Sort: Newest</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                        </select>
                        <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-secondary">
                            <span class="material-symbols-outlined text-[20px] block">swap_vert</span>
                        </span>
                    </div>
                    <button type="submit" class="h-12 px-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99]">
                        Search
                    </button>
                </div>
            </div>
            <!-- Category chips (real categories only) -->
            @if($categories->isNotEmpty())
            <div class="flex flex-wrap gap-2.5 mt-4" role="list" aria-label="Filter by category">
                <a href="{{ route('books', array_filter(['q' => request('q'), 'sort' => request('sort')])) }}"
                   role="listitem"
                   class="h-9 px-4 rounded-full font-label-md text-label-md font-semibold transition-all active:scale-95 flex items-center {{ !request('category') ? 'bg-inverse-surface text-inverse-on-surface shadow-sm' : 'bg-surface-container-lowest text-secondary shadow-sm hover:bg-surface-container' }}"
                   @if(!request('category')) aria-current="true" @endif>All</a>
                @foreach($categories as $cat)
                <a href="{{ route('books', array_filter(['category' => $cat->slug, 'q' => request('q'), 'sort' => request('sort')])) }}"
                   role="listitem"
                   class="h-9 px-4 rounded-full font-label-md text-label-md font-semibold transition-all active:scale-95 flex items-center {{ request('category') === $cat->slug ? 'bg-inverse-surface text-inverse-on-surface shadow-sm' : 'bg-surface-container-lowest text-secondary shadow-sm hover:bg-surface-container' }}"
                   @if(request('category') === $cat->slug) aria-current="true" @endif>{{ $cat->name }}</a>
                @endforeach
            </div>
            @endif
        </form>
    </section>

    <!-- Book grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($books as $book)
        @php
            $cover = $book->displayCoverUrl();
            $quote = $book->price_quote ?? [];
            $onSale = $quote['on_sale'] ?? false;
            $discountPct = $quote['discount_percent'] ?? null;
        @endphp
        <article class="group bg-surface-container-lowest rounded-DEFAULT border border-surface-container-high flex flex-col justify-between overflow-hidden hover:border-primary-container/40 transition-all duration-300">
            <div>
                <div class="relative aspect-[3/4] w-full overflow-hidden bg-surface-container p-5 flex items-center justify-center">
                    <a href="{{ route('books.show', $book->slug) }}" class="relative w-full h-full rounded-sm overflow-hidden border border-outline-variant/30 shadow-sm transition-transform duration-300 group-hover:scale-[1.02] flex items-center justify-center bg-surface-container-lowest" tabindex="-1" aria-hidden="true">
                        @if($cover)
                        <img src="{{ $cover }}" alt="" class="w-full h-full object-cover" loading="lazy">
                        @else
                        <span class="text-secondary font-headline-sm text-headline-sm font-bold text-center px-6">{{ $book->title }}</span>
                        @endif
                    </a>
                    @if($book->is_popular)
                    <span class="absolute top-4 left-4 bg-primary-container/90 text-on-primary font-label-sm text-[11px] px-2.5 py-1 rounded-full font-semibold shadow-sm">
                        Bestseller
                    </span>
                    @endif
                    <span class="absolute bottom-4 right-4 bg-surface-container-lowest/90 text-secondary font-label-sm text-label-sm px-2.5 py-0.5 rounded-full border border-surface-container-high">
                        Instant Download
                    </span>
                </div>
                <div class="p-6 pb-2">
                    <div class="flex items-center gap-2 mb-2 font-label-sm text-label-sm text-primary font-semibold">
                        @if($book->category)
                        <span>{{ $book->category->name }}</span>
                        <span aria-hidden="true">•</span>
                        @endif
                        <span class="text-on-surface-variant font-normal">
                            @if($book->pages){{ $book->pages }} Pages @else PDF Guide @endif
                        </span>
                    </div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold leading-snug line-clamp-1 group-hover:text-primary transition-colors">
                        <a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a>
                    </h2>
                    @if($book->short_description)
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 line-clamp-2">
                        {{ $book->short_description }}
                    </p>
                    @endif
                    @if(($book->ratings_count ?? 0) > 0)
                    <p class="mt-2"><x-ui.rating-stars :average="$book->rating_average" :count="$book->ratings_count" star-class="w-3.5 h-3.5" /></p>
                    @endif
                </div>
            </div>
            <div class="p-6 pt-3 mt-auto">
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="font-headline-md text-headline-md text-on-surface font-bold">{{ $book->price_display }}</span>
                    @if($book->price_regular_display)
                    <span class="font-body-sm text-body-sm text-on-surface-variant/60 line-through book-card-oldprice">{{ $book->price_regular_display }}</span>
                    @endif
                    @if($onSale && $discountPct)
                    <span class="font-label-sm text-label-sm text-primary font-semibold bg-primary-fixed/40 px-2 py-0.5 rounded-full ml-auto">{{ $discountPct }}% OFF</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('cart.store') }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <button type="submit" class="w-full h-11 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold flex items-center justify-center gap-2 active:scale-[0.99] transition-all">
                        <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                        Add to cart
                    </button>
                </form>
            </div>
        </article>
        @endforeach
    </div>

    <!-- Trust strip -->
    <section class="mt-14 mb-10 p-6 rounded-DEFAULT bg-surface-container-low border border-surface-container-high/80 grid grid-cols-1 md:grid-cols-3 gap-6" aria-label="Our promises">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-surface-container-lowest flex items-center justify-center text-primary shadow-sm shrink-0">
                <span class="material-symbols-outlined text-[20px]">bolt</span>
            </div>
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Instant PDF Delivery</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Download right after payment, plus a backup link by email.</p>
            </div>
        </div>
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-surface-container-lowest flex items-center justify-center text-primary shadow-sm shrink-0">
                <span class="material-symbols-outlined text-[20px]">devices</span>
            </div>
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Read Anywhere</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Phone, tablet, computer or Kindle — no special app needed.</p>
            </div>
        </div>
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-surface-container-lowest flex items-center justify-center text-primary shadow-sm shrink-0">
                <span class="material-symbols-outlined text-[20px]">verified_user</span>
            </div>
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Secure Checkout</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">UPI, cards and netbanking through Razorpay.</p>
            </div>
        </div>
    </section>

    <!-- Pagination (Stitch-styled, real paginator data) -->
    @if($books->hasPages())
    @php
        $current = $books->currentPage();
        $last = $books->lastPage();
        $window = 2;
        $start = max(1, min($current - $window, $last - 4));
        $end = min($last, max($current + $window, 5));
        if ($last <= 5) { $start = 1; $end = $last; }
    @endphp
    <nav aria-label="Book catalog pagination" class="pt-4 pb-12 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-surface-container-high">
        <span class="font-body-sm text-body-sm text-on-surface-variant">
            Page <strong class="text-on-surface font-semibold">{{ $current }}</strong> of <strong class="text-on-surface font-semibold">{{ $last }}</strong> ({{ $books->total() }} {{ \Illuminate\Support\Str::plural('title', $books->total()) }})
        </span>
        <div class="flex items-center gap-3">
            @if($books->onFirstPage())
            <span aria-hidden="true" class="h-10 px-5 rounded-full border border-surface-container-high bg-surface-container-low text-on-surface-variant/40 font-label-md text-label-md font-medium cursor-not-allowed flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                Previous
            </span>
            @else
            <a aria-label="Previous page" href="{{ $books->previousPageUrl() }}" class="h-10 px-5 rounded-full border border-secondary text-secondary hover:bg-secondary hover:text-on-secondary font-label-md text-label-md font-medium transition-all active:scale-[0.98] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                Previous
            </a>
            @endif
            <div class="flex items-center gap-1">
                @for($p = $start; $p <= $end; $p++)
                    @if($p === $current)
                    <span aria-current="page" class="w-10 h-10 rounded-full bg-inverse-surface text-inverse-on-surface font-label-md text-label-md font-semibold flex items-center justify-center shadow-sm">{{ $p }}</span>
                    @else
                    <a href="{{ $books->url($p) }}" aria-label="Go to page {{ $p }}" class="w-10 h-10 rounded-full hover:bg-surface-container-lowest text-on-surface-variant font-label-md text-label-md transition-colors flex items-center justify-center">{{ $p }}</a>
                    @endif
                @endfor
            </div>
            @if($books->hasMorePages())
            <a aria-label="Next page" href="{{ $books->nextPageUrl() }}" class="h-10 px-5 rounded-full border border-secondary text-secondary hover:bg-secondary hover:text-on-secondary font-label-md text-label-md font-medium transition-all active:scale-[0.98] flex items-center gap-1.5">
                Next
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </a>
            @else
            <span aria-hidden="true" class="h-10 px-5 rounded-full border border-surface-container-high bg-surface-container-low text-on-surface-variant/40 font-label-md text-label-md font-medium cursor-not-allowed flex items-center gap-1.5">
                Next
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </span>
            @endif
        </div>
    </nav>
    @endif

    @else
    {{-- Empty state --}}
    @if($isSearching)
    @php
        // Genuine bestsellers as the fallback shelf (paid orders only).
        $priceSvc = app(\App\Services\PriceService::class);
        $visitor = auth()->user();
        $popularGuides = collect(app(\App\Services\PurchaseStatsService::class)->topSellers(3))
            ->map(function ($row) use ($priceSvc, $visitor) {
                $b = $row['book'];
                $quote = $priceSvc->getPrice($b, $visitor);
                $b->price_quote = $quote;
                $b->price_display = $priceSvc->format($quote['amount'], $quote['currency']);
                $b->price_regular_display = $quote['regular_amount'] !== null
                    ? $priceSvc->format($quote['regular_amount'], $quote['currency'])
                    : null;
                return $b;
            });
    @endphp
    <!-- Search bar + status (Stitch no-results design) -->
    <section class="w-full bg-surface-container-lowest shadow-sm -mx-4 sm:-mx-6 px-4 sm:px-6" aria-label="Search">
        <div class="max-w-[1120px] mx-auto py-8">
            <div class="max-w-2xl mx-auto flex flex-col items-center">
                <form method="GET" action="{{ route('books') }}" class="w-full relative flex items-center" role="search">
                    <div class="relative w-full flex items-center">
                        <span class="material-symbols-outlined text-[22px] absolute left-4 text-secondary pointer-events-none">search</span>
                        <label for="noresult-search" class="sr-only">Search ebooks</label>
                        <input id="noresult-search" name="q" type="search" value="{{ $searchQuery }}" placeholder="Search wellness guides, recipes, routines..."
                               class="w-full h-12 pl-12 pr-24 rounded-full bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container focus:bg-surface-container-lowest transition-all"
                               autocomplete="off">
                        <button type="submit" aria-label="Execute search" class="absolute right-1.5 h-9 px-4 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.98]">
                            Search
                        </button>
                    </div>
                </form>
                <div class="mt-4 text-center">
                    <p class="font-headline-sm text-headline-sm text-on-surface">
                        No results found for <span class="text-primary italic">&ldquo;{{ $searchQuery }}&rdquo;</span>
                    </p>
                    <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant max-w-lg">
                        We couldn't find any ebooks matching your query. Check your spelling or browse popular categories below.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Empty hero -->
    <section class="w-full py-16 px-4 sm:px-6" aria-label="No results">
        <div class="max-w-[1120px] mx-auto flex flex-col items-center text-center">
            <div class="relative w-28 h-28 rounded-full bg-surface-container-high flex items-center justify-center mb-6 shadow-inner">
                <div class="absolute inset-0 rounded-full bg-secondary-container opacity-40 animate-ping" style="animation-duration: 3s;"></div>
                <svg class="w-14 h-14 relative z-10 text-secondary" fill="none" viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="24" cy="24" r="14" stroke="currentColor" stroke-linecap="round" stroke-width="2.5"></circle>
                    <path d="M34.5 34.5L46 46" stroke="currentColor" stroke-linecap="round" stroke-width="3"></path>
                    <path d="M24 18V28M24 23C21.5 21.5 19 21.8 17.5 23C17.5 26.5 21 28 24 28C27 28 30.5 26.5 30.5 23C29 21.8 26.5 21.5 24 23Z" stroke="#F28C0F" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    <circle cx="38" cy="16" fill="#F28C0F" r="1.5"></circle>
                </svg>
            </div>
            <h1 class="font-display-lg text-headline-lg sm:text-[34px] sm:leading-tight text-on-surface max-w-xl">
                No books matched your search
            </h1>
            <p class="mt-3 font-body-md text-body-md text-on-surface-variant max-w-md">
                Try simpler keywords, or explore our popular categories and bestsellers below.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
                <a class="inline-flex items-center justify-center h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold tracking-wide hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('books') }}">
                    View all ebooks
                </a>
                <a class="inline-flex items-center justify-center h-12 px-6 rounded-full text-secondary hover:text-on-surface hover:bg-surface-container font-label-md text-label-md transition-colors" href="{{ route('books') }}">
                    Clear search
                </a>
            </div>
            @if($categories->isNotEmpty())
            <div class="mt-14 w-full max-w-3xl flex flex-col items-center">
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-secondary font-semibold mb-3">
                    Popular Categories
                </span>
                <div class="flex flex-wrap justify-center gap-2.5">
                    @foreach($categories as $cat)
                    <a class="h-8 px-4 rounded-full bg-surface-container-lowest text-secondary font-label-md text-body-sm shadow-sm hover:bg-inverse-surface hover:text-inverse-on-surface transition-all active:scale-95 inline-flex items-center" href="{{ route('books', ['category' => $cat->slug]) }}">
                        {{ $cat->name }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>

    <!-- Popular guides fallback (genuine bestsellers only) -->
    @if($popularGuides->isNotEmpty())
    <section class="w-full pb-20 px-4 sm:px-6" aria-labelledby="popular-heading">
        <div class="max-w-[1120px] mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-3">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm font-bold uppercase tracking-wider mb-2">
                        <span class="material-symbols-outlined text-[14px]">local_fire_department</span> Community Favorites
                    </span>
                    <h2 id="popular-heading" class="font-headline-lg text-headline-lg text-on-surface">Popular Guides</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Our bestselling ebooks, ranked by real purchases.</p>
                </div>
                <a class="inline-flex items-center gap-1 font-label-md text-label-md text-primary font-semibold hover:text-on-primary-container transition-colors" href="{{ route('books') }}">
                    Browse full library <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($popularGuides as $book)
                @php $cover = $book->displayCoverUrl(); @endphp
                <article class="flex flex-col rounded-2xl bg-surface-container-lowest p-4 shadow-sm hover:shadow-md transition-all duration-300 group">
                    <a href="{{ route('books.show', $book->slug) }}" class="relative w-full aspect-[2/3] rounded-lg overflow-hidden bg-surface-container-high mb-4 flex items-center justify-center" tabindex="-1" aria-hidden="true">
                        @if($cover)
                        <img src="{{ $cover }}" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                        @else
                        <span class="text-secondary font-headline-sm text-headline-sm font-bold text-center px-6">{{ $book->title }}</span>
                        @endif
                        <span class="absolute top-2 left-2 px-2.5 py-1 rounded-full bg-inverse-surface/90 text-inverse-on-surface backdrop-blur-sm font-label-sm text-label-sm font-semibold">
                            Bestseller
                        </span>
                    </a>
                    <div class="flex flex-col flex-1">
                        @if(($book->ratings_count ?? 0) > 0)
                        <p class="mb-1"><x-ui.rating-stars :average="$book->rating_average" :count="$book->ratings_count" star-class="w-3.5 h-3.5" /></p>
                        @endif
                        <h3 class="font-headline-sm text-headline-sm text-on-surface line-clamp-1 group-hover:text-primary transition-colors">
                            <a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a>
                        </h3>
                        @if($book->short_description)
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 line-clamp-2 flex-1">
                            {{ $book->short_description }}
                        </p>
                        @endif
                        <div class="pt-4 mt-auto flex items-center justify-between gap-2">
                            <div class="flex items-baseline gap-2">
                                <span class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ $book->price_display }}</span>
                                @if($book->price_regular_display)
                                <span class="font-body-sm text-body-sm text-on-surface-variant/70 line-through book-card-oldprice">{{ $book->price_regular_display }}</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('cart.store') }}">
                                @csrf
                                <input type="hidden" name="book_id" value="{{ $book->id }}">
                                <button type="submit" aria-label="Add {{ $book->title }} to cart" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-95 shadow-sm">
                                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                                    <span>Add to cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Reassurance bar -->
    <section class="w-full bg-surface-container py-8 px-4 sm:px-6 -mx-4 sm:-mx-6" aria-label="Our promises">
        <div class="max-w-[1120px] mx-auto grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div class="flex flex-col items-center">
                <span class="material-symbols-outlined text-secondary text-[28px] mb-1">bolt</span>
                <span class="font-headline-sm text-body-md font-bold text-on-surface">Instant PDF Delivery</span>
                <span class="font-body-sm text-body-sm text-on-surface-variant">Download immediately after secure payment</span>
            </div>
            <div class="flex flex-col items-center">
                <span class="material-symbols-outlined text-secondary text-[28px] mb-1">devices</span>
                <span class="font-headline-sm text-body-md font-bold text-on-surface">Mobile &amp; Kindle Optimized</span>
                <span class="font-body-sm text-body-sm text-on-surface-variant">Read effortlessly on phone, tablet, or e-reader</span>
            </div>
            <div class="flex flex-col items-center">
                <span class="material-symbols-outlined text-secondary text-[28px] mb-1">verified_user</span>
                <span class="font-headline-sm text-body-md font-bold text-on-surface">Practical Indian Advice</span>
                <span class="font-body-sm text-body-sm text-on-surface-variant">No exotic ingredients, no unrealistic regimens</span>
            </div>
        </div>
    </section>
    @else
    {{-- Catalog genuinely empty (no search active). --}}
    <div class="py-20 text-center">
        <span class="material-symbols-outlined text-secondary text-[56px]">menu_book</span>
        <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold mt-4">No ebooks yet</h1>
        <p class="font-body-md text-body-md text-on-surface-variant mt-2">New guides are on the way — check back soon.</p>
        <a class="inline-flex items-center justify-center h-12 px-8 mt-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all" href="{{ route('home') }}">
            Back to home
        </a>
    </div>
    @endif
    @endif

</div>
</div>
</div>
@endsection
