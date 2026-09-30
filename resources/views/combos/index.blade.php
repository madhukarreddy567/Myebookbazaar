@extends('layouts.stitch')

@section('title', 'Combos — MyEbookBazaar')
@section('meta_description', 'Book combos — bundles of ebooks at one price. Buy the bundle, get every ebook in your library.')

@section('content')
<div class="st-page max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Combos']]" />

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">Combos</h1>
    <p class="st-muted mt-2 max-w-2xl text-[15px]">Bundles of ebooks at one honest price. Every book in the combo lands in your library as its own download.</p>

    @if($combos->isNotEmpty())
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mt-8">
        @foreach($combos as $combo)
        <article class="st-card p-6 flex flex-col">
            <div class="flex flex-wrap gap-1.5 mb-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#0B3D4A] text-white rounded-md px-2 py-1">Combo</span>
                @if($combo->price_quote['on_sale'])
                <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#F5A623] text-[#101E28] rounded-md px-2 py-1">Sale</span>
                @endif
            </div>
            <h2 class="st-h text-[22px]">{{ $combo->title }}</h2>
            @if($combo->description)
            <p class="st-muted text-sm mt-1 line-clamp-2">{{ $combo->description }}</p>
            @endif
            <p class="text-sm mt-3 st-muted">{{ $combo->combo_books->count() }} ebooks included:</p>
            <ul class="mt-1 mb-4 space-y-1">
                @foreach($combo->combo_books->take(4) as $book)
                <li class="text-sm truncate">• {{ $book->title }}</li>
                @endforeach
                @if($combo->combo_books->count() > 4)
                <li class="text-sm st-muted">• and {{ $combo->combo_books->count() - 4 }} more…</li>
                @endif
            </ul>
            <div class="mt-auto">
                <p class="flex items-baseline gap-2">
                    <span class="text-xl font-extrabold text-[#0B3D4A]">{{ $combo->price_display }}</span>
                    @if($combo->price_regular_display)
                    <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                    @endif
                </p>
                <a href="{{ route('combos.show', $combo->slug) }}" class="st-btn st-btn-teal w-full mt-3 !min-h-[48px]">View combo</a>
            </div>
        </article>
        @endforeach
    </div>
    @else
    <div class="st-card st-empty mt-8">
        <x-ui.empty-state icon="tag" title="No combos right now"
            text="Check back soon — new bundles are added regularly." />
    </div>
    @endif
</div>
@endsection
