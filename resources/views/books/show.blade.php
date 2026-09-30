@extends('layouts.stitch')

@section('title', $seo->bookTitle($book))
@section('meta_description', $seo->bookDescription($book))
@section('og_type', 'book')

@php
    // displayCoverUrl(): uploaded cover wins, Fit at Home falls back to
    // the Stitch 3D mockup, anything else keeps the CSS placeholder.
    $coverSrc = $book->displayCoverUrl();
    $quote = $book->price_quote ?? [];
    $onSale = $quote['on_sale'] ?? false;
    $discountPct = $quote['discount_percent'] ?? null;
    $currency = $quote['currency'] ?? 'INR';
    $ratingsCount = (int) ($book->ratings_count ?? 0);
    $downloadLimit = max(1, (int) settings('downloads.limit', 5));
@endphp

@section('og_image', $coverSrc ?? asset('images/stitch/logo.jpg'))
@section('canonical', route('books.show', $book->slug))

{{-- Schema.org: Book offers + breadcrumbs. --}}
@section('jsonld')
{!! $seo->script($seo->bookJsonLd($book, $book->price_quote, $coverSrc)) !!}
{!! $seo->script($seo->breadcrumbJsonLd([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Books', 'url' => route('books')],
    ['name' => $book->title],
])) !!}
@endsection

@section('content')
<div class="st-page w-full bg-surface">
<div class="max-w-[1120px] mx-auto px-4 sm:px-6 py-6">
<div class="flex flex-col w-full">

<!-- Breadcrumb -->
<nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 font-label-md text-label-md text-on-surface-variant">
    <a class="hover:text-on-surface transition-colors flex items-center gap-1" href="{{ route('home') }}">
        <span class="material-symbols-outlined text-[16px]">home</span>
        <span>Home</span>
    </a>
    <span class="text-outline-variant">/</span>
    <a class="hover:text-on-surface transition-colors" href="{{ route('books') }}">Books</a>
    <span class="text-outline-variant">/</span>
    <span class="text-on-surface font-semibold truncate max-w-xs sm:max-w-md">{{ $book->title }}</span>
</nav>

<!-- Two-column product presentation -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

    <!-- Left column: artwork + preview + spec tags -->
    <div class="lg:col-span-5 flex flex-col gap-5 lg:sticky lg:top-24">
        <div class="relative bg-surface-container rounded-2xl p-6 sm:p-8 flex flex-col items-center justify-center shadow-sm overflow-hidden">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-primary-container/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-tertiary-fixed-dim/20 rounded-full blur-2xl pointer-events-none"></div>
            @if($book->is_popular)
            <div class="self-start mb-4 z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-container/15 text-primary-container font-label-sm text-label-sm font-bold tracking-wide">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                    Bestseller
                </span>
            </div>
            @endif
            <div class="relative z-10 w-full max-w-[280px] sm:max-w-[320px] aspect-[3/4] flex items-center justify-center">
                @if($coverSrc)
                <img src="{{ $coverSrc }}" alt="Cover of {{ $book->title }}" class="w-full h-full object-cover rounded-md shadow-xl" fetchpriority="high">
                @else
                <div class="w-full h-full rounded-md shadow-xl bg-surface-container-lowest flex items-center justify-center p-8 text-center" role="img" aria-label="Cover coming soon for {{ $book->title }}">
                    <span class="font-headline-md text-headline-md text-secondary font-bold">{{ $book->title }}</span>
                </div>
                @endif
            </div>
            @if($book->preview_enabled)
            <a href="{{ route('books.preview', $book->slug) }}" class="mt-6 z-10 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-surface-container-lowest text-secondary font-label-md text-label-md font-semibold hover:bg-surface-container-low transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">menu_book</span>
                <span>Look inside ({{ (int) ($book->preview_pages ?: 5) }} pages free)</span>
            </a>
            @endif
        </div>
        <!-- Spec tags (real data only) -->
        <div class="grid grid-cols-3 gap-2.5">
            <div class="bg-surface-container-lowest rounded-xl p-3 flex flex-col items-center text-center shadow-sm">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">picture_as_pdf</span>
                <span class="font-headline-sm text-headline-sm text-on-surface font-bold text-xs sm:text-sm">PDF ebook</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant text-[11px]">Universal file</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-3 flex flex-col items-center text-center shadow-sm">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">bolt</span>
                <span class="font-headline-sm text-headline-sm text-on-surface font-bold text-xs sm:text-sm">Instant</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant text-[11px]">Direct download</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-3 flex flex-col items-center text-center shadow-sm">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">auto_stories</span>
                <span class="font-headline-sm text-headline-sm text-on-surface font-bold text-xs sm:text-sm">@if($book->pages){{ $book->pages }} Pages @else Digital @endif</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant text-[11px]">@if($book->language){{ $book->language }} @else Ebook @endif</span>
            </div>
        </div>
    </div>

    <!-- Right column: purchase info -->
    <div class="lg:col-span-7 flex flex-col gap-6">
        <div class="space-y-3">
            <div class="flex items-center gap-2 flex-wrap">
                @if($book->category)
                <a href="{{ route('books', ['category' => $book->category->slug]) }}" class="px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold hover:underline">
                    {{ $book->category->name }}
                </a>
                @endif
                @if($book->edition)
                <span class="font-label-sm text-label-sm text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">verified</span> Edition {{ $book->edition }}
                </span>
                @endif
            </div>
            <h1 class="font-display-lg text-headline-lg sm:text-display-lg text-on-surface tracking-tight leading-tight font-bold">
                {{ $book->title }}
            </h1>
            @if($book->subtitle)
            <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">{{ $book->subtitle }}</p>
            @elseif($book->short_description)
            <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed">{{ $book->short_description }}</p>
            @endif

            <!-- Rating summary: verified purchases only, real data -->
            @if($ratingsCount > 0)
            <div class="pt-1 flex flex-wrap items-center gap-3">
                <x-ui.rating-stars :average="$book->rating_average" :count="$ratingsCount" />
                <span class="text-outline-variant font-label-sm">•</span>
                <a class="font-label-md text-label-md text-secondary hover:text-on-surface underline font-medium" href="#reviews">
                    {{ $ratingsCount }} {{ \Illuminate\Support\Str::plural('reader rating', $ratingsCount) }}
                </a>
            </div>
            @endif

            @if($book->author)
            <div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface pt-1">
                <span class="material-symbols-outlined text-[18px] text-secondary">badge</span>
                <span>By <strong>{{ $book->author }}</strong></span>
            </div>
            @endif
        </div>

        <!-- Pricing strip -->
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex items-baseline gap-3 flex-wrap">
                <span class="font-display-lg text-headline-lg font-bold text-on-surface">{{ $book->price_display }}</span>
                @if($book->price_regular_display)
                <span class="font-headline-sm text-headline-sm text-on-surface-variant line-through text-lg book-card-oldprice">{{ $book->price_regular_display }}</span>
                @endif
                @if($onSale && $discountPct)
                <span class="px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed-variant font-label-sm text-label-sm font-bold">
                    {{ $discountPct }}% off
                </span>
                @endif
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-tertiary">check_circle</span>
                <span>One-time purchase. All taxes included. No subscription.</span>
            </p>
            @if($onSale && $book->sale_ends_at)
            <div class="rounded-xl bg-surface-container-low p-4">
                <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-secondary mb-2">
                    <span class="material-symbols-outlined text-[16px]">schedule</span> Sale ends in
                </p>
                <x-ui.countdown-boxes :ends-at="$book->sale_ends_at->toIso8601String()" />
            </div>
            @endif
            <p class="text-sm text-on-surface-variant">
                @if($currency === 'INR')
                    Price in Indian rupees, taxes included. International checkout: $4.99 USD.
                @else
                    Price in US dollars, taxes included.
                @endif
            </p>

            <!-- CTAs -->
            <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if(settings_enabled('features.buy_now', true))
                <form method="POST" action="{{ route('books.buy-now', $book->slug) }}">
                    @csrf
                    <button type="submit" class="h-12 w-full rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-bold flex items-center justify-center gap-2 hover:bg-primary transition-all active:scale-[0.99] shadow-md shadow-primary-container/20">
                        <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                        <span>Buy now &amp; Download</span>
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('cart.store') }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <button type="submit" class="h-12 w-full rounded-full bg-surface-container-low text-secondary font-label-md text-label-md font-semibold flex items-center justify-center gap-2 hover:bg-secondary-container transition-colors active:scale-[0.99]">
                        <span class="material-symbols-outlined text-[20px]">add_shopping_cart</span>
                        <span>Add to cart</span>
                    </button>
                </form>
            </div>

            <!-- Trust badges -->
            <div class="pt-2 flex items-center justify-around text-center border-t border-surface-container-high font-label-sm text-label-sm text-on-surface-variant">
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-secondary">verified_user</span> Secure Razorpay checkout</span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px] text-secondary">mail</span> Instant email delivery</span>
            </div>
        </div>

        <!-- What's included: honest, generic digital-package facts -->
        <div class="bg-surface-container-low rounded-2xl p-6 space-y-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container">inventory_2</span>
                <span>What's included in this digital package:</span>
            </h3>
            <ul class="space-y-3">
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-surface-container-lowest text-tertiary flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                        <span class="material-symbols-outlined text-[16px] font-bold">check</span>
                    </span>
                    <div class="font-body-md text-body-md text-on-surface">
                        <strong class="font-semibold text-on-surface">Full PDF ebook:</strong> read on your phone, tablet, computer or Kindle.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-surface-container-lowest text-tertiary flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                        <span class="material-symbols-outlined text-[16px] font-bold">check</span>
                    </span>
                    <div class="font-body-md text-body-md text-on-surface">
                        <strong class="font-semibold text-on-surface">Instant access:</strong> download link on screen right after payment, plus a backup copy by email.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-surface-container-lowest text-tertiary flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                        <span class="material-symbols-outlined text-[16px] font-bold">check</span>
                    </span>
                    <div class="font-body-md text-body-md text-on-surface">
                        <strong class="font-semibold text-on-surface">{{ $downloadLimit }} downloads</strong> included with your purchase.
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-surface-container-lowest text-tertiary flex items-center justify-center flex-shrink-0 shadow-sm mt-0.5">
                        <span class="material-symbols-outlined text-[16px] font-bold">check</span>
                    </span>
                    <div class="font-body-md text-body-md text-on-surface">
                        <strong class="font-semibold text-on-surface">Secure payment</strong> through Razorpay — UPI, cards and netbanking.
                    </div>
                </li>
            </ul>
        </div>

        <!-- Notices -->
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-sm flex items-start gap-3">
            <span class="material-symbols-outlined text-secondary text-[20px] flex-shrink-0 mt-0.5">info</span>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                <strong class="text-on-surface font-semibold">Please note:</strong> all sales are final — ebooks are digital products and cannot be returned.
                <a href="{{ route('no-refund') }}" class="underline font-semibold">Read the no-refund policy</a>.
            </p>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-4 shadow-sm flex items-start gap-3">
            <span class="material-symbols-outlined text-secondary text-[20px] flex-shrink-0 mt-0.5">health_and_safety</span>
            <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                <strong class="text-on-surface font-semibold">Health disclaimer:</strong> this ebook shares general wellness information for healthy adults.
                It is not medical advice. If you have a health condition, are pregnant, or take medication, talk to your doctor first.
            </p>
        </div>
    </div>
</div>

<!-- Deep dive: about this ebook -->
<div class="mt-16 space-y-12">
    @if($book->description)
    <section class="bg-surface-container-lowest rounded-2xl p-6 sm:p-10 shadow-sm" aria-labelledby="about-heading">
        <div class="max-w-3xl">
            <span class="font-label-sm text-label-sm text-primary uppercase font-bold tracking-widest">About this book</span>
            <h2 id="about-heading" class="font-headline-lg text-headline-lg text-on-surface mt-1 mb-6">
                {{ $book->title }}
            </h2>
            <div class="space-y-4 font-body-lg text-body-lg text-on-surface-variant leading-relaxed">
                {!! nl2br(e($book->description)) !!}
            </div>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 border-t border-surface-container">
                @if($book->pages)
                <div class="bg-surface-container-low rounded-xl p-4">
                    <div class="font-display-lg text-headline-lg font-bold text-on-surface">{{ $book->pages }}</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">Pages of practical guidance</div>
                </div>
                @endif
                <div class="bg-surface-container-low rounded-xl p-4">
                    <div class="font-display-lg text-headline-lg font-bold text-on-surface">PDF</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">Digital format, yours to keep</div>
                </div>
                @if($book->language)
                <div class="bg-surface-container-low rounded-xl p-4">
                    <div class="font-display-lg text-headline-lg font-bold text-on-surface">{{ $book->language }}</div>
                    <div class="font-body-sm text-body-sm text-on-surface-variant">Written in simple language</div>
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    <!-- Author strip (real author only, no invented credentials) -->
    @if($book->author)
    <section class="bg-surface-container-low rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-6 shadow-sm" aria-label="About the author">
        <div class="w-24 h-24 rounded-full bg-surface-container-highest flex-shrink-0 flex items-center justify-center shadow-inner">
            <span class="material-symbols-outlined text-[48px] text-secondary">account_circle</span>
        </div>
        <div class="space-y-2 text-center sm:text-left">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <h3 class="font-headline-md text-headline-md text-on-surface">{{ $book->author }}</h3>
                <span class="px-2 py-0.5 rounded-full bg-surface-container-highest text-secondary font-label-sm text-label-sm font-semibold">Author</span>
            </div>
        </div>
    </section>
    @endif

    <!-- Reviews: verified purchases only, never faked -->
    <section class="bg-surface-container-lowest rounded-2xl p-6 sm:p-8 shadow-sm space-y-6" id="reviews" aria-labelledby="reviews-heading">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="font-label-sm text-label-sm text-primary uppercase font-bold tracking-widest">Verified by buyers</span>
                <h2 id="reviews-heading" class="font-headline-md text-headline-md text-on-surface">Ratings &amp; Reviews</h2>
            </div>
            @if($ratingsCount > 0)
            <div class="flex items-center gap-2">
                <span class="font-headline-md text-headline-md font-bold text-on-surface">{{ number_format((float) $book->rating_average, 1) }} / 5.0</span>
                <span class="inline-flex items-center gap-0.5" role="img" aria-label="Rated {{ number_format((float) $book->rating_average, 1) }} out of 5">
                    @for($i = 1; $i <= 5; $i++)
                    <span class="material-symbols-outlined text-[18px] text-primary-container" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">star</span>
                    @endfor
                </span>
            </div>
            @endif
        </div>

        @if($reviews->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($reviews as $review)
            <div class="bg-surface-container-low rounded-xl p-4 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-headline-sm text-headline-sm text-on-surface text-sm font-bold">{{ \Illuminate\Support\Str::before($review->user->name ?? 'Verified buyer', ' ') }}</span>
                    <span class="font-label-sm text-label-sm text-tertiary flex items-center gap-1 shrink-0">
                        <span class="material-symbols-outlined text-[14px]">verified</span> Verified Buyer
                    </span>
                </div>
                <div class="flex items-center gap-0.5" role="img" aria-label="Rated {{ $review->stars }} out of 5 stars">
                    @for($i = 1; $i <= 5; $i++)
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 {{ $i <= $review->stars ? 'fill-[#F5A623]' : 'fill-[#E3D9C2]' }}" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                    @endfor
                </div>
                @if($review->review)
                <p class="font-body-sm text-body-sm text-on-surface-variant">{!! nl2br(e($review->review)) !!}</p>
                @endif
                <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $review->created_at?->format('j M Y') }}</p>
            </div>
            @endforeach
        </div>
        @else
        <p class="font-body-md text-body-md text-on-surface-variant">No reviews yet. Bought this ebook? You will be able to rate it here.</p>
        @endif

        {{-- Rate form: only customers who own the book via a paid order --}}
        @if($canRate)
        <div class="bg-surface-container-low rounded-2xl p-5 md:p-6">
            <h3 class="font-headline-sm text-headline-sm text-on-surface text-lg">{{ $userRating ? 'Your review' : 'Rate this book' }}</h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Only verified buyers can review — your rating is marked as a verified purchase.</p>

            @if($errors->any())
            <div class="mt-3 rounded-xl bg-error-container border border-error/30 text-on-error-container text-sm font-semibold p-3" role="alert">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ $userRating ? route('books.ratings.update', [$book->slug, $userRating->id]) : route('books.ratings.store', $book->slug) }}" class="mt-4">
                @csrf
                @if($userRating)
                @method('PUT')
                @endif
                <style>
                    .star-input{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:2px}
                    .star-input input{position:absolute;opacity:0;pointer-events:none}
                    .star-input label{cursor:pointer;padding:2px}
                    .star-input label svg{fill:#E3D9C2;transition:fill .12s}
                    .star-input label:hover svg,.star-input label:hover~label svg,.star-input input:checked~label svg{fill:#F5A623}
                    .star-input input:focus-visible+label{outline:2px solid #0B3D4A;border-radius:6px}
                </style>
                <fieldset>
                    <legend class="font-label-md text-label-md font-bold text-on-surface">Your rating</legend>
                    <div class="star-input mt-1.5" role="radiogroup" aria-label="Star rating">
                        @foreach([5, 4, 3, 2, 1] as $n)
                        <input type="radio" name="stars" id="stars-{{ $n }}" value="{{ $n }}" @checked(($userRating->stars ?? 0) === $n) required>
                        <label for="stars-{{ $n }}" title="{{ $n }} {{ \Illuminate\Support\Str::plural('star', $n) }}">
                            <svg viewBox="0 0 24 24" class="w-9 h-9" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                            <span class="sr-only">{{ $n }} {{ \Illuminate\Support\Str::plural('star', $n) }}</span>
                        </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-4">
                    <label for="review-text" class="font-label-md text-label-md font-bold text-on-surface">Your review <span class="font-normal text-on-surface-variant">(optional)</span></label>
                    <textarea id="review-text" name="review" rows="4" maxlength="2000" placeholder="What did you think of this ebook?"
                              class="w-full mt-1.5 rounded-DEFAULT border border-surface-container-high bg-surface px-4 py-3 font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all">{{ old('review', $userRating->review ?? '') }}</textarea>
                </div>
                <button type="submit" class="mt-4 inline-flex items-center justify-center h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm">
                    {{ $userRating ? 'Update review' : 'Post review' }}
                </button>
            </form>
        </div>
        @elseif(auth()->check())
        <p class="font-body-sm text-body-sm text-on-surface-variant">Only verified buyers can leave a review — buy this ebook to rate it.</p>
        @endif
    </section>

    <!-- Related books (real) -->
    @if($related->isNotEmpty())
    <section class="space-y-6" aria-labelledby="related-heading">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-label-sm text-label-sm text-primary uppercase font-bold tracking-widest">Recommended Reads</span>
                <h2 id="related-heading" class="font-headline-lg text-headline-lg text-on-surface mt-0.5">You May Also Like</h2>
            </div>
            <a class="font-label-md text-label-md text-secondary hover:text-on-surface font-semibold flex items-center gap-1" href="{{ route('books') }}">
                <span>View all catalog</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            @foreach($related as $rel)
            @php
                $relCover = $rel->displayCoverUrl();
                $relQuote = $rel->price_quote ?? [];
            @endphp
            <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
                <div class="space-y-4">
                    <a href="{{ route('books.show', $rel->slug) }}" class="w-full aspect-[3/4] bg-surface-container rounded-xl flex items-center justify-center overflow-hidden relative" tabindex="-1" aria-hidden="true">
                        @if($relCover)
                        <img src="{{ $relCover }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        @else
                        <span class="material-symbols-outlined text-[64px] text-secondary/40 group-hover:scale-105 transition-transform duration-300">menu_book</span>
                        @endif
                        @if($rel->category)
                        <span class="absolute top-3 left-3 px-2 py-0.5 rounded-full bg-surface-container-lowest font-label-sm text-label-sm text-secondary font-semibold shadow-sm">{{ $rel->category->name }}</span>
                        @endif
                    </a>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface text-base group-hover:text-primary transition-colors">
                            <a href="{{ route('books.show', $rel->slug) }}">{{ $rel->title }}</a>
                        </h3>
                        @if($rel->short_description)
                        <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 mt-1">
                            {{ $rel->short_description }}
                        </p>
                        @endif
                    </div>
                </div>
                <div class="pt-4 mt-4 border-t border-surface-container flex items-center justify-between">
                    <div>
                        <span class="font-headline-sm text-headline-sm text-on-surface font-bold text-base">{{ $rel->price_display }}</span>
                        @if($rel->price_regular_display)
                        <span class="font-body-sm text-body-sm text-on-surface-variant line-through text-xs ml-1 book-card-oldprice">{{ $rel->price_regular_display }}</span>
                        @endif
                    </div>
                    <a class="px-3 py-1.5 rounded-full bg-surface-container-low hover:bg-primary hover:text-on-primary text-secondary font-label-sm text-label-sm font-semibold transition-colors" href="{{ route('books.show', $rel->slug) }}">
                        View Guide
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>

<!-- Mobile sticky buy bar -->
<div class="fixed bottom-0 left-0 right-0 z-40 bg-surface-container-lowest/95 backdrop-blur border-t border-surface-container px-4 py-3 sm:hidden shadow-lg">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-baseline gap-1.5">
                <span class="font-headline-md text-headline-md font-bold text-on-surface text-lg">{{ $book->price_display }}</span>
                @if($book->price_regular_display)
                <span class="font-body-sm text-body-sm text-on-surface-variant line-through text-xs">{{ $book->price_regular_display }}</span>
                @endif
            </div>
            <span class="font-label-sm text-label-sm text-tertiary text-[10px] block font-medium truncate">{{ $book->title }}</span>
        </div>
        <form method="POST" action="{{ route('cart.store') }}" class="shrink-0">
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            <button type="submit" class="h-10 px-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-bold flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                <span>Buy now</span>
            </button>
        </form>
    </div>
</div>

</div>
</div>
@endsection
