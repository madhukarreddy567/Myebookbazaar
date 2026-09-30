@extends('layouts.app')

@section('title', $combo->title.' — MyEbookBazaar')
@section('meta_description', $combo->title.' — a bundle of '.($combo->combo_books->count()).' ebooks at one price.')
@section('canonical', route('combos.show', $combo->slug))

{{-- Schema.org breadcrumbs for the combo page. --}}
@section('jsonld')
{!! $seo->script($seo->breadcrumbJsonLd([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Combos', 'url' => route('combos')],
    ['name' => $combo->title],
])) !!}
@endsection

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Combos', 'url' => route('combos')], ['label' => $combo->title]]" />

    <div class="grid lg:grid-cols-[1fr_1.5fr] gap-6 lg:gap-8 mt-6">
        {{-- Combo summary card --}}
        <div>
            <div class="st-card p-6 md:p-7">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#0B3D4A] text-white rounded-md px-2 py-1">Combo</span>
                    @if($combo->price_quote['on_sale'])
                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#F5A623] text-[#101E28] rounded-md px-2 py-1">Sale</span>
                    @endif
                </div>
                <h1 class="st-h text-[30px]">{{ $combo->title }}</h1>
                @if($combo->description)
                <p class="st-muted mt-3 text-[15px]">{{ $combo->description }}</p>
                @endif

                <p class="flex items-baseline gap-2.5 mt-4">
                    <span class="text-[32px] font-extrabold text-[#0B3D4A]">{{ $combo->price_display }}</span>
                    @if($combo->price_regular_display)
                    <span class="book-card-oldprice text-lg">{{ $combo->price_regular_display }}</span>
                    @endif
                </p>
                @if($combo->price_quote['on_sale'] && $combo->sale_ends_at)
                <div class="mt-2"><x-ui.countdown :ends-at="$combo->sale_ends_at->toIso8601String()" /></div>
                @endif
                <p class="text-xs st-muted mt-1">One payment for all {{ $combo->combo_books->count() }} ebooks.</p>

                @if($errors->has('combo'))
                <x-ui.alert type="danger" class="mt-4">{{ $errors->first('combo') }}</x-ui.alert>
                @endif

                @if($ownsAll)
                <x-ui.alert type="info" class="mt-4">You already own every book in this combo — it is in your library.</x-ui.alert>
                @elseif($ownedBooks->isNotEmpty())
                <x-ui.alert type="warning" class="mt-4">
                    You already own {{ $ownedBooks->pluck('title')->map(fn ($t) => '“'.$t.'”')->join(', ') }} from this combo.
                    You can still buy the full combo at the listed price.
                </x-ui.alert>
                @endif

                @if(!$ownsAll)
                <form method="POST" action="{{ route('combos.buy', $combo->slug) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="st-btn st-btn-amber w-full !min-h-[54px]">
                        <x-ui.icon name="cart" class="w-5 h-5" />
                        {{ $ownedBooks->isNotEmpty() ? 'Buy combo anyway' : 'Buy combo' }} — {{ $combo->price_display }}
                    </button>
                </form>
                @endif
                <p class="text-xs st-muted mt-3">You need an account to buy. If you already own every book in this combo, it cannot be bought again — each ebook is sold once.</p>
            </div>
        </div>

        {{-- Books in the combo --}}
        <div>
            <h2 class="st-h text-[24px] mb-4">What you get ({{ $combo->combo_books->count() }})</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($combo->combo_books as $book)
                @php($bookCover = $book->displayCoverUrl())
                <article class="st-book-card">
                    <a href="{{ route('books.show', $book->slug) }}" class="st-book-cover block" tabindex="-1" aria-hidden="true">
                        @if($bookCover)
                            <img src="{{ $bookCover }}" alt="" loading="lazy">
                        @endif
                    </a>
                    <div class="st-book-body">
                        <h3 class="st-book-title line-clamp-2"><a href="{{ route('books.show', $book->slug) }}">{{ $book->title }}</a></h3>
                        @if($book->author)
                        <p class="st-book-meta">by {{ $book->author }}</p>
                        @endif
                        <a href="{{ route('books.show', $book->slug) }}" class="st-add-cart w-full mt-3 no-underline">View details</a>
                    </div>
                </article>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
