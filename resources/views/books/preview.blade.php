{{-- Ebook preview reader with screenshot deterrents (restyled in the Stitch
     reader design language).
     - Pages served as images (no raw PDF access).
     - Right-click, text selection, drag, and print disabled.
     - Watermark overlay on every page.
     - Content blurs when tab loses focus (defeats simple screen capture tools).
     - Keyboard shortcuts for screenshots (PrintScreen) are intercepted where possible.
--}}
@extends('layouts.stitch')

@section('title', 'Preview: ' . $book->title . ' — MyEbookBazaar')
@section('meta_description', 'Preview the first ' . $previewPages . ' pages of ' . $book->title . '.')

@section('content')
<div class="st-page w-full bg-surface" id="preview-root">
    {{-- Reader sub-bar: back, title, page counter, theme toggle, progress --}}
    <div class="sticky top-16 z-40 bg-surface/95 backdrop-blur-md px-4 sm:px-6 py-2.5 shadow-sm border-b border-outline-variant/40">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <div class="flex items-center gap-2 min-w-0">
                    <a href="{{ route('books.show', $book->slug) }}" aria-label="Back to book details"
                       class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-secondary-container transition-all shrink-0">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                    </a>
                    <div class="min-w-0">
                        <p class="font-label-sm text-label-sm text-on-surface font-semibold truncate">{{ $book->title }}</p>
                        <p class="font-label-sm text-[11px] text-on-surface-variant">
                            <span id="preview-page-label">Page 1 of {{ $previewPages }}</span>
                            <span class="mx-1" aria-hidden="true">•</span>
                            <span id="preview-page-pct">1%</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" aria-label="Previous page" id="preview-prev"
                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-secondary-container transition-all">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                    </button>
                    <button type="button" aria-label="Next page" id="preview-next"
                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface hover:bg-secondary-container transition-all">
                        <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                    </button>
                    <button type="button" aria-label="Reading theme" id="preview-theme"
                            class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed hover:bg-primary-container hover:text-on-primary transition-all">
                        <span class="material-symbols-outlined text-[18px]" id="preview-theme-icon">wb_twilight</span>
                    </button>
                </div>
            </div>
            <div class="w-full h-1 bg-surface-container rounded-full overflow-hidden">
                <div class="h-full bg-primary-container rounded-full transition-all duration-300" id="preview-progress" style="width: 1%;"></div>
            </div>
        </div>
    </div>

    {{-- Reading canvas --}}
    <div class="px-4 sm:px-6 pt-6 pb-28" id="preview-canvas" style="background-color: #FBF9F5;">
        <div class="max-w-3xl mx-auto">
            {{-- Book mini spotlight banner (real data only) --}}
            <div class="mb-6 p-4 bg-surface-container-lowest rounded-DEFAULT shadow-sm flex items-center gap-4">
                @php($previewCover = $book->displayCoverUrl())
                @if($previewCover)
                <div class="w-14 h-20 rounded shrink-0 overflow-hidden shadow-md bg-surface-container">
                    <img src="{{ $previewCover }}" alt="" class="w-full h-full object-cover" draggable="false" oncontextmenu="return false;">
                </div>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-[10px] uppercase font-bold tracking-wider mb-1">
                        <span class="material-symbols-outlined text-[12px]">verified</span>
                        Official Preview
                    </div>
                    <h1 class="font-headline-sm text-headline-sm leading-tight text-on-surface font-bold truncate">{{ $book->title }}</h1>
                    @if($book->author)
                    <p class="font-body-sm text-body-sm text-secondary truncate mt-0.5">By {{ $book->author }}</p>
                    @endif
                    <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                        Showing the first {{ $previewPages }} of {{ $totalPages }} pages
                    </p>
                </div>
            </div>

            {{-- Page images --}}
            <div id="preview-pages" class="flex flex-col gap-6 select-none">
                @for($i = 1; $i <= $previewPages; $i++)
                    <div class="preview-page relative bg-white rounded-DEFAULT shadow-lg overflow-hidden" data-page="{{ $i }}">
                        <img src="{{ route('books.preview.page', ['slug' => $book->slug, 'page' => $i]) }}"
                             alt="Preview page {{ $i }} of {{ $book->title }}"
                             class="w-full h-auto block pointer-events-none"
                             draggable="false"
                             oncontextmenu="return false;"
                             loading="{{ $i > 2 ? 'lazy' : 'eager' }}">
                        {{-- Watermark overlay --}}
                        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <span class="text-4xl font-bold text-gray-400/20 rotate-[-30deg] select-none">
                                PREVIEW
                            </span>
                        </div>
                        <div class="absolute bottom-2 right-3 text-xs text-gray-400/70 pointer-events-none select-none">
                            Page {{ $i }} · MyEbookBazaar preview
                        </div>
                    </div>
                @endfor
            </div>

            {{-- End-of-preview marker --}}
            <div class="py-8 flex flex-col items-center justify-center text-center">
                <div class="w-12 h-1 bg-surface-variant rounded-full mb-3"></div>
                <span class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">You have reached the end of the free sample</span>
            </div>
        </div>
    </div>

    {{-- Sticky bottom conversion dock --}}
    <aside class="fixed bottom-0 left-0 right-0 z-50 bg-surface-container-lowest/95 backdrop-blur-xl px-4 sm:px-6 pt-3 pb-4 shadow-[0_-8px_30px_rgba(7,30,36,0.08)]">
        <div class="max-w-3xl mx-auto flex flex-col gap-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="w-5 h-5 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed text-[12px] shrink-0">
                        <span class="material-symbols-outlined text-[14px]">auto_stories</span>
                    </span>
                    <p class="font-label-sm text-label-sm text-on-surface font-semibold truncate">
                        Enjoying the preview? Unlock all {{ $totalPages }} pages
                    </p>
                </div>
                @if($book->price_display ?? '')
                <span class="font-label-sm text-label-sm text-primary font-bold bg-primary-fixed/60 px-2 py-0.5 rounded-full shrink-0">
                    {{ $book->price_display }}
                </span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('books.show', $book->slug) }}"
                   class="flex-1 h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                    <span>Get the Full Book</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </aside>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    var root = document.getElementById('preview-root');
    if (!root) return;

    /* ---------- Reader chrome: page tracking, progress, themes ---------- */
    var pages = Array.prototype.slice.call(document.querySelectorAll('.preview-page'));
    var total = pages.length;
    var pageLabel = document.getElementById('preview-page-label');
    var pagePct = document.getElementById('preview-page-pct');
    var progress = document.getElementById('preview-progress');
    var current = 1;

    function setPage(n) {
        current = Math.max(1, Math.min(total, n));
        if (pageLabel) pageLabel.textContent = 'Page ' + current + ' of ' + total;
        var pct = Math.round((current / total) * 100);
        if (pagePct) pagePct.textContent = pct + '%';
        if (progress) progress.style.width = Math.max(pct, 2) + '%';
    }

    function scrollToPage(n) {
        var el = pages[Math.max(1, Math.min(total, n)) - 1];
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    var prevBtn = document.getElementById('preview-prev');
    var nextBtn = document.getElementById('preview-next');
    if (prevBtn) prevBtn.addEventListener('click', function() { scrollToPage(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { scrollToPage(current + 1); });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var n = parseInt(entry.target.getAttribute('data-page'), 10);
                    if (n) setPage(n);
                }
            });
        }, { rootMargin: '-40% 0px -40% 0px' });
        pages.forEach(function(p) { observer.observe(p); });
    }

    // Reading themes: paper tone of the canvas behind the pages.
    var themes = [
        { name: 'light', bg: '#FBF9F5', icon: 'light_mode' },
        { name: 'sepia', bg: '#F4ECD8', icon: 'wb_twilight' },
        { name: 'dark', bg: '#0D1117', icon: 'dark_mode' }
    ];
    var themeIdx = 0;
    var canvas = document.getElementById('preview-canvas');
    var themeBtn = document.getElementById('preview-theme');
    var themeIcon = document.getElementById('preview-theme-icon');
    if (themeBtn && canvas) {
        themeBtn.addEventListener('click', function() {
            themeIdx = (themeIdx + 1) % themes.length;
            canvas.style.backgroundColor = themes[themeIdx].bg;
            if (themeIcon) themeIcon.textContent = themes[themeIdx].icon;
        });
    }

    setPage(1);

    /* ---------- Screenshot deterrents (unchanged behavior) ---------- */
    var pagesWrap = document.getElementById('preview-pages');

    // Disable right-click.
    document.addEventListener('contextmenu', function(e) {
        if (root.contains(e.target)) e.preventDefault();
    });

    // Disable text selection and dragging.
    root.style.userSelect = 'none';
    root.style.webkitUserSelect = 'none';

    // Blur content when tab loses focus (deters capture tools).
    document.addEventListener('visibilitychange', function() {
        if (pagesWrap) pagesWrap.style.filter = document.hidden ? 'blur(20px)' : '';
    });
    window.addEventListener('blur', function() {
        if (pagesWrap) pagesWrap.style.filter = 'blur(20px)';
    });
    window.addEventListener('focus', function() {
        if (pagesWrap) pagesWrap.style.filter = '';
    });

    // Intercept PrintScreen and common screenshot shortcuts where possible.
    document.addEventListener('keydown', function(e) {
        // PrintScreen key.
        if (e.key === 'PrintScreen') {
            e.preventDefault();
            if (pagesWrap) {
                pagesWrap.style.filter = 'blur(20px)';
                setTimeout(function() { pagesWrap.style.filter = ''; }, 1500);
            }
        }
        // Ctrl/Cmd+P (print), Ctrl/Cmd+S (save).
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 's')) {
            if (root.contains(e.target) || e.target === document.body) {
                e.preventDefault();
            }
        }
    });

    // Block print.
    window.addEventListener('beforeprint', function(e) {
        e.preventDefault();
        return false;
    });
})();
</script>
<style>
@media print {
    #preview-root { display: none !important; }
}
.preview-page img {
    -webkit-user-drag: none;
    user-select: none;
}
</style>
@endpush
@endsection
