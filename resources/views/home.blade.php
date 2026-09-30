@extends('layouts.app')

@section('title', 'MyEbookBazaar — Fit at Home: Start (Edition 1)')
@section('meta_description', 'Fit at Home: Start (Edition 1) — the simple home fitness & diet guide. 54 pages, instant PDF download. ₹149 in India, $4.99 worldwide.')
@section('canonical', route('home'))

{{-- Schema.org: Organization + WebSite for the home page. --}}
@section('jsonld')
{!! $seo->script($seo->organizationJsonLd()) !!}
@endsection

@php
    $heroBook = $featured->first();
    $downloadLimit = max(1, (int) settings('downloads.limit', 5));
    $heroCover = $heroBook?->displayCoverUrl();
@endphp

@section('content')
<div class="max-w-container mx-auto px-4">

    {{-- HERO: one book, one clear action --}}
    <section class="st-hero pt-10 pb-12 md:pt-16 md:pb-16" aria-labelledby="hero-heading">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center max-w-5xl mx-auto">
            {{-- Cover --}}
            <div class="mx-auto w-56 md:w-72">
                @if($heroBook)
                <a href="{{ route('books.show', $heroBook->slug) }}" tabindex="-1" aria-hidden="true"
                   class="block rounded-2xl overflow-hidden shadow-raised bg-[#0B3D4A]">
                    @if($heroCover)
                        <img src="{{ $heroCover }}" alt="" class="w-full h-auto" fetchpriority="high">
                    @endif
                </a>
                @endif
            </div>

            {{-- Pitch --}}
            <div class="text-center lg:text-left">
                <p class="st-eyebrow justify-center lg:justify-start">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#F5A623]" aria-hidden="true"></span>
                    Edition 1 · 54 pages
                </p>
                <h1 id="hero-heading" class="st-h text-[36px] md:text-[48px] mt-3 leading-tight">
                    @if($heroBook)
                        {{ $heroBook->title }}
                    @else
                        Fit at Home: Start
                    @endif
                </h1>
                <p class="st-muted text-[16px] md:text-[17px] leading-relaxed mt-4 max-w-lg mx-auto lg:mx-0">
                    The simple home fitness &amp; diet guide for total wellness.
                    No gym. No starving. Just 20 minutes a day with real Indian food.
                </p>

                @if($heroBook)
                <p class="flex items-baseline justify-center lg:justify-start gap-3 mt-5">
                    <span class="text-[34px] font-extrabold text-[#D97706]">{{ $heroBook->price_display }}</span>
                    @if($heroBook->price_regular_display)
                    <span class="text-lg text-mutedtext line-through">{{ $heroBook->price_regular_display }}</span>
                    @endif
                </p>

                <div class="flex flex-col sm:flex-row gap-3 mt-6 justify-center lg:justify-start">
                    <form method="POST" action="{{ route('cart.store') }}">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
                        <button type="submit" class="st-btn st-btn-amber st-btn-lg w-full sm:w-auto">
                            <x-ui.icon name="cart" class="w-5 h-5" /> Buy now
                        </button>
                    </form>
                    <a href="{{ route('books.show', $heroBook->slug) }}" class="st-btn st-btn-white st-btn-lg">
                        View details
                    </a>
                </div>
                @endif

                <div class="flex flex-wrap justify-center lg:justify-start gap-x-5 gap-y-2 mt-6 text-sm st-muted">
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="check-circle" class="w-4 h-4 text-[#0B3D4A]" /> Instant PDF download</span>
                    <span class="inline-flex items-center gap-1.5"><x-ui.icon name="lock" class="w-4 h-4 text-[#0B3D4A]" /> Secure checkout</span>
                </div>
            </div>
        </div>
    </section>

    {{-- WHY: three simple reasons --}}
    <section class="py-10 md:py-14" aria-labelledby="why-heading">
        <h2 id="why-heading" class="st-h text-[26px] md:text-[32px] text-center">Why Fit at Home?</h2>
        <div class="grid sm:grid-cols-3 gap-5 mt-8 max-w-4xl mx-auto">
            <div class="st-card p-6 text-center">
                <p class="w-12 h-12 mx-auto rounded-2xl bg-tint-teal flex items-center justify-center">
                    <x-ui.icon name="zap" class="w-6 h-6 text-[#0B3D4A]" />
                </p>
                <h3 class="font-extrabold text-[17px] mt-3">20 minutes a day</h3>
                <p class="text-sm st-muted mt-1.5">Short home workouts. No equipment, no gym membership.</p>
            </div>
            <div class="st-card p-6 text-center">
                <p class="w-12 h-12 mx-auto rounded-2xl bg-tint-teal flex items-center justify-center">
                    <x-ui.icon name="leaf" class="w-6 h-6 text-[#0B3D4A]" />
                </p>
                <h3 class="font-extrabold text-[17px] mt-3">Real Indian food</h3>
                <p class="text-sm st-muted mt-1.5">Dal, roti, eggs — a diet plan built on ghar ka khana.</p>
            </div>
            <div class="st-card p-6 text-center">
                <p class="w-12 h-12 mx-auto rounded-2xl bg-tint-teal flex items-center justify-center">
                    <x-ui.icon name="clock" class="w-6 h-6 text-[#0B3D4A]" />
                </p>
                <h3 class="font-extrabold text-[17px] mt-3">30-day plan</h3>
                <p class="text-sm st-muted mt-1.5">A clear day-by-day roadmap from Day 1 to Day 30.</p>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS: three steps --}}
    <section class="py-10 md:py-14" aria-labelledby="how-heading">
        <div class="st-darkpanel p-8 md:p-12 max-w-4xl mx-auto" aria-labelledby="how-heading">
            <h2 id="how-heading" class="st-h !text-white text-[26px] md:text-[32px] text-center">How it works</h2>
            <ol class="grid sm:grid-cols-3 gap-8 mt-8 text-center">
                <li>
                    <p class="text-[40px] font-extrabold text-[#F5A623]">1</p>
                    <h3 class="font-bold text-white mt-1">Pay securely</h3>
                    <p class="text-sm text-white/70 mt-1">UPI, cards, netbanking via Razorpay.</p>
                </li>
                <li>
                    <p class="text-[40px] font-extrabold text-[#F5A623]">2</p>
                    <h3 class="font-bold text-white mt-1">Get your link</h3>
                    <p class="text-sm text-white/70 mt-1">Instant download link on screen + email.</p>
                </li>
                <li>
                    <p class="text-[40px] font-extrabold text-[#F5A623]">3</p>
                    <h3 class="font-bold text-white mt-1">Start Day 1</h3>
                    <p class="text-sm text-white/70 mt-1">Read on any phone, tablet or computer.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-10 md:py-14 max-w-3xl mx-auto" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="st-h text-[26px] md:text-[32px] text-center mb-6">Questions, answered</h2>
        <div class="st-card px-6 py-3">
            <x-ui.accordion :items="[
                ['q' => 'How do I receive the ebook?', 'a' => 'A download link appears immediately after payment, and a backup copy is emailed to you.'],
                ['q' => 'What format is it?', 'a' => 'High-resolution PDF. Opens on phones, tablets, computers and Kindle.'],
                ['q' => 'How many times can I download?', 'a' => 'You get '.$downloadLimit.' downloads. Each link works for 10 minutes after it is created.'],
                ['q' => 'Can I get a refund?', 'a' => 'All sales are final — ebooks can\u{2019}t be returned once downloaded.'],
            ]" />
        </div>
    </section>

    {{-- FINAL CTA --}}
    @if($heroBook)
    <section class="pb-16 pt-4 text-center" aria-label="Buy now">
        <p class="text-[26px] md:text-[32px] font-extrabold font-display">Start your 30 days today.</p>
        <p class="st-muted mt-2">{{ $heroBook->price_display }} · Instant download</p>
        <form method="POST" action="{{ route('cart.store') }}" class="mt-5 inline-block">
            @csrf
            <input type="hidden" name="book_id" value="{{ $heroBook->id }}">
            <button type="submit" class="st-btn st-btn-amber st-btn-lg">
                <x-ui.icon name="cart" class="w-5 h-5" /> Buy now — {{ $heroBook->price_display }}
            </button>
        </form>
    </section>
    @endif

</div>
@endsection
