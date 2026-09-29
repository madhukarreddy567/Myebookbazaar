@extends('layouts.app')

@section('title', $combo->title.' — MyEbookBazaar')
@section('meta_description', $combo->title.' — a bundle of '.($combo->combo_books->count()).' ebooks at one price.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Combos', 'url' => route('combos')], ['label' => $combo->title]]" />

    <div class="grid lg:grid-cols-[1fr_1.5fr] gap-8 mt-6">
        {{-- Combo summary card --}}
        <div>
            <x-ui.card>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <x-ui.badge>Combo</x-ui.badge>
                    @if($combo->price_quote['on_sale'])
                    <x-ui.badge type="sale">Sale</x-ui.badge>
                    @endif
                </div>
                <h1 class="display-md text-teal-900 uppercase">{{ $combo->title }}</h1>
                @if($combo->description)
                <p class="text-mutedtext mt-3">{{ $combo->description }}</p>
                @endif

                <div class="book-card-price mt-4">
                    <span class="price text-3xl">{{ $combo->price_display }}</span>
                    @if($combo->price_regular_display)
                    <span class="book-card-oldprice text-lg">{{ $combo->price_regular_display }}</span>
                    @endif
                </div>
                @if($combo->price_quote['on_sale'] && $combo->sale_ends_at)
                <div class="mt-2"><x-ui.countdown :ends-at="$combo->sale_ends_at->toIso8601String()" /></div>
                @endif
                <p class="text-xs text-mutedtext mt-1">One payment for all {{ $combo->combo_books->count() }} ebooks.</p>

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
                    <x-ui.button type="submit" class="w-full" icon="cart">
                        {{ $ownedBooks->isNotEmpty() ? 'Buy combo anyway' : 'Buy combo' }} — {{ $combo->price_display }}
                    </x-ui.button>
                </form>
                @endif
                <p class="text-xs text-mutedtext mt-3">You need an account to buy. If you already own every book in this combo, it cannot be bought again — each ebook is sold once.</p>
            </x-ui.card>
        </div>

        {{-- Books in the combo --}}
        <div>
            <h2 class="font-heading text-2xl font-bold mb-4">What you get ({{ $combo->combo_books->count() }})</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($combo->combo_books as $book)
                <x-ui.book-card :title="$book->title"
                                :author="$book->author"
                                :description="$book->short_description"
                                :cover="$book->coverUrl()"
                                :action-url="route('books.show', $book->slug)"
                                action-label="View details" />
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
