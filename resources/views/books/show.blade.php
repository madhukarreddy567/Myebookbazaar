@extends('layouts.app')

@section('title', $book->title.' — MyEbookBazaar')
@section('meta_description', $book->short_description ?? $book->title)

@php
    // Real uploaded cover wins; the Fit at Home book falls back to the
    // Stitch 3D mockup; anything else keeps the CSS placeholder.
    $coverSrc = $book->coverUrl()
        ?: (str_contains($book->slug, 'fit-at-home') ? asset('images/stitch/book-mockup-3d.jpg') : null);
    $onSale = $book->price_quote['on_sale'] ?? false;
    $discountPct = $book->price_quote['discount_percent'] ?? null;
@endphp

@section('og_image', $coverSrc ?? asset('images/stitch/logo.jpg'))
@section('canonical', route('books.show', $book->slug))

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
            <div class="card overflow-hidden md:sticky md:top-24 bg-teal-900">
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
               class="text-sm font-bold text-teal-700 hover:underline">{{ $book->category->name }}</a>
            @endif
            <h1 class="display-lg text-teal-900 mt-1.5 text-balance">{{ $book->title }}</h1>
            @if($book->subtitle)
            <p class="text-lg text-mutedtext mt-1.5">{{ $book->subtitle }}</p>
            @endif

            @if($book->author)
            <p class="flex items-center gap-2.5 mt-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full font-display font-extrabold text-[13px] tracking-wide shrink-0" style="background: var(--color-teal-800); color: #fff" aria-hidden="true">HMKR</span>
                <span>
                    <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-mutedtext">Author</span>
                    <strong class="text-inktext">{{ $book->author }}</strong>
                </span>
            </p>
            @endif

            {{-- Price block --}}
            <div class="card p-5 mt-5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="display-md text-teal-900">{{ $book->price_display }}</span>
                    @if($book->price_regular_display)
                        <span class="book-card-oldprice text-lg">{{ $book->price_regular_display }}</span>
                        @if($discountPct)<span class="off-chip">{{ $discountPct }}% off</span>@endif
                    @endif
                </div>
                @if($onSale && $book->sale_ends_at)
                <div class="sale-banner mt-4 p-4">
                    <div class="relative flex flex-col items-center text-center gap-2.5">
                        <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.18em]" style="color: var(--color-saffron-500)">
                            <x-ui.icon name="clock" class="w-4 h-4" /> Sale ends in
                        </p>
                        <x-ui.countdown-boxes :ends-at="$book->sale_ends_at->toIso8601String()" />
                    </div>
                </div>
                @endif
                <p class="text-sm text-mutedtext mt-3">
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
                        <x-ui.button type="submit" variant="secondary" size="lg" class="w-full" icon="cart">Add to cart</x-ui.button>
                    </form>
                    @if(settings_enabled('features.buy_now', true))
                    <form method="POST" action="{{ route('books.buy-now', $book->slug) }}" class="flex-1">
                        @csrf
                        <x-ui.button type="submit" size="lg" class="w-full btn-glow" icon="zap">Get instant access</x-ui.button>
                    </form>
                    @endif
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-teal-700 shrink-0" /> Instant PDF download after payment</li>
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-teal-700 shrink-0" /> Read on phone, tablet or computer</li>
                    <li class="flex gap-2 items-start"><x-ui.icon name="check" class="w-5 h-5 text-teal-700 shrink-0" /> Secure payment through Razorpay</li>
                </ul>
            </div>

            {{-- Details row --}}
            <dl class="flex flex-wrap gap-x-5 gap-y-1.5 mt-4 text-sm text-mutedtext">
                @if($book->pages)<div><dt class="sr-only">Pages</dt><dd><strong class="text-inktext">{{ $book->pages }}</strong> pages</dd></div>@endif
                <div><dt class="sr-only">Format</dt><dd><strong class="text-inktext">PDF</strong> ebook</dd></div>
                @if($book->language)<div><dt class="sr-only">Language</dt><dd><strong class="text-inktext">{{ $book->language }}</strong></dd></div>@endif
                @if($book->edition)<div><dt class="sr-only">Edition</dt><dd>Edition <strong class="text-inktext">{{ $book->edition }}</strong></dd></div>@endif
            </dl>

            {{-- No-refund notice --}}
            <div class="rounded-2xl p-4 mt-4 text-sm" style="background: var(--color-saffron-100)">
                <strong>All sales are final.</strong> Ebooks are digital products and cannot be returned.
                Please read the description carefully before you buy.
                <a href="{{ route('no-refund') }}" class="underline font-semibold">Read the no-refund policy</a>.
            </div>
        </div>
    </div>

    {{-- Description --}}
    @if($book->description)
    <section class="mt-10 max-w-3xl" aria-labelledby="desc-heading">
        <p class="text-[11px] text-saffron-600 font-bold uppercase tracking-[0.18em]">What's inside</p>
        <h2 id="desc-heading" class="display-md text-teal-900 uppercase mt-1">About this ebook</h2>
        <div class="stripe-divider mt-3 mb-5" aria-hidden="true"></div>
        <div class="prose-like text-inktext leading-relaxed space-y-4">
            {!! nl2br(e($book->description)) !!}
        </div>
    </section>
    @endif

    {{-- Health disclaimer --}}
    <aside class="disclaimer-box mt-8 max-w-3xl text-sm" aria-label="Health disclaimer">
        <strong>Health disclaimer.</strong> This ebook shares general fitness and nutrition
        information for healthy adults. It is not medical advice. If you have a health
        condition, are pregnant, or take medication, talk to your doctor before changing
        your diet or exercise routine.
    </aside>

    {{-- Related books --}}
    @if($related->isNotEmpty())
    <section class="mt-12" aria-labelledby="related-heading">
        <h2 id="related-heading" class="display-md text-teal-900 uppercase mb-4">You may also like</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($related as $rel)
            <x-ui.book-card :title="$rel->title"
                            :author="$rel->author"
                            :description="$rel->short_description"
                            :price="$rel->price_display"
                            :old-price="$rel->price_regular_display"
                            :on-sale="$rel->price_quote['on_sale']"
                            :sale-ends-at="$rel->sale_ends_at?->toIso8601String()"
                            :cover="$rel->coverUrl()"
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
                <span class="font-display text-2xl font-extrabold text-teal-900">{{ $book->price_display }}</span>
                @if($book->price_regular_display)
                    <span class="book-card-oldprice text-sm">{{ $book->price_regular_display }}</span>
                @endif
            </p>
            <p class="text-[11px] text-mutedtext truncate">{{ $book->title }}</p>
        </div>
        <div class="flex-1"></div>
        <form method="POST" action="{{ route('cart.store') }}">
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            <x-ui.button type="submit" class="btn-glow uppercase tracking-wide">Buy now</x-ui.button>
        </form>
    </div>
</div>
@endsection
