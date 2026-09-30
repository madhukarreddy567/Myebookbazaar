{{-- Ebook preview reader with screenshot deterrents.
     - Pages served as images (no raw PDF access).
     - Right-click, text selection, drag, and print disabled.
     - Watermark overlay on every page.
     - Content blurs when tab loses focus (defeats simple screen capture tools).
     - Keyboard shortcuts for screenshots (PrintScreen) are intercepted where possible.
--}}
@extends('layouts.app')

@section('title', 'Preview: ' . $book->title . ' — MyEbookBazaar')
@section('meta_description', 'Preview the first ' . $previewPages . ' pages of ' . $book->title . '.')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" id="preview-root">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-mutedtext uppercase tracking-wide">Preview</p>
            <h1 class="font-display text-2xl font-bold">{{ $book->title }}</h1>
            <p class="text-sm text-mutedtext mt-1">
                Showing first {{ $previewPages }} of {{ $totalPages }} pages
            </p>
        </div>
        <a href="{{ route('books.show', $book->slug) }}" class="x-ui-button">
            Buy now — {{ $book->price_display ?? '' }}
        </a>
    </div>

    <div id="preview-pages" class="space-y-6 select-none">
        @for($i = 1; $i <= $previewPages; $i++)
            <div class="preview-page relative bg-white rounded-xl shadow-lg overflow-hidden" data-page="{{ $i }}">
                <img src="{{ route('books.preview.page', ['slug' => $book->slug, 'page' => $i]) }}"
                     alt="Preview page {{ $i }} of {{ $book->title }}"
                     class="w-full h-auto block pointer-events-none"
                     draggable="false"
                     oncontextmenu="return false;">
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

    <div class="mt-8 text-center p-6 bg-teal-900/5 rounded-2xl">
        <p class="font-semibold text-lg">Enjoying the preview?</p>
        <p class="text-mutedtext text-sm mt-1 mb-4">Get the full {{ $totalPages }}-page ebook with instant download.</p>
        <a href="{{ route('books.show', $book->slug) }}" class="x-ui-button">
            Get the full book
        </a>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    var root = document.getElementById('preview-root');
    if (!root) return;

    // Disable right-click.
    document.addEventListener('contextmenu', function(e) {
        if (root.contains(e.target)) e.preventDefault();
    });

    // Disable text selection and dragging.
    root.style.userSelect = 'none';
    root.style.webkitUserSelect = 'none';

    // Blur content when tab loses focus (deters capture tools).
    var pages = document.getElementById('preview-pages');
    document.addEventListener('visibilitychange', function() {
        if (pages) pages.style.filter = document.hidden ? 'blur(20px)' : '';
    });
    window.addEventListener('blur', function() {
        if (pages) pages.style.filter = 'blur(20px)';
    });
    window.addEventListener('focus', function() {
        if (pages) pages.style.filter = '';
    });

    // Intercept PrintScreen and common screenshot shortcuts where possible.
    document.addEventListener('keydown', function(e) {
        // PrintScreen key.
        if (e.key === 'PrintScreen') {
            e.preventDefault();
            if (pages) {
                pages.style.filter = 'blur(20px)';
                setTimeout(function() { pages.style.filter = ''; }, 1500);
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
