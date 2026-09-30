{{-- No-refund policy (designed static fallback; PageController shows this when
     the admin has not saved custom legal text in the Settings Center). --}}
@extends('layouts.stitch')

@section('title', 'No-Refund Policy — MyEbookBazaar')
@section('meta_description', 'No-refund policy of MyEbookBazaar: all sales are final because ebooks are digital products.')

@section('content')
@component('pages.partials.policy-shell', ['active' => 'no-refund'])
    @slot('title')
        No-Refund Policy
    @endslot
    @slot('subtitle')
        Unambiguous guidelines regarding digital licenses, payment security, and fair resolution paths for our digital editions.
    @endslot

    <section class="scroll-mt-28 bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-fixed/50 flex items-center justify-center text-primary flex-shrink-0">
                    <span class="material-symbols-outlined text-[22px]">assignment_return</span>
                </div>
                <div>
                    <span class="font-label-sm text-label-sm text-primary uppercase font-bold tracking-wider">Priority Notice</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface">No-Refund Policy (Digital Goods)</h2>
                </div>
            </div>
            <span class="self-start sm:self-center px-3 py-1 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
                Final Sale
            </span>
        </div>

        <div class="space-y-4 font-body-md text-body-md text-on-surface-variant max-w-[70ch]">
            <p>
                Due to the nature of digital media,
                <strong class="text-on-surface font-semibold">all sales on MyEbookBazaar are final</strong>
                once the download is delivered. Please read the book description carefully before you pay.
            </p>
            <p>
                Unlike physical books, a digital ebook cannot be returned — instant delivery gives you permanent
                possession of the file the moment your payment succeeds. That is why we ask you to confirm
                this policy at checkout, every time.
            </p>
        </div>

        <div class="bg-surface-container-low rounded-DEFAULT p-5 space-y-3">
            <h3 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">help_outline</span>
                Legitimate Resolution Exceptions
            </h3>
            <p class="font-body-sm text-body-sm text-on-surface-variant max-w-[70ch]">
                We stand behind the technical integrity of our store. If something went wrong on our side,
                we will make it right:
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <div class="bg-surface-container-lowest p-3.5 rounded-DEFAULT shadow-xs">
                    <div class="flex items-center gap-2 text-on-surface font-label-md text-label-md font-semibold mb-1">
                        <span class="material-symbols-outlined text-error text-[18px]">credit_card_off</span>
                        Duplicate Accidental Charges
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        A network glitch caused the same order to be charged twice on your card or UPI.
                    </p>
                </div>
                <div class="bg-surface-container-lowest p-3.5 rounded-DEFAULT shadow-xs">
                    <div class="flex items-center gap-2 text-on-surface font-label-md text-label-md font-semibold mb-1">
                        <span class="material-symbols-outlined text-primary-container text-[18px]">broken_image</span>
                        Corrupted File Delivery
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        The delivered file is incomplete or cannot be opened in standard PDF readers.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h4 class="font-label-md text-label-md uppercase tracking-wider text-secondary font-semibold">How to Report an Issue</h4>
            <div class="space-y-2.5">
                <div class="flex items-start gap-3 text-body-sm text-on-surface-variant">
                    <span class="w-6 h-6 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                    <span>Write to us via the <a href="{{ route('contact') }}" class="text-tertiary font-medium hover:underline">Contact page</a> as soon as you notice the problem.</span>
                </div>
                <div class="flex items-start gap-3 text-body-sm text-on-surface-variant">
                    <span class="w-6 h-6 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                    <span>Include your order details and the email address you used at checkout so we can find your purchase.</span>
                </div>
                <div class="flex items-start gap-3 text-body-sm text-on-surface-variant">
                    <span class="w-6 h-6 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                    <span>If possible, attach a screenshot showing the error or the duplicate charge.</span>
                </div>
            </div>
        </div>
    </section>
@endcomponent
@endsection
