{{-- Terms of service (designed static fallback; PageController shows this when
     the admin has not saved custom legal text in the Settings Center). --}}
@extends('layouts.stitch')

@section('title', 'Terms of Service — MyEbookBazaar')
@section('meta_description', 'Terms of service of MyEbookBazaar.')

@section('content')
@component('pages.partials.policy-shell', ['active' => 'terms'])
    @slot('title')
        Terms of Service
    @endslot
    @slot('subtitle')
        The simple rules of the store: using your account, your ebook license, and what keeps the bazaar fair for everyone.
    @endslot

    <section class="scroll-mt-28 bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex items-center gap-3 pb-4">
            <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-secondary flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">contract</span>
            </div>
            <div>
                <span class="font-label-sm text-label-sm text-secondary uppercase font-bold tracking-wider">Digital Licensing</span>
                <h2 class="font-headline-md text-headline-md text-on-surface">Terms of Service &amp; Usage Rights</h2>
            </div>
        </div>

        <div class="space-y-4 font-body-md text-body-md text-on-surface-variant max-w-[70ch]">
            <p>
                When you purchase an ebook through MyEbookBazaar, you acquire a non-exclusive, non-transferable
                <strong class="text-on-surface font-semibold">Single-User Personal License</strong>.
                All books, recipes, charts and associated media are protected under the
                <strong class="text-on-surface font-semibold">Indian Copyright Act, 1957</strong>.
            </p>
            <p>
                By using the store you agree to these terms. You must provide accurate details when creating
                an account, and you must be old enough — or have permission — to make online payments in your country.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <div class="p-5 rounded-DEFAULT bg-surface-container-low space-y-3">
                <div class="flex items-center gap-2 text-on-surface font-headline-sm text-headline-sm text-[18px]">
                    <span class="material-symbols-outlined text-secondary text-[22px]">check_circle</span>
                    Permitted Uses
                </div>
                <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-secondary mt-0.5">done</span>
                        <span>Reading and storing files on your own personal devices — phone, tablet, laptop or e-reader.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-secondary mt-0.5">done</span>
                        <span>Printing a single personal copy for your own kitchen or wellness journaling.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-secondary mt-0.5">done</span>
                        <span>Offline, DRM-free reading in any standard PDF reader — no special app required.</span>
                    </li>
                </ul>
            </div>
            <div class="p-5 rounded-DEFAULT bg-surface-container-low space-y-3">
                <div class="flex items-center gap-2 text-on-surface font-headline-sm text-headline-sm text-[18px]">
                    <span class="material-symbols-outlined text-error text-[22px]">cancel</span>
                    Prohibited Uses
                </div>
                <ul class="space-y-2.5 font-body-sm text-body-sm text-on-surface-variant">
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-error mt-0.5">close</span>
                        <span>Re-hosting or sharing files on public sites, torrents, messaging channels or cloud drives.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-error mt-0.5">close</span>
                        <span>Reselling, sub-licensing, or using the content in paid products or subscriptions.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[16px] text-error mt-0.5">close</span>
                        <span>Modifying the text or removing author attributions and publication marks.</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="p-4 rounded-DEFAULT bg-surface-container space-y-2">
            <h4 class="font-label-md text-label-md font-semibold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">receipt_long</span>
                Sales Are Final
            </h4>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-[70ch]">
                Because ebooks are delivered instantly as digital downloads, all sales are final.
                At checkout you confirm that you have read and accepted our
                <a href="{{ route('no-refund') }}" class="text-tertiary font-medium hover:underline">No-Refund Policy</a>.
            </p>
        </div>
    </section>
@endcomponent
@endsection
