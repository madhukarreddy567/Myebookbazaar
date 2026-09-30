{{-- FAQ — Stitch-styled accordion. Items come from PageController@faq (parsed from
     the admin-editable FAQ page content); no content is invented here. --}}
@extends('layouts.stitch')

@section('title', $page->title.' — MyEbookBazaar')
@section('meta_description', 'Frequently asked questions about buying ebooks on MyEbookBazaar.')

@section('content')
<div class="st-page w-full bg-surface">
    <div class="w-full max-w-[760px] mx-auto px-4 sm:px-6 py-10 md:py-16">
        {{-- Header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container/60 text-secondary mb-4">
                <span class="material-symbols-outlined text-[16px]">help</span>
                <span class="font-label-sm text-label-sm uppercase tracking-wider font-semibold">Help Center</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">{{ $page->title }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">Short answers before you buy.</p>
        </div>

        {{-- Accordion --}}
        @if($items)
        <div class="bg-surface-container-lowest rounded-DEFAULT shadow-sm overflow-hidden" x-data="{ open: null }">
            @foreach($items as $i => $item)
            <div class="{{ $i > 0 ? 'border-t border-outline-variant/40' : '' }}">
                <h2>
                    <button type="button"
                            @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                            :aria-expanded="open === {{ $i }}"
                            aria-controls="faq-panel-{{ $i }}" id="faq-btn-{{ $i }}"
                            class="w-full flex items-center justify-between gap-4 text-left px-5 sm:px-6 py-4 sm:py-5 hover:bg-surface-container-low/50 transition-colors">
                        <span class="font-label-md text-label-md font-semibold text-on-surface">{{ $item['q'] }}</span>
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-all duration-200"
                              :class="open === {{ $i }} ? 'bg-primary-container text-on-primary' : 'bg-surface-container text-on-surface-variant'">
                            <span class="material-symbols-outlined text-[20px] transition-transform duration-200"
                                  :class="open === {{ $i }} ? 'rotate-180' : ''">expand_more</span>
                        </span>
                    </button>
                </h2>
                <div id="faq-panel-{{ $i }}" role="region" aria-labelledby="faq-btn-{{ $i }}"
                     x-show="open === {{ $i }}" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <p class="px-5 sm:px-6 pb-5 sm:pb-6 font-body-md text-body-md text-on-surface-variant leading-relaxed max-w-[65ch]">{{ $item['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-surface-container-lowest rounded-DEFAULT shadow-sm p-10 text-center">
            <div class="w-12 h-12 rounded-full bg-surface-container text-on-surface-variant flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[24px]">info</span>
            </div>
            <p class="font-headline-sm text-headline-sm text-on-surface">No questions yet</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Check back soon — answers are being written.</p>
        </div>
        @endif

        {{-- Still stuck? CTA --}}
        <div class="rounded-DEFAULT bg-surface-container-highest p-6 sm:p-8 mt-10 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm">
            <div class="space-y-1.5 text-center sm:text-left">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Still have a question?</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant max-w-md">
                    Our support team reads every message and replies within 2 working days.
                </p>
            </div>
            <a class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm shrink-0" href="{{ route('contact') }}">
                <span class="material-symbols-outlined text-[18px]">support_agent</span>
                <span>Contact Support</span>
            </a>
        </div>
    </div>
</div>
@endsection
