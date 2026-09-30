@extends('layouts.app')

@section('title', 'Offers — MyEbookBazaar')
@section('meta_description', 'All live offers in one place — sale prices on ebooks and combos, only while they last.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Offers']]" />

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">Offers</h1>
    <p class="st-muted mt-2 max-w-2xl text-[15px]">Every live sale price, in one place. When a sale ends, prices go back to normal — no coupons needed.</p>

    @if($books->isNotEmpty() || $combos->isNotEmpty())
        @if($books->isNotEmpty())
        <h2 class="st-h text-[24px] md:text-[28px] mt-10 mb-4">Ebooks on sale</h2>
        <div class="grid gap-4 md:gap-5 grid-cols-2 lg:grid-cols-4">
            @foreach($books as $book)
            @php
                $cover = $book->displayCoverUrl();
            @endphp
            <article class="st-book-card">
                <a href="{{ route('books.show', $book->slug) }}" class="st-book-cover block" tabindex="-1" aria-hidden="true">
                    @if($cover)
                        <img src="{{ $cover }}" alt="" loading="lazy">
                    @endif
                    <span class="st-badge-sale">Sale</span>
                </a>
                <div class="st-book-body">
                    <h3 class="st-book-title line-clamp-2"><a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a></h3>
                    @if($book->author)
                    <p class="st-book-meta">by {{ $book->author }}</p>
                    @endif
                    <p class="st-book-price">
                        <span class="now">{{ $book->price_display }}</span>
                        @if($book->price_regular_display)
                        <span class="book-card-oldprice">{{ $book->price_regular_display }}</span>
                        @endif
                    </p>
                    @if($book->sale_ends_at)
                    <p class="mt-1"><x-ui.countdown :ends-at="$book->sale_ends_at->toIso8601String()" /></p>
                    @endif
                    <a href="{{ route('books.show', $book->slug) }}" class="st-add-cart w-full mt-3 no-underline">View details</a>
                </div>
            </article>
            @endforeach
        </div>
        @endif

        @if($combos->isNotEmpty())
        <h2 class="st-h text-[24px] md:text-[28px] mt-12 mb-4">Combos on sale</h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($combos as $combo)
            <article class="st-card p-6 flex flex-col">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#0B3D4A] text-white rounded-md px-2 py-1">Combo</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#F5A623] text-[#101E28] rounded-md px-2 py-1">Sale</span>
                </div>
                <h3 class="st-h text-[22px]">{{ $combo->title }}</h3>
                <p class="text-sm mt-2 st-muted">{{ $combo->combo_books->count() }} ebooks at one price</p>
                <div class="mt-auto pt-4">
                    <p class="flex items-baseline gap-2">
                        <span class="text-xl font-extrabold text-[#0B3D4A]">{{ $combo->price_display }}</span>
                        <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                    </p>
                    <a href="{{ route('combos.show', $combo->slug) }}" class="st-btn st-btn-teal w-full mt-3 !min-h-[48px]">View combo</a>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    @else
    <div class="st-card st-empty mt-8">
        <x-ui.empty-state icon="tag" title="No offers right now"
            text="There are no live sales at the moment. Check back soon." />
    </div>
    @endif
</div>
@endsection
