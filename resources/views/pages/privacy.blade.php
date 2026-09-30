{{-- Privacy policy (designed static fallback; PageController shows this when
     the admin has not saved custom legal text in the Settings Center). --}}
@extends('layouts.stitch')

@section('title', 'Privacy Policy — MyEbookBazaar')
@section('meta_description', 'Privacy policy of MyEbookBazaar: what data we collect and how we use it.')

@section('content')
@component('pages.partials.policy-shell', ['active' => 'privacy'])
    @slot('title')
        Privacy Policy
    @endslot
    @slot('subtitle')
        Unambiguous guidelines on what we collect, how we use it, and what we never do with your data.
    @endslot

    <section class="scroll-mt-28 bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex items-center gap-3 pb-4">
            <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-secondary flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">shield</span>
            </div>
            <div>
                <span class="font-label-sm text-label-sm text-secondary uppercase font-bold tracking-wider">Privacy Protection</span>
                <h2 class="font-headline-md text-headline-md text-on-surface">Privacy Policy &amp; Data Practices</h2>
            </div>
        </div>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-[70ch]">
            At MyEbookBazaar, we believe your personal collection and reading interests are private.
            We practise data minimalism: we collect only what is strictly needed to run your account
            and deliver your ebooks.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-DEFAULT bg-surface-container-low space-y-2">
                <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-2">
                    <span class="material-symbols-outlined text-[18px]">badge</span>
                </div>
                <h4 class="font-headline-sm text-headline-sm text-[17px] text-on-surface">Minimal Info</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Only your name, email address and country are recorded — to deliver download links and tax receipts.
                </p>
            </div>
            <div class="p-4 rounded-DEFAULT bg-surface-container-low space-y-2">
                <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-2">
                    <span class="material-symbols-outlined text-[18px]">lock_clock</span>
                </div>
                <h4 class="font-headline-sm text-headline-sm text-[17px] text-on-surface">Tokenized Payments</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Razorpay processes all UPI, card and net-banking payments. Sensitive payment details never touch our servers.
                </p>
            </div>
            <div class="p-4 rounded-DEFAULT bg-surface-container-low space-y-2">
                <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-2">
                    <span class="material-symbols-outlined text-[18px]">mark_email_read</span>
                </div>
                <h4 class="font-headline-sm text-headline-sm text-[17px] text-on-surface">Zero Marketing Spam</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    We never sell, auction or share your data with ad brokers. We only send transactional emails — receipts, download links and password resets.
                </p>
            </div>
        </div>

        <div class="space-y-3 pt-2">
            <h4 class="font-label-md text-label-md text-on-surface font-semibold">How your details are used</h4>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-[70ch]">
                Your details are used to create your account, process your orders, deliver downloads and send
                important emails like order receipts and password resets — nothing more.
            </p>
        </div>

        <div class="space-y-3 pt-2">
            <h4 class="font-label-md text-label-md text-on-surface font-semibold">Data retention &amp; right to erasure</h4>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-[70ch]">
                Order records are kept for accounting and to keep your lifetime library working.
                You can ask for a copy or deletion of your personal data at any time by writing to us
                via the <a href="{{ route('contact') }}" class="text-tertiary font-medium hover:underline">Contact page</a>;
                we act on such requests as required by law. Please note that deleting your account also
                removes access to your download links.
            </p>
        </div>
    </section>
@endcomponent
@endsection
