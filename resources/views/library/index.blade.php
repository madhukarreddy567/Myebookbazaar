@extends('layouts.app')

@section('title', 'My library — MyEbookBazaar')

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
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-8" x-data="{ q: '', cat: 'all' }">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My library']]" />

    {{-- Header --}}
    <div class="mt-3 flex items-center justify-between gap-3">
        <h1 class="display-lg text-teal-900 uppercase">My digital library</h1>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-saffron-500 animate-pulse" aria-hidden="true"></span>
            {{ $items->count() }} active {{ $items->count() === 1 ? 'item' : 'items' }}
        </span>
    </div>
    <p class="mt-1.5 text-mutedtext text-sm">Instant high-speed PDF downloads for your purchased guides.</p>

    @if($errors->any())
        <x-ui.alert type="warning" class="mt-4">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if($items->isEmpty())
        <x-ui.card class="mt-6">
            <x-ui.empty-state icon="book" title="Your library is empty"
                text="Your purchased ebooks will live here, ready to download any time."
                :action-url="route('books')" action-label="Browse ebooks" />
        </x-ui.card>
    @else
        {{-- Search + filter chips --}}
        <div class="mt-5">
            <div class="relative">
                <x-ui.icon name="search" class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-mutedtext pointer-events-none" />
                <input x-model="q" type="search" placeholder="Search my books…" aria-label="Search my books"
                       class="w-full h-11 pl-10 pr-4 rounded-xl bg-white dark:bg-surface border border-line text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-saffron-500/50">
            </div>
            @if($libCategories->isNotEmpty())
            <div class="mt-2.5 flex items-center gap-2 overflow-x-auto pb-1" role="group" aria-label="Filter by category">
                <button type="button" @click="cat = 'all'" :class="cat === 'all' ? 'bg-teal-900 text-white' : 'bg-teal-100 text-teal-900'"
                        class="px-3.5 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-wider shrink-0 transition-colors">
                    All <span class="opacity-70">{{ $items->count() }}</span>
                </button>
                @foreach($libCategories as $category)
                <button type="button" @click="cat = '{{ $category->id }}'" :class="cat === '{{ $category->id }}' ? 'bg-teal-900 text-white' : 'bg-teal-100 text-teal-900'"
                        class="px-3.5 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-wider shrink-0 transition-colors">
                    {{ $category->name }} <span class="opacity-70">{{ $items->filter(fn ($it) => $it->book && $it->book->category_id === $category->id)->count() }}</span>
                </button>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Purchased ebook cards --}}
        <div class="mt-5 space-y-4">
            @foreach($items as $item)
            @php
                $book = $item->book;
                $sizeLabel = $book ? $fmtSize($book->file_size) : null;
                $titleLower = mb_strtolower($item->title ?? ($book->title ?? ''));
                $catId = $book && $book->category_id ? (string) $book->category_id : '';
            @endphp
            <x-ui.card class="p-4 sm:p-5" x-data
                       x-show="(cat === 'all' || cat === '{{ $catId }}') && '{{ $titleLower }}'.includes(q.toLowerCase())">
                <div class="flex items-center justify-between gap-3 pb-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-teal-100 text-teal-900 text-[10px] font-bold uppercase tracking-wider">
                        <x-ui.icon name="check-circle" class="w-3.5 h-3.5" /> Purchased • Verified lifetime license
                    </span>
                    <span class="text-[11px] text-mutedtext uppercase font-medium shrink-0">Order #{{ $item->order->order_number ?? $item->order_id }}</span>
                </div>

                <div class="flex gap-4 items-start">
                    <span class="w-24 shrink-0 rounded-lg overflow-hidden shadow-md bg-teal-100">
                        @if($book && $book->coverUrl())
                            <img src="{{ $book->coverUrl() }}" alt="" class="w-full h-auto object-cover" loading="lazy">
                        @elseif($book && str_contains($book->slug, 'fit-at-home'))
                            <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}" alt="" class="w-full h-auto object-cover" loading="lazy">
                        @else
                            <span class="book-cover-placeholder aspect-[3/4] !p-2" role="img" aria-label="Cover coming soon"><span class="!text-xs">{{ $item->title }}</span></span>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="display-sm text-teal-900 uppercase leading-tight block line-clamp-2">{{ $item->title }}</span>
                        @if($book && $book->subtitle)
                        <span class="text-sm font-semibold text-saffron-600 block mt-0.5">{{ $book->subtitle }}</span>
                        @endif
                        <span class="mt-2 space-y-1 text-xs text-mutedtext block">
                            <span class="flex items-center gap-1.5">
                                <x-ui.icon name="file-text" class="w-4 h-4 text-teal-700 shrink-0" />
                                <span class="truncate">{{ $book && $book->pages ? $book->pages.' pages' : 'PDF' }}{{ $sizeLabel ? ' • '.$sizeLabel : '' }} • Universal PDF</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <x-ui.icon name="globe" class="w-4 h-4 text-teal-700 shrink-0" />
                                <span>{{ $book->language ?? 'English' }}{{ $book && $book->author ? ' • by '.$book->author : '' }}</span>
                            </span>
                        </span>
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-2 rounded-xl bg-tint-saffron border border-tint-saffron p-2.5">
                    <x-ui.icon name="lock" class="w-5 h-5 text-saffron-600 shrink-0" />
                    <p class="text-xs font-medium leading-snug">
                        <strong class="text-saffron-600">10-minute secure link</strong>
                        <span class="text-mutedtext"> • {{ $item->downloads_left }} of {{ $limit }} downloads left</span>
                    </p>
                </div>

                <div class="mt-3 flex flex-col gap-2">
                    @if(!$downloadsEnabled)
                        <p class="text-sm text-mutedtext">Downloads are currently disabled.</p>
                    @elseif($item->download_url)
                        <x-ui.button size="lg" href="{{ $item->download_url }}" class="w-full uppercase tracking-wider shadow-glow-saffron">
                            <span class="inline-flex items-center gap-2"><x-ui.icon name="download" class="w-5 h-5" /> Download PDF{{ $sizeLabel ? ' ('.$sizeLabel.')' : '' }}</span>
                        </x-ui.button>
                    @elseif(!$book || !$book->hasFile())
                        <p class="text-sm text-mutedtext">PDF not uploaded yet.</p>
                    @else
                        <p class="text-sm text-danger font-medium">Download limit reached.</p>
                    @endif
                    <x-ui.button variant="ghost" size="sm" href="{{ route('orders.show', $item->order) }}" class="self-start">
                        View invoice
                    </x-ui.button>
                </div>
            </x-ui.card>
            @endforeach
        </div>

        {{-- Reading help accordions --}}
        <section class="mt-8" x-data="{ open: null }">
            <h2 class="display-sm text-teal-900 uppercase inline-flex items-center gap-1.5">
                <x-ui.icon name="book" class="w-5 h-5" /> Device reading instructions
            </h2>
            <div class="mt-3 space-y-2">
                <div class="rounded-xl bg-white dark:bg-surface border border-line overflow-hidden">
                    <button type="button" @click="open = open === 'ios' ? null : 'ios'" class="w-full p-3.5 flex items-center justify-between text-left">
                        <span class="font-bold text-sm">iPhone, iPad &amp; Mac (Apple Books)</span>
                        <x-ui.icon name="chevron-down" class="w-5 h-5 text-mutedtext transition-transform" ::class="{ 'rotate-180': open === 'ios' }" />
                    </button>
                    <div x-show="open === 'ios'" x-cloak class="px-3.5 pb-3.5 text-sm text-mutedtext space-y-1.5">
                        <p>1. Tap the <strong class="text-inktext">Download PDF</strong> button above in Safari.</p>
                        <p>2. Tap the Safari Share icon at the bottom.</p>
                        <p>3. Select <strong class="text-inktext">Apple Books</strong> to sync across all your Apple devices.</p>
                    </div>
                </div>
                <div class="rounded-xl bg-white dark:bg-surface border border-line overflow-hidden">
                    <button type="button" @click="open = open === 'android' ? null : 'android'" class="w-full p-3.5 flex items-center justify-between text-left">
                        <span class="font-bold text-sm">Android &amp; Amazon Kindle</span>
                        <x-ui.icon name="chevron-down" class="w-5 h-5 text-mutedtext transition-transform" ::class="{ 'rotate-180': open === 'android' }" />
                    </button>
                    <div x-show="open === 'android'" x-cloak class="px-3.5 pb-3.5 text-sm text-mutedtext space-y-1.5">
                        <p>1. Open with your favourite PDF reader like Google Drive or ReadEra.</p>
                        <p>2. For Kindle, forward the PDF to your <em>@kindle.com</em> address or use Amazon's Send to Kindle.</p>
                    </div>
                </div>
                <div class="rounded-xl bg-white dark:bg-surface border border-line overflow-hidden">
                    <button type="button" @click="open = open === 'reset' ? null : 'reset'" class="w-full p-3.5 flex items-center justify-between text-left">
                        <span class="font-bold text-sm">Need a download link reset?</span>
                        <x-ui.icon name="chevron-down" class="w-5 h-5 text-mutedtext transition-transform" ::class="{ 'rotate-180': open === 'reset' }" />
                    </button>
                    <div x-show="open === 'reset'" x-cloak class="px-3.5 pb-3.5 text-sm text-mutedtext space-y-1.5">
                        <p>Links expire 10 minutes after creation. Just revisit this page — a fresh secure link is generated every time you open it, until your {{ $limit }} downloads are used.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Discover more --}}
        <section class="mt-6 rounded-2xl bg-gradient-to-br from-teal-900 to-teal-700 text-white p-5 flex items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-glow-saffron blur-xl pointer-events-none" aria-hidden="true"></div>
            <div class="relative z-10">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-teal-100">Expand your stack</p>
                <h2 class="display-sm uppercase text-white mt-1">Discover more guides</h2>
                <p class="text-xs text-teal-100 mt-1">Explore curated business, workout, and productivity playbooks.</p>
            </div>
            <x-ui.button href="{{ route('books') }}" size="sm" class="relative z-10 shrink-0">
                <span class="inline-flex items-center gap-1 uppercase tracking-wider">Browse <x-ui.icon name="arrow-right" class="w-4 h-4" /></span>
            </x-ui.button>
        </section>
    @endif
</div>
@endsection
