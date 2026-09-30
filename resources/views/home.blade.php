@extends('layouts.stitch')

@section('title', 'MyEbookBazaar — Simple, practical ebooks for everyday wellness')
@section('meta_description', 'MyEbookBazaar — simple, affordable ebooks on fitness, skills and money. Instant PDF download, secure payment through Razorpay.')
@section('canonical', route('home'))

{{-- Schema.org: Organization + WebSite for the home page. --}}
@section('jsonld')
{!! $seo->script($seo->organizationJsonLd()) !!}
@endsection

@php
    $heroBook = $featured->first();
    $heroCover = $heroBook?->displayCoverUrl();
    $downloadLimit = max(1, (int) settings('downloads.limit', 5));
    // Genuine bestsellers only (paid orders). Never shown when empty.
    $topSellerIds = collect(app(\App\Services\PurchaseStatsService::class)->topSellers(10))
        ->map(fn ($row) => $row['book']->id)
        ->all();
@endphp

@section('content')
<div class="st-page w-full bg-surface">
<div class="max-w-[1120px] mx-auto px-4 sm:px-6 py-6">
<div class="w-full space-y-[72px] py-6">

<!-- 1. HERO -->
@if($heroBook)
<section class="st-hero grid grid-cols-1 lg:grid-cols-12 gap-10 items-center pt-2" aria-labelledby="hero-heading">
    <!-- Left column -->
    <div class="lg:col-span-6 flex flex-col items-start">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-container/15 text-primary font-label-sm text-label-sm mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
            <span>Featured ebook</span>
        </div>
        <h1 id="hero-heading" class="font-headline-lg text-display-lg text-on-surface tracking-tight leading-[1.08] font-bold">
            Get fit at home, the Indian way.
        </h1>
        <p class="font-body-md text-body-md text-on-surface-variant font-semibold mt-3">{{ $heroBook->title }}</p>
        <p class="font-body-lg text-body-lg text-secondary leading-relaxed max-w-lg mt-2">
            {{ $heroBook->short_description ?: 'Simple diet and workout plans using food from your own kitchen. No gym, no equipment.' }}
        </p>
        <div class="mt-8 flex flex-wrap items-center gap-6">
            <form method="POST" action="{{ route('cart.store') }}">
                @csrf
                <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                <button type="submit" class="inline-flex items-center justify-center bg-primary-container text-on-primary px-8 py-3.5 rounded-full font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm">
                    Get the ebook — {{ $heroBook->price_display }}
                </button>
            </form>
            <a class="font-label-md text-label-md font-medium text-secondary hover:text-on-surface hover:underline transition-colors flex items-center gap-1.5" href="#whats-inside">
                <span>See what's inside</span>
                <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
            </a>
        </div>
        {{-- Real rating only — never invented social proof. --}}
        @if(($heroBook->ratings_count ?? 0) > 0)
        <div class="mt-8 pt-6">
            <x-ui.rating-stars :average="$heroBook->rating_average" :count="$heroBook->ratings_count" />
        </div>
        @endif
    </div>
    <!-- Right column: cover showcase -->
    <div class="lg:col-span-6 flex justify-center">
        <div class="w-full max-w-md bg-surface-container-low rounded-lg p-8 relative overflow-hidden flex items-center justify-center">
            <div class="absolute -right-16 -top-16 w-52 h-20 bg-primary-container/20 transform rotate-45 pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-44 h-16 bg-primary-container/10 transform rotate-45 pointer-events-none"></div>
            <div class="relative z-10 p-2 bg-surface-container-lowest/80 rounded-DEFAULT shadow-md backdrop-blur-sm">
                <a href="{{ route('books.show', $heroBook->slug) }}" tabindex="-1" aria-hidden="true" class="block">
                    @if($heroCover)
                    <img src="{{ $heroCover }}" alt="Cover of {{ $heroBook->title }}" class="rounded-sm shadow-sm max-h-[460px] w-auto object-contain mx-auto" fetchpriority="high">
                    @else
                    <span class="flex items-center justify-center rounded-sm max-h-[460px] min-h-[320px] w-64 bg-surface-container text-secondary font-headline-sm text-headline-sm font-bold text-center px-6">{{ $heroBook->title }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</section>
@endif

<!-- 2. TRUST STRIP -->
<section class="bg-surface-container-lowest rounded-DEFAULT py-6 px-8 sm:px-10 shadow-sm" aria-label="Why buy from us">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 items-center">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-full bg-surface-container flex items-center justify-center text-secondary flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
            <div>
                <h2 class="font-headline-sm text-body-lg text-on-surface font-semibold">Instant download</h2>
                <p class="font-body-sm text-body-sm text-secondary">PDF delivered immediately after payment</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-full bg-surface-container flex items-center justify-center text-secondary flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
            <div>
                <h2 class="font-headline-sm text-body-lg text-on-surface font-semibold">Made for Indian kitchens</h2>
                <p class="font-body-sm text-body-sm text-secondary">Local grains, pulses &amp; everyday spices</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-full bg-surface-container flex items-center justify-center text-secondary flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
            <div>
                <h2 class="font-headline-sm text-body-lg text-on-surface font-semibold">Secure payment</h2>
                <p class="font-body-sm text-body-sm text-secondary">Razorpay checkout — UPI &amp; cards</p>
            </div>
        </div>
    </div>
</section>

<!-- 3. FEATURED EBOOKS -->
@if($featured->isNotEmpty())
<section class="space-y-6" aria-labelledby="featured-heading">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary font-semibold">Curated Catalog</span>
            <h2 id="featured-heading" class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-1">Featured Guides</h2>
        </div>
        <a class="font-label-md text-label-md text-secondary hover:text-on-surface font-semibold flex items-center gap-1" href="{{ route('books') }}">
            <span>View all ebooks</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($featured->take(3) as $book)
        @php
            $cover = $book->displayCoverUrl();
            $isBestseller = in_array($book->id, $topSellerIds, true);
        @endphp
        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
            <div>
                <a href="{{ route('books.show', $book->slug) }}" class="block bg-surface-container-low rounded-sm p-4 flex items-center justify-center aspect-[3/4] mb-5 overflow-hidden" tabindex="-1" aria-hidden="true">
                    @if($cover)
                    <img src="{{ $cover }}" alt="" class="h-full w-auto object-contain rounded-sm" loading="lazy">
                    @else
                    <span class="text-secondary font-headline-sm text-headline-sm font-bold text-center px-4">{{ $book->title }}</span>
                    @endif
                </a>
                @if($isBestseller)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-primary-container/15 text-primary text-label-sm font-label-sm mb-2">
                    <span class="material-symbols-outlined text-[14px]">star</span>
                    <span>Bestseller</span>
                </div>
                @elseif($book->category)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container text-secondary text-label-sm font-label-sm mb-2">
                    <span>{{ $book->category->name }}</span>
                </div>
                @endif
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">
                    <a href="{{ route('books.show', $book->slug) }}" class="hover:text-primary transition-colors">{{ $book->title }}</a>
                </h3>
                @if($book->short_description)
                <p class="font-body-sm text-body-sm text-secondary mt-2 line-clamp-2">{{ $book->short_description }}</p>
                @endif
                @if(($book->ratings_count ?? 0) > 0)
                <p class="mt-2"><x-ui.rating-stars :average="$book->rating_average" :count="$book->ratings_count" star-class="w-3.5 h-3.5" /></p>
                @endif
            </div>
            <div class="mt-6 pt-4 border-t border-outline-variant/30 flex items-center justify-between">
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-md text-headline-md text-on-surface font-bold">{{ $book->price_display }}</span>
                    @if($book->price_regular_display)
                    <span class="font-body-sm text-body-sm text-secondary line-through">{{ $book->price_regular_display }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('cart.store') }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-colors">
                        <span class="material-symbols-outlined text-[16px]">add_shopping_cart</span>
                        <span>Add to cart</span>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- 4. WHAT'S INSIDE -->
<section class="space-y-6" id="whats-inside" aria-labelledby="inside-heading">
    <div class="max-w-xl">
        <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary font-semibold">Transparent Curriculum</span>
        <h2 id="inside-heading" class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-1">What's Inside</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 flex gap-5 items-start shadow-sm">
            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container flex-shrink-0">
                <span class="material-symbols-outlined text-[24px]">skillet</span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm text-primary font-bold">MODULE 01</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Diet plans with real food</h3>
                <p class="font-body-md text-body-md text-secondary mt-2 leading-relaxed">
                    Everyday Indian food — dal, roti, rice, eggs and seasonal vegetables. No exotic ingredients, no starving, no complicated weighing scales.
                </p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 flex gap-5 items-start shadow-sm">
            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container flex-shrink-0">
                <span class="material-symbols-outlined text-[24px]">fitness_center</span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm text-primary font-bold">MODULE 02</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Home workouts, zero equipment</h3>
                <p class="font-body-md text-body-md text-secondary mt-2 leading-relaxed">
                    Short no-equipment routines for your living room. Twenty minutes a day is enough to start — no gym membership needed.
                </p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 flex gap-5 items-start shadow-sm">
            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container flex-shrink-0">
                <span class="material-symbols-outlined text-[24px]">calendar_month</span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm text-primary font-bold">MODULE 03</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">A clear 30-day roadmap</h3>
                <p class="font-body-md text-body-md text-secondary mt-2 leading-relaxed">
                    A day-by-day plan that takes you from Day 1 to Day 30 — gentle ramps that build momentum without burnout.
                </p>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 flex gap-5 items-start shadow-sm">
            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center text-primary-container flex-shrink-0">
                <span class="material-symbols-outlined text-[24px]">verified</span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm text-primary font-bold">MODULE 04</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Myth busting</h3>
                <p class="font-body-md text-body-md text-secondary mt-2 leading-relaxed">
                    Straight answers on rice, ghee, snacking and fasting — what actually matters for your health, and what doesn't.
                </p>
            </div>
        </div>
    </div>
    <div class="p-4 bg-surface-container-low rounded-DEFAULT flex items-center gap-3">
        <span class="material-symbols-outlined text-secondary text-[20px] flex-shrink-0">info</span>
        <p class="font-body-sm text-body-sm text-secondary">
            <span class="font-semibold text-on-surface">Note:</span> This guide provides general nutritional and physical wellness education, not medical advice.
        </p>
    </div>
</section>

<!-- 5. HOW IT WORKS -->
<section class="space-y-8" aria-labelledby="how-heading">
    <div class="text-center max-w-lg mx-auto">
        <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary font-semibold">Frictionless Reading</span>
        <h2 id="how-heading" class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-1">How It Works</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
        <div class="bg-surface-container-lowest rounded-DEFAULT p-8 relative flex flex-col items-start shadow-sm">
            <div class="w-10 h-10 rounded-full bg-surface-container text-secondary font-headline-sm text-headline-sm font-bold flex items-center justify-center mb-6">
                1
            </div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold mb-2">Choose your guide</h3>
            <p class="font-body-md text-body-md text-secondary">
                Pick the ebook that fits your goal — fitness, food, or everyday habits.
            </p>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT p-8 relative flex flex-col items-start shadow-sm">
            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary font-headline-sm text-headline-sm font-bold flex items-center justify-center mb-6 shadow-sm">
                2
            </div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold mb-2">Pay securely</h3>
            <p class="font-body-md text-body-md text-secondary">
                Fast checkout with UPI, cards and netbanking through Razorpay's secure checkout.
            </p>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT p-8 relative flex flex-col items-start shadow-sm">
            <div class="w-10 h-10 rounded-full bg-surface-container text-secondary font-headline-sm text-headline-sm font-bold flex items-center justify-center mb-6">
                3
            </div>
            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold mb-2">Download instantly</h3>
            <p class="font-body-md text-body-md text-secondary">
                Get your PDF right after payment, plus a backup link by email. Read on any device.
            </p>
        </div>
    </div>
</section>

<!-- 6. COMBOS / OFFERS (real admin-configured bundles only) -->
@if(settings_enabled('features.combos') && $combos->isNotEmpty())
<section aria-label="Bundles and offers" class="space-y-6">
    @foreach($combos as $combo)
    @php
        $comboQuote = $combo->price_quote ?? [];
        $comboPct = $comboQuote['discount_percent'] ?? null;
    @endphp
    <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
        <div class="flex items-center gap-5">
            <div class="w-12 h-12 rounded-full bg-primary-container/15 text-primary flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[26px]">local_fire_department</span>
            </div>
            <div>
                <div class="inline-flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm font-bold text-primary uppercase">Bundle offer</span>
                    @if($comboPct)
                    <span class="w-1 h-1 rounded-full bg-outline"></span>
                    <span class="font-label-sm text-label-sm text-secondary font-medium">Save {{ $comboPct }}%</span>
                    @endif
                </div>
                <p class="font-headline-sm text-headline-sm text-on-surface font-bold">
                    {{ $combo->title }}
                </p>
                @if($combo->description)
                <p class="font-body-sm text-body-sm text-secondary mt-1">{{ $combo->description }}</p>
                @endif
            </div>
        </div>
        <div class="flex-shrink-0 w-full md:w-auto flex flex-col items-center gap-3">
            <p class="flex items-baseline gap-2">
                <span class="font-headline-md text-headline-md text-on-surface font-bold">{{ $combo->price_display }}</span>
                @if($combo->price_regular_display)
                <span class="font-body-sm text-body-sm text-secondary line-through">{{ $combo->price_regular_display }}</span>
                @endif
            </p>
            <a class="w-full md:w-auto inline-flex items-center justify-center bg-primary-container text-on-primary px-8 py-3.5 rounded-full font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('combos.show', $combo->slug) }}">
                View bundle
            </a>
        </div>
    </div>
    @endforeach
</section>
@endif

<!-- 7. FAQ -->
<section class="space-y-6" aria-labelledby="faq-heading">
    <div class="text-center max-w-xl mx-auto">
        <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary font-semibold">Common Queries</span>
        <h2 id="faq-heading" class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight mt-1">Frequently Asked Questions</h2>
    </div>
    <div class="max-w-3xl mx-auto space-y-3">
        <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden shadow-sm">
            <button class="w-full px-6 py-4 flex items-center justify-between text-left font-headline-sm text-body-lg text-on-surface font-semibold hover:text-primary transition-colors focus:outline-none" onclick="toggleFaq('faq-1')" type="button" aria-expanded="false" aria-controls="faq-1-content" id="faq-1-btn">
                <span>How do I receive the ebook?</span>
                <span class="material-symbols-outlined text-[20px] text-secondary transition-transform" id="faq-1-icon">expand_more</span>
            </button>
            <div class="px-6 pb-4 pt-1 text-secondary font-body-md text-body-md hidden" id="faq-1-content" role="region" aria-labelledby="faq-1-btn">
                A download link appears immediately after payment, and a backup copy is emailed to you.
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden shadow-sm">
            <button class="w-full px-6 py-4 flex items-center justify-between text-left font-headline-sm text-body-lg text-on-surface font-semibold hover:text-primary transition-colors focus:outline-none" onclick="toggleFaq('faq-2')" type="button" aria-expanded="false" aria-controls="faq-2-content" id="faq-2-btn">
                <span>What payment methods do you accept?</span>
                <span class="material-symbols-outlined text-[20px] text-secondary transition-transform" id="faq-2-icon">expand_more</span>
            </button>
            <div class="px-6 pb-4 pt-1 text-secondary font-body-md text-body-md hidden" id="faq-2-content" role="region" aria-labelledby="faq-2-btn">
                UPI, credit and debit cards, and netbanking — processed securely through Razorpay.
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden shadow-sm">
            <button class="w-full px-6 py-4 flex items-center justify-between text-left font-headline-sm text-body-lg text-on-surface font-semibold hover:text-primary transition-colors focus:outline-none" onclick="toggleFaq('faq-3')" type="button" aria-expanded="false" aria-controls="faq-3-content" id="faq-3-btn">
                <span>What format is the ebook?</span>
                <span class="material-symbols-outlined text-[20px] text-secondary transition-transform" id="faq-3-icon">expand_more</span>
            </button>
            <div class="px-6 pb-4 pt-1 text-secondary font-body-md text-body-md hidden" id="faq-3-content" role="region" aria-labelledby="faq-3-btn">
                A high-resolution PDF. It opens on phones, tablets, computers and Kindle — no special app needed.
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden shadow-sm">
            <button class="w-full px-6 py-4 flex items-center justify-between text-left font-headline-sm text-body-lg text-on-surface font-semibold hover:text-primary transition-colors focus:outline-none" onclick="toggleFaq('faq-4')" type="button" aria-expanded="false" aria-controls="faq-4-content" id="faq-4-btn">
                <span>How many times can I download?</span>
                <span class="material-symbols-outlined text-[20px] text-secondary transition-transform" id="faq-4-icon">expand_more</span>
            </button>
            <div class="px-6 pb-4 pt-1 text-secondary font-body-md text-body-md hidden" id="faq-4-content" role="region" aria-labelledby="faq-4-btn">
                You get {{ $downloadLimit }} downloads. Each link works for 10 minutes after it is created.
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-DEFAULT overflow-hidden shadow-sm">
            <button class="w-full px-6 py-4 flex items-center justify-between text-left font-headline-sm text-body-lg text-on-surface font-semibold hover:text-primary transition-colors focus:outline-none" onclick="toggleFaq('faq-5')" type="button" aria-expanded="false" aria-controls="faq-5-content" id="faq-5-btn">
                <span>Can I get a refund?</span>
                <span class="material-symbols-outlined text-[20px] text-secondary transition-transform" id="faq-5-icon">expand_more</span>
            </button>
            <div class="px-6 pb-4 pt-1 text-secondary font-body-md text-body-md hidden" id="faq-5-content" role="region" aria-labelledby="faq-5-btn">
                All sales are final — ebooks can&rsquo;t be returned once downloaded. Please read the book description carefully before you buy.
            </div>
        </div>
    </div>
</section>

<!-- 8. FINAL CTA -->
@if($heroBook)
<section class="text-center py-10 bg-surface-container-low rounded-DEFAULT px-6 shadow-sm" aria-label="Buy now">
    <div class="max-w-md mx-auto space-y-6">
        <h2 class="font-headline-lg text-display-lg-mobile md:text-headline-lg text-on-surface font-bold tracking-tight">
            Start your healthy journey today.
        </h2>
        <p class="font-body-md text-body-md text-secondary">
            {{ $heroBook->price_display }} · Instant PDF download · No gym, no starving.
        </p>
        <div class="pt-2">
            <form method="POST" action="{{ route('cart.store') }}" class="inline-block">
                @csrf
                <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                <button type="submit" class="inline-flex items-center justify-center bg-primary-container text-on-primary px-8 py-3.5 rounded-full font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm">
                    Get the ebook — {{ $heroBook->price_display }}
                </button>
            </form>
        </div>
    </div>
</section>
@endif

</div>
</div>
</div>
@endsection

@push('scripts')
<script>
function toggleFaq(id) {
    var content = document.getElementById(id + '-content');
    var icon = document.getElementById(id + '-icon');
    var btn = document.getElementById(id + '-btn');
    if (!content || !icon) return;
    var isHidden = content.classList.contains('hidden');
    if (isHidden) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
        if (btn) btn.setAttribute('aria-expanded', 'true');
    } else {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }
}
</script>
@endpush
