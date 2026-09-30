@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'My Digital Library — MyEbookBazaar')
@section('meta_description', 'Your personal MyEbookBazaar library: purchased ebooks, signed download links, invoices and lifetime access.')

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
$regionLabel = auth()->user() && auth()->user()->pricing_region === 'INTL' ? 'INTL $' : 'INR ₹';
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10" x-data="{ q: '', cat: 'all' }">
    <p class="st-crumb">
        <a href="{{ route('home') }}">Home</a><span class="sep">›</span>
        <a href="{{ route('account') }}">Reader Portal</a><span class="sep">›</span>
        <strong class="text-[#101E28]">My Digital Library</strong>
    </p>

    <div class="grid lg:grid-cols-[1.5fr_1fr] gap-5 mt-4 items-start">
        {{-- Header panel --}}
        <div class="st-card p-6 md:p-8">
            <p class="inline-flex items-center gap-2 text-[12px] font-bold st-muted bg-[#F1F4FA] border border-[#E4E8F2] rounded-full px-3.5 py-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#F5A623]" aria-hidden="true"></span>
                Verified Account • {{ auth()->user()->name }} • {{ auth()->user()->email }} • {{ $regionLabel }}
            </p>
            <h1 class="st-h text-[34px] md:text-[42px] mt-3">My Digital Library</h1>
            <p class="st-muted text-[15px] mt-2 leading-relaxed max-w-xl">
                Access your purchased ebooks, secure signed download tokens, and reading companions.
                Direct DRM-free files ready for Kindle, Apple Books, Kobo, and e-ink displays.
            </p>
            <div class="st-security-box mt-5">
                <x-ui.icon name="lock" class="w-5 h-5 text-[#0B3D4A] shrink-0" />
                <p class="text-sm">
                    <strong class="text-[#0B3D4A]">Security Protocol:</strong>
                    Signed URLs expire in 10 mins • {{ $limit }} lifetime downloads/edition
                    <a href="#device-help" class="font-extrabold text-[#0B3D4A] hover:underline underline-offset-4 ml-2 whitespace-nowrap">Transfer Guide →</a>
                </p>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="st-stat-card">
                <span class="st-menu-ic !w-11 !h-11 mb-3"><x-ui.icon name="book" /></span>
                <p class="st-stat-num">{{ $items->count() }}</p>
                <p class="st-stat-label">Books owned</p>
                <p class="text-[13px] font-bold text-[#D97706] mt-1.5">100% DRM-Free</p>
            </div>
            <div class="st-stat-card">
                <span class="st-menu-ic !w-11 !h-11 mb-3"><x-ui.icon name="download" /></span>
                <p class="st-stat-num">{{ $limit }}</p>
                <p class="st-stat-label">Downloads / book</p>
                <p class="text-[13px] font-bold text-[#0B3D4A] mt-1.5">Lifetime access</p>
            </div>
            <div class="st-darkpanel !rounded-[18px] p-5 col-span-2">
                <div class="relative z-10 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#F5A623] inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F5A623] animate-pulse" aria-hidden="true"></span> Link status
                        </p>
                        <p class="text-white font-extrabold text-lg mt-1">Active Signed Sessions</p>
                        <p class="text-white/60 text-[13px]">Fresh 10-minute download links generated on every visit</p>
                    </div>
                    <x-ui.icon name="shield" class="w-10 h-10 text-white/25 shrink-0" />
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <x-ui.alert type="warning" class="mt-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if($items->isEmpty())
        <div class="st-card st-empty mt-6">
            <x-ui.empty-state icon="book" title="Your library is empty"
                text="Your purchased ebooks will live here, ready to download any time."
                :action-url="route('books')" action-label="Browse ebooks" />
        </div>
    @else
        {{-- Search + filters --}}
        <div class="st-card p-4 mt-6">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="st-input-wrap flex-1">
                    <x-ui.icon name="search" class="st-input-icon" />
                    <label for="lib-q" class="sr-only">Filter by title, author, or order ID</label>
                    <input id="lib-q" x-model="q" type="search" placeholder="Filter by title, author, or order ID…"
                           class="st-input has-icon !min-h-[48px]">
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0" role="group" aria-label="Filter by category">
                    <button type="button" @click="cat = 'all'" :class="cat === 'all' ? 'chip-active' : ''"
                            class="chip shrink-0">All Formats</button>
                    @foreach($libCategories as $category)
                    <button type="button" @click="cat = '{{ $category->id }}'" :class="cat === '{{ $category->id }}' ? 'chip-active' : ''"
                            class="chip shrink-0">{{ $category->name }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Purchased ebook cards --}}
        <div class="mt-5 space-y-5">
            @foreach($items as $item)
            @php
                $book = $item->book;
                $sizeLabel = $book ? $fmtSize($book->file_size) : null;
                $cover = $book?->displayCoverUrl();
                $titleLower = mb_strtolower($item->title ?? ($book->title ?? ''));
                $catId = $book && $book->category_id ? (string) $book->category_id : '';
                $pct = $limit > 0 ? (int) round($item->downloads_left / $limit * 100) : 0;
                $lowQuota = $item->downloads_left <= 1;
            @endphp
            <article class="st-card p-5 md:p-6" x-data
                     x-show="(cat === 'all' || cat === '{{ $catId }}') && '{{ $titleLower }}'.includes(q.toLowerCase())">
                <div class="grid sm:grid-cols-[150px_1fr] gap-5">
                    {{-- Cover --}}
                    <div class="relative w-36 sm:w-full mx-auto sm:mx-0">
                        @if($cover)
                            <img src="{{ $cover }}" alt="Cover of {{ $item->title }}" class="w-full h-auto rounded-2xl shadow-lg" loading="lazy">
                        @else
                            <div class="book-cover-placeholder aspect-[3/4] rounded-2xl" role="img" aria-label="Cover coming soon"><span class="text-sm">{{ $item->title }}</span></div>
                        @endif
                        @if($book && $book->edition)
                        <span class="absolute top-2 left-2 text-[10px] font-extrabold uppercase tracking-wider bg-[#0B3D4A] text-white rounded-md px-2 py-1">Edition {{ $book->edition }}</span>
                        @endif
                        <span class="st-badge-free !top-auto !bottom-2">DRM-Free</span>
                    </div>

                    {{-- Details --}}
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#5C6B75]">
                                @if($book && $book->category){{ $book->category->name }} • @endif
                                @if($book && $book->pages){{ $book->pages }} pages @else PDF ebook @endif
                            </p>
                            <p class="inline-flex items-center gap-1.5 text-[12px] font-bold bg-[#EEF3FF] text-[#0B3D4A] rounded-lg px-2.5 py-1.5">
                                <x-ui.icon name="file-text" class="w-3.5 h-3.5" />
                                Order <span class="font-extrabold">#{{ $item->order->order_number ?? $item->order_id }}</span>
                                <span class="text-[#D97706]">Paid {{ $item->price_display }}</span>
                            </p>
                        </div>

                        <h2 class="st-h text-[24px] md:text-[28px] mt-2">{{ $item->title }}</h2>
                        <p class="text-sm st-muted mt-1">
                            @if($book && $book->author)By {{ $book->author }} • @endif
                            @if($book && $book->published_at)Published {{ $book->published_at->format('F Y') }}@endif
                        </p>
                        @if($book && $book->short_description)
                        <p class="text-[14px] st-muted mt-2 leading-relaxed">{{ $book->short_description }}</p>
                        @endif

                        {{-- Quota bar --}}
                        <div class="st-quota mt-4 {{ $lowQuota ? 'low' : '' }}">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[13px] font-bold {{ $lowQuota ? 'text-[#B3372F]' : 'text-[#0B3D4A]' }} inline-flex items-center gap-1.5">
                                    @if($lowQuota)
                                    <x-ui.icon name="alert" class="w-4 h-4" />
                                    Low Quota Notice • Only {{ $item->downloads_left }} {{ $item->downloads_left === 1 ? 'download' : 'downloads' }} remaining!
                                    @else
                                    <x-ui.icon name="download" class="w-4 h-4" />
                                    Download Quota • {{ $item->downloads_left }} of {{ $limit }} downloads remaining{{ $item->downloads_left === $limit ? ' (Fresh)' : '' }}
                                    @endif
                                </p>
                                <span class="st-timer-chip" x-data="{ secs: 600 }" x-init="setInterval(() => { if (secs > 0) secs-- }, 1000)">
                                    <x-ui.icon name="clock" class="w-3.5 h-3.5" />
                                    <span x-show="secs > 0" x-text="String(Math.floor(secs / 60)).padStart(2, '0') + 'm:' + String(secs % 60).padStart(2, '0') + 's'"></span>
                                    <span x-show="secs === 0" x-cloak>expired — refresh page</span>
                                </span>
                            </div>
                            <div class="st-quota-track" role="progressbar" aria-label="Downloads remaining" aria-valuenow="{{ $item->downloads_left }}" aria-valuemin="0" aria-valuemax="{{ $limit }}">
                                <div class="st-quota-fill" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-wrap items-center gap-3 mt-4">
                            @if(!$downloadsEnabled)
                                <p class="text-sm st-muted">Downloads are currently disabled.</p>
                            @elseif($item->download_url)
                                <a href="{{ $item->download_url }}" class="st-btn st-btn-teal !min-h-[50px]">
                                    <x-ui.icon name="download" class="w-5 h-5" />
                                    Download PDF{{ $sizeLabel ? ' ('.$sizeLabel.')' : '' }}
                                </a>
                            @elseif(!$book || !$book->hasFile())
                                <p class="text-sm st-muted">PDF not uploaded yet.</p>
                            @else
                                <p class="text-sm font-bold text-[#B3372F]">Download limit reached.</p>
                            @endif
                            @if($lowQuota && $item->download_url)
                                <a href="{{ route('contact') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4">
                                    <x-ui.icon name="info" class="w-4 h-4" /> Request Limit Extension
                                </a>
                            @endif
                            <span class="ml-auto inline-flex items-center gap-3 text-[13px] font-bold">
                                <a href="{{ route('orders.show', $item->order) }}" class="inline-flex items-center gap-1.5 text-[#0B3D4A] hover:underline underline-offset-4">
                                    <x-ui.icon name="file-text" class="w-4 h-4" /> Invoice #INV-{{ $item->order->order_number ?? $item->order_id }}
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        {{-- Help cards --}}
        <section id="device-help" class="grid md:grid-cols-3 gap-5 mt-8 scroll-mt-24" aria-label="Download help">
            <div class="st-card p-6">
                <span class="st-menu-ic mb-4"><x-ui.icon name="book" /></span>
                <h3 class="font-extrabold text-lg">Device Reading Instructions</h3>
                <div class="text-sm st-muted mt-2 space-y-2 leading-relaxed">
                    <p><strong class="text-[#101E28]">iPhone / iPad / Mac:</strong> tap Download PDF in Safari, then Share → Apple Books.</p>
                    <p><strong class="text-[#101E28]">Android:</strong> open with Google Drive or ReadEra.</p>
                    <p><strong class="text-[#101E28]">Kindle:</strong> forward the PDF to your @kindle.com address or use Amazon&rsquo;s Send to Kindle.</p>
                </div>
            </div>
            <div class="st-card p-6">
                <span class="st-menu-ic mb-4"><x-ui.icon name="lock" /></span>
                <h3 class="font-extrabold text-lg">Signed 10-Min Links</h3>
                <p class="text-sm st-muted mt-2 leading-relaxed">
                    Each download URL contains a short-lived cryptographic HMAC signature to prevent
                    unauthorized hotlinking. If your timer hits 0:00, simply refresh this page —
                    a fresh link is generated every time you visit, until your {{ $limit }} downloads are used.
                </p>
                <p class="text-[12px] font-bold text-[#1E8E5A] mt-3 inline-flex items-center gap-1.5">
                    <x-ui.icon name="check-circle" class="w-4 h-4" /> Zero DRM watermark locks
                </p>
            </div>
            <div class="st-card p-6">
                <span class="st-menu-ic mb-4"><x-ui.icon name="mail" /></span>
                <h3 class="font-extrabold text-lg">Need a Limit Reset?</h3>
                <p class="text-sm st-muted mt-2 leading-relaxed">
                    Exceeded {{ $limit }} device transfers on a single title? Simply send an inquiry quoting
                    your Order ID. Our customer success team re-arms download counters within a few hours.
                </p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-1.5 text-sm font-extrabold text-[#0B3D4A] hover:underline underline-offset-4 mt-3">
                    Email Support Team <x-ui.icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </section>

        {{-- Discover more --}}
        <section class="st-darkpanel p-6 md:p-8 mt-8" aria-label="Discover more guides">
            <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-[#F5A623]">Expand your stack</p>
                    <h2 class="st-h !text-white text-[24px] mt-1.5">Discover more guides</h2>
                    <p class="text-white/70 text-sm mt-1">Explore curated fitness, skills, and money playbooks.</p>
                </div>
                <a href="{{ route('books') }}" class="st-btn st-btn-amber shrink-0">
                    Browse <x-ui.icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </section>
    @endif
</div>
@endsection
