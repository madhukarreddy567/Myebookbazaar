@extends('layouts.app')

@section('title', 'Books — MyEbookBazaar')
@section('meta_description', 'Browse all ebooks on MyEbookBazaar. Fitness, diet, skills and money guides at honest prices.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-4">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Books']]" />

    <h1 class="display-lg text-teal-900 uppercase mt-3">Book catalog</h1>
    <p class="text-mutedtext mt-1 max-w-2xl text-[15px]">Short, practical ebooks. Search, filter and sort your way.</p>

    {{-- Search + sort --}}
    <form method="GET" action="{{ route('books') }}" class="mt-5 flex flex-col gap-3">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <label for="f-search" class="sr-only">Search ebooks</label>
                <input id="f-search" name="q" type="search" value="{{ request('q') }}" placeholder="Search ebooks…"
                       class="field-input !pr-12 !rounded-xl" autocomplete="off">
                <button type="submit" aria-label="Search"
                        class="absolute right-1 top-1/2 -translate-y-1/2 p-2.5 text-mutedtext hover:text-inktext">
                    <x-ui.icon name="search" class="w-5 h-5" />
                </button>
            </div>
            <div class="w-36 shrink-0">
                <label for="f-sort" class="sr-only">Sort by</label>
                <select id="f-sort" name="sort" class="field-input field-select !rounded-xl" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                </select>
            </div>
        </div>
        {{-- Category chips --}}
        <div class="chip-scroll" role="list" aria-label="Filter by category">
            <a href="{{ route('books', array_filter(['q' => request('q'), 'sort' => request('sort')])) }}"
               class="chip @if(!request('category')) chip-active @endif" role="listitem"
               @if(!request('category')) aria-current="true" @endif>All</a>
            @foreach($categories as $cat)
            <a href="{{ route('books', array_filter(['category' => $cat->slug, 'q' => request('q'), 'sort' => request('sort')])) }}"
               class="chip @if(request('category') === $cat->slug) chip-active @endif" role="listitem"
               @if(request('category') === $cat->slug) aria-current="true" @endif>{{ $cat->name }}</a>
            @endforeach
        </div>
        @if(request('category'))
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
    </form>

    {{-- Results --}}
    @if($books->isNotEmpty())
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
        @foreach($books as $book)
        <x-ui.book-card :title="$book->title"
                        :author="$book->author"
                        :description="$book->short_description"
                        :price="$book->price_display"
                        :old-price="$book->price_regular_display"
                        :on-sale="$book->price_quote['on_sale']"
                        :sale-ends-at="$book->sale_ends_at?->toIso8601String()"
                        :cover="$book->coverUrl()"
                        action-url="{{ route('books.show', $book->slug) }}"
                        action-label="View details" />
        @endforeach
    </div>
    <div class="mt-8">
        {{ $books->links() }}
    </div>
    @else
    <x-ui.card class="mt-5">
        <x-ui.empty-state icon="book" title="No books found"
            text="Try a different search or category."
            :action-url="route('books')" action-label="Clear filters" />
    </x-ui.card>
    @endif
</div>
@endsection
