@extends('layouts.app')

@section('title', 'MyEbookBazaar — Simple ebooks for fitness, skills and money')
@section('meta_description', 'MyEbookBazaar sells short, practical ebooks on fitness, diet and healthy habits. Instant download, honest prices in INR and USD.')

@php
    $heroBook = $featured->first();
    $saleBook = $featured->concat($offers)->first(fn ($b) => ($b->price_quote['on_sale'] ?? false) && $b->sale_ends_at);
    $allBooksCount = $featured->count() + $offers->count();
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-4 pb-2 flex flex-col gap-5">

    {{-- 1. HERO --}}
    <section class="stitch-hero px-5 pt-6 pb-7 text-center overflow-hidden" aria-labelledby="hero-heading">
        <div class="hero-glow w-48 h-48 -top-12 -right-12" style="background: rgb(15 85 102 / 0.14)"></div>
        <div class="hero-glow w-40 h-40 top-1/3 -left-12" style="background: rgb(254 147 44 / 0.18)"></div>
        <div class="relative">
            <span class="pill-live inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-wider">
                <x-ui.icon name="zap" class="w-4 h-4" />
                @if($heroBook && $heroBook->edition) Edition {{ $heroBook->edition }} now live @else New guides now live @endif
            </span>
            <h1 id="hero-heading" class="display-lg text-teal-900 mt-3 text-balance">
                Transform your body at home without expensive gyms
            </h1>
            <p class="text-mutedtext mt-2 max-w-[320px] mx-auto text-[15px] leading-relaxed">
                No equipment needed. Download our 54-page beginner fitness and
                nutrition blueprint instantly.
            </p>

            <div class="relative w-fit mx-auto my-5">
                <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}"
                     alt="Fit at Home: Start (Edition 1) — 3D book mockup"
                     class="mockup-3d w-52 h-auto rounded-lg" fetchpriority="high">
                @if($heroBook && ($heroBook->price_quote['on_sale'] ?? false))
                <span class="off-chip absolute -bottom-2 -right-3 shadow-md">
                    Save {{ $heroBook->price_quote['discount_percent'] ?? '' }}%
                </span>
                @endif
            </div>

            <div class="flex flex-col gap-2.5 max-w-sm mx-auto">
                @if($heroBook)
                <a href="{{ route('books.show', $heroBook->slug) }}"
                   class="btn btn-primary btn-lg btn-glow w-full !flex-col !gap-0.5 !py-3">
                    <span class="flex items-center gap-1.5 uppercase tracking-wider">
                        <x-ui.icon name="zap" class="w-5 h-5" /> Get the book — {{ $heroBook->price_display }}
                    </span>
                    <span class="text-[11px] font-medium opacity-90">
                        Instant PDF download
                        @if($heroBook->price_quote['currency'] === 'INR') &bull; International $4.99 USD @endif
                    </span>
                </a>
                @else
                <a href="{{ route('books') }}" class="btn btn-primary btn-lg btn-glow w-full uppercase tracking-wider">
                    Browse ebooks
                </a>
                @endif
                <a href="#whats-inside"
                   class="btn btn-secondary w-full !border-teal-700">
                    Explore sample chapters
                </a>
            </div>
            <p class="flex items-center justify-center gap-1.5 mt-3 text-[11px] font-semibold text-mutedtext">
                <x-ui.icon name="shield" class="w-4 h-4 text-teal-700" />
                Instant access link delivered on-screen and to your inbox
            </p>
        </div>
    </section>

    {{-- 2. TRUST STRIP --}}
    <section class="card p-4" aria-label="Why buy from us">
        <ul class="flex flex-col gap-3">
            <li class="flex items-start gap-3">
                <img src="{{ asset('images/stitch/icon-instant-access.png') }}" alt="" class="trust-icon">
                <span>
                    <strong class="block text-[15px]">Instant PDF download</strong>
                    <span class="text-sm text-mutedtext">Immediate link on payment + emailed to you.</span>
                </span>
            </li>
            <li class="flex items-start gap-3">
                <img src="{{ asset('images/stitch/icon-secure-checkout.png') }}" alt="" class="trust-icon">
                <span>
                    <strong class="block text-[15px]">100% secure checkout</strong>
                    <span class="text-sm text-mutedtext">Encrypted payments via UPI, cards and netbanking.</span>
                </span>
            </li>
            <li class="flex items-start gap-3">
                <img src="{{ asset('images/stitch/icon-pdf.png') }}" alt="" class="trust-icon">
                <span>
                    <strong class="block text-[15px]">Made for Indian kitchens</strong>
                    <span class="text-sm text-mutedtext">Meal guidance around dal, roti, rice and everyday food.</span>
                </span>
            </li>
            <li class="flex items-start gap-3">
                <img src="{{ asset('images/stitch/icon-shield.png') }}" alt="" class="trust-icon">
                <span>
                    <strong class="block text-[15px]">Read anywhere</strong>
                    <span class="text-sm text-mutedtext">Crisp PDF for phone, tablet, Kindle or laptop.</span>
                </span>
            </li>
        </ul>
    </section>

    {{-- 3. FEATURED SPOTLIGHT --}}
    @if($heroBook)
    <section class="card p-5" aria-labelledby="spotlight-heading">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-saffron-700 uppercase tracking-[0.18em]">Guide of the month</span>
            @if($heroBook->edition)
            <span class="badge badge-neutral">Edition {{ $heroBook->edition }}</span>
            @endif
        </div>
        <div class="flex gap-4 items-center mt-4">
            <a href="{{ route('books.show', $heroBook->slug) }}" class="shrink-0" tabindex="-1" aria-hidden="true">
                @if($heroBook->coverUrl())
                    <img src="{{ $heroBook->coverUrl() }}" alt="" class="spotlight-cover">
                @else
                    <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}" alt="" class="spotlight-cover">
                @endif
            </a>
            <div class="min-w-0">
                <h2 id="spotlight-heading" class="display-sm text-teal-900 line-clamp-2">
                    <a href="{{ route('books.show', $heroBook->slug) }}">{{ $heroBook->title }}</a>
                </h2>
                @if($heroBook->author)
                <p class="text-sm text-mutedtext mt-1">by <strong class="text-inktext">{{ $heroBook->author }}</strong></p>
                @endif
                @if($heroBook->short_description)
                <p class="text-sm text-mutedtext font-semibold mt-1 line-clamp-2">{{ $heroBook->short_description }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center justify-between mt-4 p-3 rounded-xl bg-canvas">
            <div>
                <p class="flex items-baseline gap-2">
                    <span class="display-sm text-teal-900">{{ $heroBook->price_display }}</span>
                    @if($heroBook->price_regular_display)
                        <span class="book-card-oldprice">{{ $heroBook->price_regular_display }}</span>
                        <span class="off-chip">{{ $heroBook->price_quote['discount_percent'] ?? '' }}% off</span>
                    @endif
                </p>
                @if($heroBook->price_quote['currency'] === 'INR')
                <p class="text-[11px] text-mutedtext mt-0.5">Global checkout: $4.99 USD</p>
                @endif
            </div>
            <p class="text-right text-[11px] text-mutedtext leading-tight">
                <span class="flex items-center gap-1 justify-end font-semibold text-teal-700">
                    <x-ui.icon name="file-text" class="w-4 h-4" /> {{ $heroBook->pages ?? 54 }} pages
                </span>
                <span>Zero gear required</span>
            </p>
        </div>
        <div class="grid grid-cols-2 gap-2.5 mt-4">
            <form method="POST" action="{{ route('cart.store') }}">
                @csrf
                <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                <x-ui.button type="submit" variant="secondary" class="w-full" icon="cart">Add to cart</x-ui.button>
            </form>
            @if(settings_enabled('features.buy_now', true))
            <form method="POST" action="{{ route('books.buy-now', $heroBook->slug) }}">
                @csrf
                <x-ui.button type="submit" class="w-full btn-glow" icon="zap">Instant buy</x-ui.button>
            </form>
            @endif
        </div>
    </section>
    @endif

    {{-- 4. CATEGORY CHIPS --}}
    <section aria-labelledby="cats-heading">
        <div class="flex items-center justify-between mb-2">
            <h2 id="cats-heading" class="display-sm text-teal-900 uppercase">Browse topics</h2>
            <a href="{{ route('books') }}" class="text-sm font-bold text-saffron-700 hover:underline">View all</a>
        </div>
        <div class="chip-scroll" role="list">
            <a href="{{ route('books') }}" class="chip chip-active" role="listitem" @if(!request('category')) aria-current="true" @endif>
                All guides ({{ $allBooksCount }})
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('books', ['category' => $cat->slug]) }}" class="chip" role="listitem">
                {{ $cat->name }}
            </a>
            @endforeach
        </div>
    </section>

    {{-- 5. WHAT'S INSIDE --}}
    @if($heroBook)
    <section id="whats-inside" aria-labelledby="inside-heading" class="scroll-mt-24">
        <p class="text-[11px] text-saffron-700 font-bold uppercase tracking-[0.18em]">Unpack the blueprint</p>
        <h2 id="inside-heading" class="display-md text-teal-900 uppercase mt-1">What's inside Edition 1</h2>
        <p class="text-sm text-mutedtext mt-1">Practical, no-fluff instructions for daily consistency.</p>
        <div class="flex flex-col gap-4 mt-4 md:grid md:grid-cols-3">
            <article class="card p-4">
                <img src="{{ asset('images/stitch/illustration-workout.jpg') }}" alt="Home workout illustration" class="module-art" loading="lazy">
                <p class="flex items-center gap-2 mt-3">
                    <span class="badge badge-info">Module 01</span>
                    <span class="text-[11px] font-semibold text-mutedtext">22 pages</span>
                </p>
                <h3 class="font-bold text-lg mt-1.5">4-week zero-gym routine</h3>
                <p class="text-sm text-mutedtext mt-1">Step-by-step illustrated guides for full-body tone, posture and endurance — no weights or machines.</p>
            </article>
            <article class="card p-4">
                <img src="{{ asset('images/stitch/illustration-mealplan.jpg') }}" alt="Nutrition and meal plan illustration" class="module-art" loading="lazy">
                <p class="flex items-center gap-2 mt-3">
                    <span class="badge badge-sale">Module 02</span>
                    <span class="text-[11px] font-semibold text-mutedtext">18 pages</span>
                </p>
                <h3 class="font-bold text-lg mt-1.5">Practical nutrition &amp; meal plans</h3>
                <p class="text-sm text-mutedtext mt-1">Kitchen-friendly grocery checklists, macro breakdowns and fast recipes for busy routines.</p>
            </article>
            <article class="card p-4">
                <img src="{{ asset('images/stitch/illustration-progress.jpg') }}" alt="Habit tracking illustration" class="module-art" loading="lazy">
                <p class="flex items-center gap-2 mt-3">
                    <span class="badge badge-success">Module 03</span>
                    <span class="text-[11px] font-semibold text-mutedtext">14 pages</span>
                </p>
                <h3 class="font-bold text-lg mt-1.5">Habit &amp; measurement trackers</h3>
                <p class="text-sm text-mutedtext mt-1">Printable weekly sheets for weight, waist, energy and hydration.</p>
            </article>
        </div>
    </section>
    @endif

    {{-- 6. COMBOS --}}
    @if($combos->isNotEmpty())
    <section aria-labelledby="combos-heading">
        <div class="flex items-end justify-between gap-4 mb-3">
            <div>
                <h2 id="combos-heading" class="display-md text-teal-900 uppercase">Combos</h2>
                <p class="text-sm text-mutedtext">Bundles of ebooks at one honest price.</p>
            </div>
            <x-ui.button variant="ghost" href="{{ route('combos') }}" size="sm">View all</x-ui.button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($combos as $combo)
            <x-ui.card class="p-5 flex flex-col">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <x-ui.badge>Combo</x-ui.badge>
                    @if($combo->price_quote['on_sale'])
                    <x-ui.badge type="sale">Sale</x-ui.badge>
                    @endif
                </div>
                <h3 class="font-display text-2xl font-bold uppercase tracking-wide">{{ $combo->title }}</h3>
                @if($combo->description)
                <p class="text-mutedtext text-sm mt-1 line-clamp-2">{{ $combo->description }}</p>
                @endif
                <div class="mt-auto pt-4">
                    <p class="flex items-baseline gap-2">
                        <span class="price text-xl">{{ $combo->price_display }}</span>
                        @if($combo->price_regular_display)
                        <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                        @endif
                    </p>
                    <x-ui.button :href="route('combos.show', $combo->slug)" size="sm" class="w-full mt-3">View combo</x-ui.button>
                </div>
            </x-ui.card>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 7. OFFERS --}}
    @if($offers->isNotEmpty())
    <section aria-labelledby="offers-heading">
        <div class="flex items-end justify-between gap-4 mb-3">
            <div>
                <h2 id="offers-heading" class="display-md text-teal-900 uppercase">On sale now</h2>
                <p class="text-sm text-mutedtext">Live sale prices — only while they last.</p>
            </div>
            <x-ui.button variant="ghost" href="{{ route('offers') }}" size="sm">All offers</x-ui.button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($offers as $book)
            <x-ui.book-card :title="$book->title"
                            :author="$book->author"
                            :description="$book->short_description"
                            :price="$book->price_display"
                            :old-price="$book->price_regular_display"
                            :on-sale="true"
                            :sale-ends-at="$book->sale_ends_at?->toIso8601String()"
                            :cover="$book->coverUrl()"
                            action-url="{{ route('books.show', $book->slug) }}"
                            action-label="View details" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- 8. FLASH-SALE BANNER (only when a real sale is live) --}}
    @if($saleBook)
    <section class="sale-banner p-5 text-center" aria-labelledby="sale-heading">
        <img src="{{ asset('images/stitch/banner-promo.jpg') }}" alt="" class="banner-bg" loading="lazy" aria-hidden="true">
        <div class="banner-shade" aria-hidden="true"></div>
        <div class="relative">
            <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.18em]" style="color: var(--color-saffron-500)">
                <x-ui.icon name="clock" class="w-4 h-4" /> Limited period flash sale
            </p>
            <h2 id="sale-heading" class="display-md text-white uppercase mt-1.5">
                Grab {{ $saleBook->edition ? 'Edition '.$saleBook->edition : 'it' }} for {{ $saleBook->price_display }}
            </h2>
            <p class="text-sm mt-1" style="color: #d5e3fc">
                Regular {{ $saleBook->price_regular_display }} resumes when the sale ends.
            </p>
            <div class="flex justify-center mt-4">
                <x-ui.countdown-boxes :ends-at="$saleBook->sale_ends_at->toIso8601String()" />
            </div>
            <a href="{{ route('books.show', $saleBook->slug) }}"
               class="btn btn-primary btn-lg btn-glow w-full mt-4 uppercase tracking-wider">
                Claim discount now &bull; {{ $saleBook->price_display }}
            </a>
        </div>
    </section>
    @endif

    {{-- 9. ADMIN BANNERS --}}
    @if($banners->isNotEmpty())
    <section aria-label="Offers">
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($banners as $banner)
            <div class="card overflow-hidden">
                @if($banner->link_url)
                <a href="{{ $banner->link_url }}" class="block">
                @endif
                    <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="w-full h-auto" loading="lazy">
                @if($banner->link_url)
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 10. FAQ --}}
    <section aria-labelledby="faq-heading">
        <p class="text-[11px] text-saffron-700 font-bold uppercase tracking-[0.18em]">Quick answers</p>
        <h2 id="faq-heading" class="display-md text-teal-900 uppercase mt-1 mb-3">Frequently asked questions</h2>
        <x-ui.card class="px-5 py-2">
            <x-ui.accordion :items="[
                ['q' => 'How do I receive the ebook?', 'a' => 'An instant download link appears immediately after successful payment, and a backup copy is emailed to you.'],
                ['q' => 'What format is it in?', 'a' => 'High-resolution universal PDF. It opens on phones, tablets, Kindle PDF viewers and web browsers.'],
                ['q' => 'Can complete beginners do this?', 'a' => 'Yes. Edition 1 is calibrated for novices with zero gym access, with low-impact modifications and gradual weekly progressions.'],
                ['q' => 'Can I get a refund?', 'a' => 'All sales are final because ebooks are digital products. Please read the book description carefully before you pay.'],
            ]" />
        </x-ui.card>
    </section>
</div>
@endsection
