@extends('layouts.app')

@section('title', 'Offers — MyEbookBazaar')
@section('meta_description', 'All live offers in one place — sale prices on ebooks and combos, only while they last.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Offers']]" />

    <h1 class="display-lg text-teal-900 uppercase mt-3">Offers</h1>
    <p class="text-mutedtext mt-2 max-w-2xl">Every live sale price, in one place. When a sale ends, prices go back to normal — no coupons needed.</p>

    @if($books->isNotEmpty() || $combos->isNotEmpty())
        @if($books->isNotEmpty())
        <h2 class="font-heading text-3xl font-bold mt-10 mb-4">Ebooks on sale</h2>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($books as $book)
            <x-ui.book-card :title="$book->title"
                            :author="$book->author"
                            :description="$book->short_description"
                            :price="$book->price_display"
                            :old-price="$book->price_regular_display"
                            :on-sale="true"
                            :sale-ends-at="$book->sale_ends_at?->toIso8601String()"
                            :cover="$book->coverUrl()"
                            :action-url="route('books.show', $book->slug)"
                            action-label="View details" />
            @endforeach
        </div>
        @endif

        @if($combos->isNotEmpty())
        <h2 class="font-heading text-3xl font-bold mt-12 mb-4">Combos on sale</h2>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($combos as $combo)
            <x-ui.card class="flex flex-col">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <x-ui.badge>Combo</x-ui.badge>
                    <x-ui.badge type="sale">Sale</x-ui.badge>
                </div>
                <h3 class="font-heading text-2xl font-bold">{{ $combo->title }}</h3>
                <p class="text-sm mt-2 text-mutedtext">{{ $combo->combo_books->count() }} ebooks at one price</p>
                <div class="mt-auto pt-4">
                    <div class="book-card-price">
                        <span class="price text-xl">{{ $combo->price_display }}</span>
                        <span class="book-card-oldprice">{{ $combo->price_regular_display }}</span>
                    </div>
                    <x-ui.button :href="route('combos.show', $combo->slug)" size="sm" class="w-full mt-3">View combo</x-ui.button>
                </div>
            </x-ui.card>
            @endforeach
        </div>
        @endif
    @else
    <x-ui.card class="mt-8">
        <x-ui.empty-state icon="tag" title="No offers right now"
            text="There are no live sales at the moment. Check back soon." />
    </x-ui.card>
    @endif
</div>
@endsection
