@extends('layouts.app')

@section('title', $seo->bookTitle($book))
@section('meta_description', $seo->bookDescription($book))
@section('og_type', 'book')

@php
    // displayCoverUrl(): uploaded cover wins, Fit at Home falls back to
    // the Stitch 3D mockup, anything else keeps the CSS placeholder.
    $coverSrc = $book->displayCoverUrl();
    $onSale = $book->price_quote['on_sale'] ?? false;
    $discountPct = $book->price_quote['discount_percent'] ?? null;
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
<div class="max-w-container mx-auto px-4 pt-6 pb-40 md:pb-6">
    <x-ui.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Books', 'url' => route('books')],
        ['label' => $book->title],
    ]" />

    <div class="grid md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-6 lg:gap-10 mt-5">
        {{-- Cover --}}
        <div>
            <div class="st-card overflow-hidden md:sticky md:top-24">
                @if($coverSrc)
                    <img src="{{ $coverSrc }}" alt="Cover of {{ $book->title }}" class="w-full h-auto" fetchpriority="high">
                @else
                    <div class="book-cover-placeholder aspect-[3/4]" role="img" aria-label="Cover coming soon for {{ $book->title }}">
                        <span class="text-3xl">{{ $book->title }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Details --}}
        <div>
            @if($book->category)
            <a href="{{ route('books', ['category' => $book->category->slug]) }}"
               class="text-sm font-extrabold text-[#0B3D4A] hover:underline underline-offset-4">{{ $book->category->name }}</a>
            @endif
            <h1 class="st-h text-[32px] md:text-[44px] mt-1.5 text-balance">{{ $book->title }}</h1>
            @if($book->subtitle)
            <p class="text-lg text-[#3C4B58] mt-1.5">{{ $book->subtitle }}</p>
            @endif

            {{-- Verified-purchase rating summary (real data only) --}}
            @if(($book->ratings_count ?? 0) > 0)
            <div class="mt-3">
                <x-ui.rating-stars :average="$book->rating_average" :count="$book->ratings_count" />
            </div>
            @endif

            @if($book->author)
            <p class="flex items-center gap-2.5 mt-3">
                <span class="st-menu-ic" aria-hidden="true">{{ substr($book->author, 0, 2) }}</span>
                <span>
                    <span class="block text-[11px] font-bold uppercase tracking-[0.14em] st-muted">Author</span>
                    <strong class="text-[#101E28]">{{ $book->author }}</strong>
                </span>
            </p>
            @endif

            {{-- Price block --}}
            <div class="st-card p-5 mt-5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="st-h text-[36px]">{{ $book->price_display }}</span>
                    @if($book->price_regular_display)
                        <span class="book-card-oldprice text-lg">{{ $book->price_regular_display }}</span>
                        @if($discountPct)<span class="off-chip">{{ $discountPct }}% off</span>@endif
                    @endif
                </div>
                @if($onSale && $book->sale_ends_at)
                <div class="sale-banner mt-4 p-4">
                    <div class="relative flex flex-col items-center text-center gap-2.5">
                        <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-[#B45309]">
                            <x-ui.icon name="clock" class="w-4 h-4" /> Sale ends in
                        </p>
                        <x-ui.countdown-boxes :ends-at="$book->sale_ends_at->toIso8601String()" />
                    </div>
                </div>
                @endif
                <p class="text-sm st-muted mt-3">
                    @if($book->price_quote['currency'] === 'INR')
                        Price in Indian rupees, taxes included. International checkout: $4.99 USD.
                    @else
                        Price in US dollars, taxes included.
                    @endif
                </p>

                <div class="flex flex-col sm:flex-row gap-2.5 mt-4">
                    <form method="POST" action="{{ route('cart.store') }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $book->id }}">
                        <button type="submit" class="st-btn st-btn-teal w-full !min-h-[56px]">
                            <x-ui.icon name="cart" class="w-5 h-5" /> Add to cart
                        </button>
                    </form>
                    @if(settings_enabled('features.buy_now', true))
                    <form method="POST" action="{{ route('books.buy-now', $book->slug) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="st-btn st-btn-amber w-full !min-h-[56px]">
                            <x-ui.icon name="zap" class="w-5 h-5" /> Get instant access
                        </button>
                    </form>
                    @endif
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-[#0B3D4A] shrink-0" /> Instant PDF download after payment</li>
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-[#0B3D4A] shrink-0" /> Read on phone, tablet or computer</li>
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-[#0B3D4A] shrink-0" /> Secure payment through Razorpay</li>
                </ul>
            </div>

            {{-- Details row --}}
            <dl class="flex flex-wrap gap-x-5 gap-y-1.5 mt-4 text-sm st-muted">
                @if($book->pages)<div><dt class="sr-only">Pages</dt><dd><strong class="text-[#101E28]">{{ $book->pages }}</strong> pages</dd></div>@endif
                <div><dt class="sr-only">Format</dt><dd><strong class="text-[#101E28]">PDF</strong> ebook</dd></div>
                @if($book->language)<div><dt class="sr-only">Language</dt><dd><strong class="text-[#101E28]">{{ $book->language }}</strong></dd></div>@endif
                @if($book->edition)<div><dt class="sr-only">Edition</dt><dd>Edition <strong class="text-[#101E28]">{{ $book->edition }}</strong></dd></div>@endif
            </dl>

            {{-- No-refund notice --}}
            <div class="rounded-2xl p-4 mt-4 text-sm bg-[#FFF4E5]">
                <strong>All sales are final.</strong> Ebooks are digital products and cannot be returned.
                Please read the description carefully before you buy.
                <a href="{{ route('no-refund') }}" class="underline font-semibold">Read the no-refund policy</a>.
            </div>
        </div>
    </div>

    {{-- Description --}}
    @if($book->description)
    <section class="mt-10 max-w-3xl" aria-labelledby="desc-heading">
        <p class="text-[11px] text-[#B45309] font-bold uppercase tracking-[0.18em]">What's inside</p>
        <h2 id="desc-heading" class="st-h text-[28px] uppercase mt-1">About this ebook</h2>
        <div class="stripe-divider mt-3 mb-5" aria-hidden="true"></div>
        <div class="prose-like text-[#101E28] leading-relaxed space-y-4">
            {!! nl2br(e($book->description)) !!}
        </div>
    </section>
    @endif

    {{-- Ratings & reviews: verified purchases only, never seeded or faked --}}
    <section class="mt-10 max-w-3xl" aria-labelledby="reviews-heading">
        <p class="text-[11px] text-[#B45309] font-bold uppercase tracking-[0.18em]">Verified by buyers</p>
        <h2 id="reviews-heading" class="st-h text-[28px] uppercase mt-1">Ratings &amp; reviews</h2>
        <div class="stripe-divider mt-3 mb-5" aria-hidden="true"></div>

        @if($reviews->isNotEmpty())
        <ol class="space-y-4">
            @foreach($reviews as $review)
            <li class="st-card p-5">
                <div class="flex items-center gap-1" role="img" aria-label="Rated {{ $review->stars }} out of 5 stars">
                    @for($i = 1; $i <= 5; $i++)
                    <svg viewBox="0 0 24 24" class="w-4 h-4 {{ $i <= $review->stars ? 'fill-[#F5A623]' : 'fill-[#E3D9C2]' }}" aria-hidden="true"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
                    @endfor
                </div>
                @if($review->review)
                <p class="mt-2 text-[#101E28] leading-relaxed">{!! nl2br(e($review->review)) !!}</p>
                @endif
                <p class="mt-3 text-[13px] text-[#5B6B78]">
                    <strong class="text-[#0B3D4A]">{{ \Illuminate\Support\Str::before($review->user->name ?? 'Verified buyer', ' ') }}</strong>
                    <span aria-hidden="true">·</span> Verified purchase
                    <span aria-hidden="true">·</span> {{ $review->created_at?->format('j M Y') }}
                </p>
            </li>
            @endforeach
        </ol>
        @else
        <p class="text-[#3C4B58]">No reviews yet. Bought this ebook? You will be able to rate it here.</p>
        @endif
    </section>

    {{-- Rate / edit form: only for customers who own the book via a paid order --}}
    @if($canRate)
    <section class="mt-10 max-w-3xl" aria-labelledby="rate-heading">
        <div class="st-card p-5 md:p-6">
            <h2 id="rate-heading" class="st-h text-[24px]">{{ $userRating ? 'Your review' : 'Rate this book' }}</h2>
            <p class="text-sm text-[#5B6B78] mt-1">Only verified buyers can review — your rating is marked as a verified purchase.</p>

            @if($errors->any())
            <div class="mt-3 rounded-xl bg-[#FDECEA] text-[#9B1C1C] text-sm font-semibold p-3" role="alert">
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
                    <legend class="text-sm font-bold text-[#0B3D4A]">Your rating</legend>
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
                    <label for="review-text" class="text-sm font-bold text-[#0B3D4A]">Your review <span class="font-normal text-[#5B6B78]">(optional)</span></label>
                    <textarea id="review-text" name="review" rows="4" maxlength="2000" class="st-input w-full mt-1.5" placeholder="What did you think of this ebook?">{{ old('review', $userRating->review ?? '') }}</textarea>
                </div>
                <button type="submit" class="st-btn st-btn-teal mt-4">{{ $userRating ? 'Update review' : 'Post review' }}</button>
            </form>
        </div>
    </section>
    @elseif(auth()->check())
    <p class="mt-8 max-w-3xl text-sm text-[#5B6B78]">Only verified buyers can leave a review — buy this ebook to rate it.</p>
    @endif

    {{-- Health disclaimer --}}
    <aside class="st-security-box mt-8 max-w-3xl text-sm" aria-label="Health disclaimer">
        <x-ui.icon name="info" class="w-5 h-5 text-[#0B3D4A] shrink-0" />
        <p><strong>Health disclaimer.</strong> This ebook shares general fitness and nutrition
        information for healthy adults. It is not medical advice. If you have a health
        condition, are pregnant, or take medication, talk to your doctor before changing
        your diet or exercise routine.</p>
    </aside>

    {{-- Related books --}}
    @if($related->isNotEmpty())
    <section class="mt-12" aria-labelledby="related-heading">
        <h2 id="related-heading" class="st-h text-[28px] uppercase mb-4">You may also like</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($related as $rel)
            <x-ui.book-card :title="$rel->title"
                            :author="$rel->author"
                            :description="$rel->short_description"
                            :price="$rel->price_display"
                            :old-price="$rel->price_regular_display"
                            :on-sale="$rel->price_quote['on_sale']"
                            :sale-ends-at="$rel->sale_ends_at?->toIso8601String()"
                            :cover="$rel->displayCoverUrl()"
                            :rating-average="$rel->rating_average ?? null"
                            :rating-count="$rel->ratings_count ?? 0"
                            action-url="{{ route('books.show', $rel->slug) }}"
                            action-label="View details" />
            @endforeach
        </div>
    </section>
    @endif
</div>

{{-- Sticky mobile buy bar --}}
<div class="buy-bar md:hidden">
    <div class="px-4 py-3 flex items-center gap-3">
        <div class="min-w-0">
            <p class="flex items-baseline gap-1.5">
                <span class="st-h text-[24px]">{{ $book->price_display }}</span>
                @if($book->price_regular_display)
                    <span class="book-card-oldprice text-sm">{{ $book->price_regular_display }}</span>
                @endif
            </p>
            <p class="text-[11px] st-muted truncate">{{ $book->title }}</p>
        </div>
        <div class="flex-1"></div>
        <form method="POST" action="{{ route('cart.store') }}">
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            <button type="submit" class="st-btn st-btn-amber uppercase tracking-wide">Buy now</button>
        </form>
    </div>
</div>
@endsection
