@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'My Digital Library — MyEbookBazaar')
@section('meta_description', 'Your MyEbookBazaar digital library: purchased ebooks, secure signed download links and invoices.')

@php
$fmtSize = function ($bytes) {
    if (!$bytes || $bytes <= 0) return null;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = min((int) floor(log($bytes, 1024)), count($units) - 1);
    return round($bytes / pow(1024, $i), 1).' '.$units[$i];
};
$libCategories = $items->filter(fn ($it) => $it->book && $it->book->category)
    ->map(fn ($it) => $it->book->category)
    ->unique('id')->values();
$regionLabel = auth()->user() && auth()->user()->pricing_region === 'INTL' ? 'INTL ($)' : 'India (₹)';
@endphp

@section('content')
<div class="st-page w-full max-w-[1120px] mx-auto px-4 sm:px-6 py-8 sm:py-12">

    {{-- Top header --}}
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-container/10 text-primary-container font-label-sm text-label-sm tracking-wide uppercase font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
                Personal Collection
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold">My Library</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xl">
                Your digital bookshelf — {{ auth()->user()->name }} · {{ $regionLabel }}.
                Secure download links, refreshed on every visit.
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto bg-surface-container-low px-3.5 py-1.5 rounded-full text-secondary text-body-sm font-body-sm shadow-sm">
            <span class="material-symbols-outlined text-[18px] text-tertiary">lock</span>
            <span>Signed links expire in 10 minutes</span>
        </div>
    </header>

    {{-- Shelf summary strip --}}
    <div class="w-full bg-surface-container-low rounded-xl px-5 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm mb-8">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[22px]">auto_stories</span>
            </div>
            <div>
                <p class="font-label-md text-label-md font-semibold text-on-surface">
                    {{ $items->count() }} {{ Str::plural('ebook', $items->count()) }} purchased · {{ $limit }} {{ Str::plural('download', $limit) }} per book
                </p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Downloads are formatted for Apple Books, Kindle, and PDF viewers.
                </p>
            </div>
        </div>
        <a href="{{ route('books') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-secondary hover:bg-surface-container font-label-sm text-label-sm transition-colors shrink-0">
            <span class="material-symbols-outlined text-[16px]">add</span>
            Browse more ebooks
        </a>
    </div>

    @if($items->isEmpty())
        <div class="bg-surface-container-lowest rounded-xl p-10 sm:p-14 shadow-sm text-center">
            <div class="w-14 h-14 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[28px]">menu_book</span>
            </div>
            <h2 class="font-headline-md text-headline-md text-on-surface font-bold">Your library is empty</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-md mx-auto">
                Your purchased ebooks will live here, ready to download any time.
            </p>
            <a href="{{ route('books') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm mt-6">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                Browse ebooks
            </a>
        </div>
    @else
        {{-- Search & filter controls --}}
        <section aria-label="Book catalog filters" class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div aria-label="Filter library by category" class="flex flex-wrap items-center gap-2" role="tablist" id="library-filter-btns">
                <button aria-selected="true" class="library-filter-btn px-4 py-2 rounded-xl bg-secondary text-on-secondary font-label-md text-label-md shadow-sm transition-all active:scale-[0.99]" data-filter="all" role="tab" type="button">
                    All ({{ $items->count() }})
                </button>
                @foreach($libCategories as $category)
                <button aria-selected="false" class="library-filter-btn px-4 py-2 rounded-xl bg-surface-container-lowest text-on-surface-variant hover:text-on-surface font-label-md text-label-md transition-all active:scale-[0.99] shadow-sm" data-filter="cat-{{ $category->id }}" role="tab" type="button">
                    {{ $category->name }} ({{ $items->filter(fn ($it) => $it->book && $it->book->category_id === $category->id)->count() }})
                </button>
                @endforeach
            </div>
            <div class="relative w-full md:w-72">
                <label class="sr-only" for="library-search">Search your library</label>
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </span>
                <input class="w-full h-11 pl-10 pr-4 bg-surface-container-lowest rounded-xl font-body-sm text-body-sm text-on-surface placeholder:text-on-surface-variant/60 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-container" id="library-search" placeholder="Search title or topics..." type="text"/>
            </div>
        </section>

        {{-- Ebook cards --}}
        <section aria-label="Purchased Bookshelf" class="grid grid-cols-1 gap-6 mb-12">
            @foreach($items as $item)
            @php
                $book = $item->book;
                $sizeLabel = $book ? $fmtSize($book->file_size) : null;
                $cover = $book?->displayCoverUrl();
                $titleLower = mb_strtolower($item->title ?? ($book->title ?? ''));
                $catFilter = $book && $book->category_id ? 'cat-'.$book->category_id : 'none';
                $pct = $limit > 0 ? (int) round($item->downloads_left / $limit * 100) : 0;
                $lowQuota = $item->downloads_left <= 1;
            @endphp
            <article class="ebook-item bg-surface-container-lowest rounded-xl p-5 sm:p-7 shadow-sm transition-all hover:shadow-md flex flex-col md:flex-row gap-6 md:gap-8 items-start" data-category="{{ $catFilter }}" data-title="{{ $titleLower }}">
                {{-- Book cover --}}
                <div class="w-full sm:w-48 md:w-52 aspect-[3/4] sm:aspect-[2/3] shrink-0 rounded-DEFAULT overflow-hidden bg-surface-container relative shadow-sm group">
                    @if($cover)
                        <img alt="Cover of {{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out" src="{{ $cover }}" loading="lazy"/>
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center gap-2 p-4 bg-secondary text-on-secondary">
                            <span class="material-symbols-outlined text-[36px]">menu_book</span>
                            <p class="font-label-sm text-label-sm text-center line-clamp-3">{{ $item->title }}</p>
                        </div>
                    @endif
                    @if($book && $book->edition)
                    <div class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-full bg-secondary/90 backdrop-blur-sm text-on-secondary font-label-sm text-[10px] uppercase tracking-wider font-semibold">
                        Edition {{ $book->edition }}
                    </div>
                    @endif
                </div>

                {{-- Meta & actions --}}
                <div class="flex-1 flex flex-col justify-between w-full h-full space-y-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            @if($book && $book->category)
                            <span class="px-2.5 py-0.5 rounded-full bg-surface-container text-secondary font-label-sm text-label-sm font-medium">
                                {{ $book->category->name }}
                            </span>
                            @endif
                            @if($book && $book->pages)
                            <span class="px-2.5 py-0.5 rounded-full bg-surface-container text-secondary font-label-sm text-label-sm font-medium">
                                PDF ({{ $book->pages }} Pages)
                            </span>
                            @endif
                            <span class="inline-flex items-center gap-1 text-on-surface-variant font-body-sm text-body-sm ml-auto">
                                <span class="material-symbols-outlined text-[16px] text-tertiary">verified</span>
                                Verified Purchase · Paid {{ $item->price_display }}
                            </span>
                        </div>
                        <h2 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight">
                            {{ $item->title }}
                        </h2>
                        <p class="font-body-md text-body-md text-on-surface-variant mt-1 max-w-2xl">
                            @if($book && $book->author)By {{ $book->author }} · @endif
                            Order <a href="{{ route('orders.show', $item->order) }}" class="font-semibold text-primary hover:underline">#{{ $item->order->order_number ?? $item->order_id }}</a>
                        </p>
                        @if($book && $book->short_description)
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 max-w-2xl line-clamp-2">{{ $book->short_description }}</p>
                        @endif
                    </div>

                    {{-- Download actions --}}
                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center flex-wrap gap-3">
                        @if(!$downloadsEnabled)
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Downloads are currently disabled.</p>
                        @elseif($item->download_url)
                            <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ $item->download_url }}">
                                <span class="material-symbols-outlined text-[18px]">download</span>
                                <span>Download PDF{{ $sizeLabel ? ' ('.$sizeLabel.')' : '' }}</span>
                            </a>
                        @elseif(!$book || !$book->hasFile())
                            <p class="font-body-sm text-body-sm text-on-surface-variant">PDF not uploaded yet.</p>
                        @else
                            <p class="font-body-sm text-body-sm font-semibold text-error">Download limit reached.</p>
                        @endif
                        @if($lowQuota && $item->download_url)
                        <a href="{{ route('contact') }}" class="inline-flex items-center gap-1.5 text-primary font-label-md text-label-md font-semibold hover:underline">
                            <span>Request limit extension</span>
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </a>
                        @endif
                    </div>

                    {{-- Quota & link expiry --}}
                    <div class="pt-4 mt-2 bg-surface-container-low/50 rounded-DEFAULT p-3.5 flex flex-col gap-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-on-surface-variant">
                            <p class="font-body-sm text-body-sm flex items-center gap-1.5">
                                @if($lowQuota)
                                <span class="material-symbols-outlined text-[16px] text-error">warning</span>
                                <span>Remaining downloads: <strong class="text-on-surface font-semibold">{{ $item->downloads_left }} of {{ $limit }}</strong> — contact support if you need more.</span>
                                @else
                                <span class="material-symbols-outlined text-[16px] text-tertiary">info</span>
                                <span>Remaining downloads: <strong class="text-on-surface font-semibold">{{ $item->downloads_left }} of {{ $limit }}</strong></span>
                                @endif
                            </p>
                            @if($item->download_url)
                            <span class="st-timer-chip inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-lowest font-label-sm text-label-sm text-on-surface-variant shadow-sm shrink-0" x-data="{ secs: 600 }" x-init="setInterval(() => { if (secs > 0) secs-- }, 1000)">
                                <span class="material-symbols-outlined text-[16px]">timer</span>
                                <span x-show="secs > 0" x-text="String(Math.floor(secs / 60)).padStart(2, '0') + 'm:' + String(secs % 60).padStart(2, '0') + 's'"></span>
                                <span x-show="secs === 0" x-cloak>expired — refresh page</span>
                            </span>
                            @else
                            <span class="st-timer-chip inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-lowest font-label-sm text-label-sm text-on-surface-variant shadow-sm shrink-0">
                                <span class="material-symbols-outlined text-[16px]">timer</span>
                                <span>links valid 10 min</span>
                            </span>
                            @endif
                        </div>
                        <div role="progressbar" aria-label="Downloads remaining" aria-valuenow="{{ $item->downloads_left }}" aria-valuemin="0" aria-valuemax="{{ $limit }}" class="h-1.5 rounded-full bg-surface-container overflow-hidden">
                            <div class="st-quota-fill h-full rounded-full {{ $lowQuota ? 'bg-error' : 'bg-tertiary' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </section>

        {{-- Support callout --}}
        <aside aria-label="Kindle and device assistance note" class="w-full bg-surface-container-low rounded-xl p-6 sm:p-7 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-full bg-surface-container-lowest text-secondary flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">tablet_mac</span>
                </div>
                <div>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">
                        Reading on Kindle, iPad, or Android?
                    </h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                        Having trouble opening files on Kindle or iPad? Read our 2-minute setup guide or contact support.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full bg-surface-container-lowest text-secondary font-label-md text-label-md font-semibold hover:bg-surface-container transition-colors shadow-sm" href="{{ route('faq') }}">
                    <span class="material-symbols-outlined text-[16px]">help_outline</span>
                    Setup Guide
                </a>
                <a class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary/90 transition-colors shadow-sm" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[16px]">support_agent</span>
                    Get Support
                </a>
            </div>
        </aside>
    @endif
</div>

@if(!$items->isEmpty())
<script>
(function() {
    var filterBtns = document.querySelectorAll('.library-filter-btn');
    var searchInput = document.getElementById('library-search');
    var bookCards = document.querySelectorAll('.ebook-item');
    var currentCategory = 'all';
    var currentQuery = '';

    function applyFilters() {
        bookCards.forEach(function(card) {
            var category = card.getAttribute('data-category');
            var title = card.getAttribute('data-title') || '';
            var categoryMatch = (currentCategory === 'all' || category === currentCategory);
            var searchMatch = !currentQuery || title.indexOf(currentQuery.toLowerCase()) !== -1;
            card.style.display = (categoryMatch && searchMatch) ? '' : 'none';
        });
    }

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterBtns.forEach(function(b) {
                b.classList.remove('bg-secondary', 'text-on-secondary');
                b.classList.add('bg-surface-container-lowest', 'text-on-surface-variant');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('bg-secondary', 'text-on-secondary');
            btn.classList.remove('bg-surface-container-lowest', 'text-on-surface-variant');
            btn.setAttribute('aria-selected', 'true');
            currentCategory = btn.getAttribute('data-filter');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            currentQuery = e.target.value.trim();
            applyFilters();
        });
    }
})();
</script>
@endif
@endsection
