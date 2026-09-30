@extends('layouts.app')

@section('title', 'MyEbookBazaar — Curated digital ebooks, instant PDF download')
@section('meta_description', 'MyEbookBazaar sells short, practical ebooks on fitness, skills and money. Instant PDF download, honest prices in INR and USD.')
@section('canonical', route('home'))

{{-- Schema.org: Organization + WebSite for the home page. --}}
@section('jsonld')
{!! $seo->script($seo->organizationJsonLd()) !!}
@endsection

@php
    $heroBook = $featured->first();
    $saleBook = $featured->concat($offers)->first(fn ($b) => ($b->price_quote['on_sale'] ?? false) && $b->sale_ends_at);
    $downloadLimit = max(1, (int) settings('downloads.limit', 5));
    $heroCover = $heroBook && $heroBook->coverUrl()
        ? $heroBook->coverUrl()
        : ($heroBook && str_contains($heroBook->slug, 'fit-at-home') ? asset('images/stitch/book-mockup-3d.jpg') : null);
@endphp

@section('content')
<div class="max-w-container mx-auto px-4">

    {{-- 1. HERO --}}
    <section class="pt-10 pb-8 md:pt-14 md:pb-10" aria-labelledby="hero-heading">
        <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-10 items-center">
            <div class="text-center lg:text-left">
                <p class="st-eyebrow justify-center lg:justify-start">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#F5A623]" aria-hidden="true"></span>
                    Curated direct catalog
                </p>
                <h1 id="hero-heading" class="st-h text-[38px] md:text-[52px] mt-4">
                    Expand Your Horizon with<br>
                    <span class="text-[#D97706]">Curated Digital Books</span>
                </h1>
                <p class="st-muted text-[16px] md:text-[17px] leading-relaxed mt-4 max-w-xl mx-auto lg:mx-0">
                    Handpicked nonfiction, fitness, tech, and business guides designed for
                    immediate implementation. Instant secure downloads in high-resolution PDF.
                </p>

                {{-- Hero search: real categories --}}
                <form method="GET" action="{{ route('books') }}" role="search" class="st-hero-search mt-7 max-w-xl mx-auto lg:mx-0" aria-label="Search the catalog">
                    <div class="grow-field">
                        <x-ui.icon name="search" class="w-5 h-5 text-[#8A98A5] shrink-0" />
                        <label for="hero-q" class="sr-only">Search title, tech stack, or topic</label>
                        <input id="hero-q" name="q" type="search" placeholder="Search title, tech stack, or topic…" autocomplete="off">
                    </div>
                    <label for="hero-cat" class="sr-only">Category</label>
                    <select id="hero-cat" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="st-btn st-btn-teal !min-h-[48px]">
                        Explore <x-ui.icon name="arrow-right" class="w-4 h-4" />
                    </button>
                </form>

                <div class="st-trust-row mt-6">
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="check-circle" class="w-4 h-4 text-[#0B3D4A]" /> Verified instant PDF delivery</span>
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="lock" class="w-4 h-4 text-[#0B3D4A]" /> Secure Razorpay checkout</span>
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="zap" class="w-4 h-4 text-[#0B3D4A]" /> No subscription required</span>
                </div>
            </div>

            {{-- Featured mini-card (real book, real price) --}}
            @if($heroBook)
            <div class="st-card p-5 max-w-sm w-full mx-auto lg:mx-0 lg:justify-self-end">
                <div class="flex items-center justify-between">
                    <p class="st-eyebrow">Featured</p>
                    @if($heroBook->edition)
                    <span class="text-[11px] font-bold text-[#5C6B75] uppercase tracking-wider">Edition {{ $heroBook->edition }}</span>
                    @endif
                </div>
                <div class="flex gap-4 mt-3">
                    <a href="{{ route('books.show', $heroBook->slug) }}" class="w-20 shrink-0 rounded-xl overflow-hidden shadow-md bg-[#0B3D4A]" tabindex="-1" aria-hidden="true">
                        @if($heroCover)
                            <img src="{{ $heroCover }}" alt="" class="w-full h-auto object-cover" fetchpriority="high">
                        @endif
                    </a>
                    <div class="min-w-0">
                        <h2 class="font-extrabold text-[17px] leading-snug">
                            <a href="{{ route('books.show', $heroBook->slug) }}" class="hover:text-[#0B3D4A]">{{ $heroBook->title }}</a>
                        </h2>
                        @if($heroBook->author)
                        <p class="text-sm st-muted mt-0.5">by {{ $heroBook->author }}</p>
                        @endif
                        <p class="mt-1"><x-ui.rating-stars :average="$heroBook->rating_average ?? null" :count="$heroBook->ratings_count ?? 0" star-class="w-3.5 h-3.5" /></p>
                        <p class="flex items-baseline gap-2 mt-1.5">
                            <span class="text-[22px] font-extrabold text-[#D97706]">{{ $heroBook->price_display }}</span>
                            @if($heroBook->price_regular_display)
                            <span class="book-card-oldprice text-sm">{{ $heroBook->price_regular_display }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5 mt-4">
                    <a href="{{ route('books.show', $heroBook->slug) }}" class="st-btn st-btn-white st-btn-sm">View details</a>
                    <form method="POST" action="{{ route('cart.store') }}">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                        <button type="submit" class="st-btn st-btn-amber st-btn-sm w-full">
                            <x-ui.icon name="cart" class="w-4 h-4" /> Add to cart
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </section>

    {{-- 2. FEATURED SPOTLIGHT PANEL (dark teal) --}}
    @if($heroBook)
    <section class="st-darkpanel p-6 md:p-10 mt-2" aria-labelledby="spotlight-heading">
        <div class="relative z-10 grid md:grid-cols-[220px_1fr] gap-8 items-center">
            <div class="relative mx-auto md:mx-0 w-44 md:w-full">
                @if($heroCover)
                    <img src="{{ $heroCover }}" alt="Cover of {{ $heroBook->title }}"
                         class="w-full h-auto rounded-2xl shadow-2xl" loading="lazy">
                @endif
                @if($heroBook->price_quote['on_sale'] ?? false)
                <span class="off-chip absolute -bottom-3 left-1/2 -translate-x-1/2 shadow-lg whitespace-nowrap">
                    Save {{ $heroBook->price_quote['discount_percent'] ?? '' }}%
                </span>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-[#F5A623]">
                    Featured{{ $heroBook->pages ? ' • '.$heroBook->pages.' pages edition' : '' }}
                    @if($heroBook->category)
                    <span class="text-white/60 normal-case tracking-normal font-semibold"> · {{ $heroBook->category->name }}</span>
                    @endif
                </p>
                <h2 id="spotlight-heading" class="st-h !text-white text-[30px] md:text-[38px] mt-2">{{ $heroBook->title }}</h2>
                @if($heroBook->author)
                <p class="text-white/70 text-[15px] mt-2">Authored by <strong class="text-white">{{ $heroBook->author }}</strong></p>
                @endif
                @if($heroBook->short_description)
                <p class="text-white/75 text-[15px] leading-relaxed mt-3 max-w-2xl">{{ $heroBook->short_description }}</p>
                @endif

                <div class="grid sm:grid-cols-3 gap-3 mt-6">
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-3.5 flex items-center gap-3">
                        <x-ui.icon name="clock" class="w-6 h-6 text-[#F5A623] shrink-0" />
                        <p class="text-[13px] font-semibold leading-snug">Instant PDF download<br><span class="text-white/60 font-normal">right after payment</span></p>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-3.5 flex items-center gap-3">
                        <x-ui.icon name="download" class="w-6 h-6 text-[#F5A623] shrink-0" />
                        <p class="text-[13px] font-semibold leading-snug">{{ $downloadLimit }} lifetime downloads<br><span class="text-white/60 font-normal">per purchased ebook</span></p>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-3.5 flex items-center gap-3">
                        <x-ui.icon name="book" class="w-6 h-6 text-[#F5A623] shrink-0" />
                        <p class="text-[13px] font-semibold leading-snug">Read anywhere<br><span class="text-white/60 font-normal">phone, tablet, Kindle, laptop</span></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-4 mt-7">
                    <div>
                        <p class="flex items-baseline gap-2.5">
                            <span class="text-[34px] font-extrabold text-[#F5A623] leading-none">{{ $heroBook->price_display }}</span>
                            @if($heroBook->price_regular_display)
                            <span class="text-lg text-white/50 line-through">{{ $heroBook->price_regular_display }}</span>
                            <span class="off-chip">{{ $heroBook->price_quote['discount_percent'] ?? '' }}% off</span>
                            @endif
                        </p>
                        <p class="text-[12px] text-white/60 mt-1">Instant PDF &bull; DRM-free &bull; Lifetime library access</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto">
                        <a href="{{ route('books.show', $heroBook->slug) }}" class="st-btn st-btn-white">View details</a>
                        <form method="POST" action="{{ route('cart.store') }}" class="sm:w-auto w-full">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                            <button type="submit" class="st-btn st-btn-amber w-full">
                                <x-ui.icon name="zap" class="w-5 h-5" /> Get instant copy
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- 3. EXPLORE BY DISCIPLINE --}}
    <section class="mt-14" aria-labelledby="disciplines-heading">
        <p class="st-eyebrow">Curated catalog</p>
        <div class="flex items-end justify-between gap-4 mt-1.5">
            <h2 id="disciplines-heading" class="st-h text-[28px] md:text-[34px]">Explore by Discipline</h2>
            <a href="{{ route('books') }}" class="text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4 whitespace-nowrap">View all</a>
        </div>
        <p class="st-muted text-sm mt-1.5 max-w-2xl">Browse by category. Pick the field you want to master today.</p>

        <div class="chip-scroll mt-5" role="list" aria-label="Filter by category">
            <a href="{{ route('books') }}" class="chip chip-active" role="listitem">All Editions</a>
            @foreach($categories as $cat)
            <a href="{{ route('books', ['category' => $cat->slug]) }}" class="chip" role="listitem">{{ $cat->name }}</a>
            @endforeach
        </div>

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
                    <h3 class="st-book-title line-clamp-2">
                        <a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a>
                    </h3>
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
        @else
        <div class="st-card st-empty mt-6">
            <x-ui.empty-state icon="book" title="Catalog coming soon"
                text="New guides are being added. Check back shortly." />
        </div>
        @endif
    </section>

    {{-- 4. COMBOS --}}
    @if($combos->isNotEmpty())
    <section class="mt-14" aria-labelledby="combos-heading">
        <div class="flex items-end justify-between gap-4 mb-5">
            <div>
                <p class="st-eyebrow">Bundle &amp; save</p>
                <h2 id="combos-heading" class="st-h text-[28px] md:text-[34px] mt-1">Combos</h2>
                <p class="st-muted text-sm mt-1">Bundles of ebooks at one honest price.</p>
            </div>
            <a href="{{ route('combos') }}" class="text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4 whitespace-nowrap">View all</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($combos as $combo)
            <div class="st-card p-6 flex flex-col">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <x-ui.badge>Combo</x-ui.badge>
                    @if($combo->price_quote['on_sale'])
                    <x-ui.badge type="sale">Sale</x-ui.badge>
                    @endif
                </div>
                <h3 class="font-extrabold text-xl tracking-tight">{{ $combo->title }}</h3>
                @if($combo->description)
                <p class="st-muted text-sm mt-1.5 line-clamp-2">{{ $combo->description }}</p>
                @endif
                <p class="text-sm st-muted mt-2">{{ $combo->combo_books->count() }} {{ Str::plural('ebook', $combo->combo_books->count()) }} included</p>
                <div class="mt-auto pt-5">
                    <p class="flex items-baseline gap-2">
                        <span class="price text-2xl">{{ $combo->price_display }}</span>
                        @if($combo->price_regular_display)
                        <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                        @endif
                    </p>
                    <a href="{{ route('combos.show', $combo->slug) }}" class="st-btn st-btn-teal st-btn-sm w-full mt-3">View combo</a>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 5. OFFERS --}}
    @if($offers->isNotEmpty())
    <section class="mt-14" aria-labelledby="offers-heading">
        <div class="flex items-end justify-between gap-4 mb-5">
            <div>
                <p class="st-eyebrow">Limited period</p>
                <h2 id="offers-heading" class="st-h text-[28px] md:text-[34px] mt-1">On sale now</h2>
                <p class="st-muted text-sm mt-1">Live sale prices — only while they last.</p>
            </div>
            <a href="{{ route('offers') }}" class="text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4 whitespace-nowrap">All offers</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($offers as $book)
            <x-ui.book-card :title="$book->title"
                            :author="$book->author"
                            :description="$book->short_description"
                            :price="$book->price_display"
                            :old-price="$book->price_regular_display"
                            :on-sale="true"
                            :sale-ends-at="$book->sale_ends_at?->toIso8601String()"
                            :cover="$book->coverUrl()"
                            :rating-average="$book->rating_average ?? null"
                            :rating-count="$book->ratings_count ?? 0"
                            action-url="{{ route('books.show', $book->slug) }}"
                            action-label="View details" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- 6. FLASH-SALE BANNER (only when a real sale is live) --}}
    @if($saleBook)
    <section class="st-darkpanel p-6 md:p-10 mt-14 text-center" aria-labelledby="sale-heading">
        <div class="relative z-10">
            <p class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-[0.18em] text-[#F5A623]">
                <x-ui.icon name="clock" class="w-4 h-4" /> Limited period flash sale
            </p>
            <h2 id="sale-heading" class="st-h !text-white text-[28px] md:text-[36px] mt-2">
                Grab {{ $saleBook->edition ? 'Edition '.$saleBook->edition : 'it' }} for {{ $saleBook->price_display }}
            </h2>
            <p class="text-sm mt-2 text-white/70">
                Regular {{ $saleBook->price_regular_display }} resumes when the sale ends.
            </p>
            <div class="flex justify-center mt-5">
                <x-ui.countdown-boxes :ends-at="$saleBook->sale_ends_at->toIso8601String()" />
            </div>
            <a href="{{ route('books.show', $saleBook->slug) }}" class="st-btn st-btn-amber mt-6 w-full sm:w-auto">
                Claim discount now • {{ $saleBook->price_display }}
            </a>
        </div>
    </section>
    @endif

    {{-- 7. ADMIN BANNERS --}}
    @if($banners->isNotEmpty())
    <section class="mt-14" aria-label="Announcements">
        <div class="grid gap-5 md:grid-cols-2">
            @foreach($banners as $banner)
            <div class="st-card overflow-hidden">
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

    {{-- 8. HOW IT WORKS --}}
    <section class="mt-16" aria-labelledby="how-heading">
        <div class="text-center max-w-2xl mx-auto">
            <p class="st-eyebrow justify-center">Simple frictionless model</p>
            <h2 id="how-heading" class="st-h text-[28px] md:text-[36px] mt-2">How MyEbookBazaar Works</h2>
            <p class="st-muted text-[15px] mt-2">No subscriptions, no confusing tokens. Pick what you want, unlock immediate ownership.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-5 mt-8">
            <div class="st-step">
                <span class="st-step-num">1</span>
                <h3 class="font-extrabold text-lg">Browse &amp; Select</h3>
                <p class="st-muted text-sm mt-2 leading-relaxed">Explore curated practical ebooks. Inspect the description, page counts and edition details before committing.</p>
            </div>
            <div class="st-step">
                <span class="st-step-num">2</span>
                <h3 class="font-extrabold text-lg">Instant Secure Checkout</h3>
                <p class="st-muted text-sm mt-2 leading-relaxed">Pay safely via Razorpay — UPI, cards, netbanking and popular wallets, with a server-verified order total.</p>
                <p class="text-[12px] font-bold text-[#0B3D4A] mt-3 inline-flex items-center gap-1.5"><x-ui.icon name="lock" class="w-4 h-4" /> Encrypted checkout</p>
            </div>
            <div class="st-step">
                <span class="st-step-num">3</span>
                <h3 class="font-extrabold text-lg">Lifetime Digital Access</h3>
                <p class="st-muted text-sm mt-2 leading-relaxed">A download-ready email arrives right after payment, and your ebooks stay saved permanently in your personal reader library.</p>
                <p class="text-[12px] font-bold text-[#0B3D4A] mt-3 inline-flex items-center gap-1.5"><x-ui.icon name="download" class="w-4 h-4" /> {{ settings('downloads.limit', 5) }} downloads per ebook</p>
            </div>
        </div>
    </section>

    {{-- 9. FREE CLUB (email prefill → register; no fake subscriber counts) --}}
    <section class="st-darkpanel p-6 md:p-10 mt-16" aria-labelledby="club-heading">
        <div class="relative z-10 grid md:grid-cols-2 gap-8 items-center">
            <div>
                <p class="inline-block text-[11px] font-extrabold uppercase tracking-[0.14em] bg-[#F5A623] text-[#3A2503] rounded-full px-3.5 py-1.5">Reader club</p>
                <h2 id="club-heading" class="st-h !text-white text-[26px] md:text-[34px] mt-4">Get Drops, Discounts &amp; Free Starter Guides</h2>
                <p class="text-white/70 text-[15px] mt-3 leading-relaxed max-w-md">New guides and honest discounts — straight to your inbox. No spam, unsubscribe anytime.</p>
            </div>
            <form method="GET" action="{{ route('register') }}" class="flex flex-col sm:flex-row gap-3">
                <label for="club-email" class="sr-only">Email address</label>
                <div class="relative flex-1">
                    <x-ui.icon name="mail" class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-white/50" />
                    <input id="club-email" name="email" type="email" required placeholder="Enter your email address…"
                           class="w-full rounded-2xl bg-white/10 border border-white/20 text-white placeholder:text-white/50 pl-12 pr-4 min-h-[54px] text-[15px] focus:outline-none focus:border-[#F5A623]">
                </div>
                <button type="submit" class="st-btn st-btn-amber whitespace-nowrap">Join Free Club</button>
            </form>
        </div>
    </section>

    {{-- 10. FAQ --}}
    <section class="mt-16 mb-4 max-w-3xl mx-auto" aria-labelledby="faq-heading">
        <div class="text-center">
            <p class="st-eyebrow justify-center">Quick answers</p>
            <h2 id="faq-heading" class="st-h text-[28px] md:text-[34px] mt-2 mb-6">Frequently asked questions</h2>
        </div>
        <div class="st-card px-6 py-3">
            <x-ui.accordion :items="[
                ['q' => 'How do I receive the ebook?', 'a' => 'An instant download link appears immediately after successful payment, and a backup copy is emailed to you.'],
                ['q' => 'What format is it in?', 'a' => 'High-resolution universal PDF. It opens on phones, tablets, Kindle PDF viewers and web browsers.'],
                ['q' => 'How many times can I download?', 'a' => 'Each purchased ebook includes '.$downloadLimit.' lifetime downloads. Links are signed and expire 10 minutes after they are generated.'],
                ['q' => 'Can I get a refund?', 'a' => 'All sales are final because ebooks are digital products. Please read the book description carefully before you pay.'],
            ]" />
        </div>
        <p class="text-center mt-5">
            <a href="{{ route('faq') }}" class="text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4">Read all FAQs</a>
        </p>
    </section>
</div>
@endsection
