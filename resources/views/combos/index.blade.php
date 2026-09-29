@extends('layouts.app')

@section('title', 'Combos — MyEbookBazaar')
@section('meta_description', 'Book combos — bundles of ebooks at one price. Buy the bundle, get every ebook in your library.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Combos']]" />

    <h1 class="display-lg text-teal-900 uppercase mt-3">Combos</h1>
    <p class="text-mutedtext mt-2 max-w-2xl">Bundles of ebooks at one honest price. Every book in the combo lands in your library as its own download.</p>

    @if($combos->isNotEmpty())
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-8">
        @foreach($combos as $combo)
        <x-ui.card class="flex flex-col">
            <div class="flex flex-wrap gap-1.5 mb-3">
                <x-ui.badge>Combo</x-ui.badge>
                @if($combo->price_quote['on_sale'])
                <x-ui.badge type="sale">Sale</x-ui.badge>
                @endif
            </div>
            <h2 class="font-heading text-2xl font-bold">{{ $combo->title }}</h2>
            @if($combo->description)
            <p class="text-mutedtext text-sm mt-1 line-clamp-2">{{ $combo->description }}</p>
            @endif
            <p class="text-sm mt-3 text-mutedtext">{{ $combo->combo_books->count() }} ebooks included:</p>
            <ul class="mt-1 mb-4 space-y-1">
                @foreach($combo->combo_books->take(4) as $book)
                <li class="text-sm truncate">• {{ $book->title }}</li>
                @endforeach
                @if($combo->combo_books->count() > 4)
                <li class="text-sm text-mutedtext">• and {{ $combo->combo_books->count() - 4 }} more…</li>
                @endif
            </ul>
            <div class="mt-auto">
                <div class="book-card-price">
                    <span class="price text-xl">{{ $combo->price_display }}</span>
                    @if($combo->price_regular_display)
                    <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                    @endif
                </div>
                <x-ui.button :href="route('combos.show', $combo->slug)" size="sm" class="w-full mt-3">View combo</x-ui.button>
            </div>
        </x-ui.card>
        @endforeach
    </div>
    @else
    <x-ui.card class="mt-8">
        <x-ui.empty-state icon="tag" title="No combos right now"
            text="Check back soon — new bundles are added regularly." />
    </x-ui.card>
    @endif
</div>
@endsection
