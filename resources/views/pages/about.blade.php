{{-- About us — Stitch editorial design. Content is the admin-editable Page model
     (escaped); marketing sections below use real store facts only. --}}
@extends('layouts.stitch')

@section('title', $page->title.' — MyEbookBazaar')
@section('meta_description', 'About MyEbookBazaar: simple, affordable ebooks on fitness, skills and money.')

@section('content')
<div class="st-page w-full bg-surface">
    {{-- Editorial hero --}}
    <section class="w-full max-w-[1120px] mx-auto px-4 sm:px-6 pt-12 pb-16 lg:py-20 relative overflow-hidden">
        <div class="absolute -top-24 right-0 w-96 h-96 bg-primary-container/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute top-1/2 -left-20 w-80 h-80 bg-secondary-container/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="flex flex-col items-start max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-surface-container text-secondary mb-6 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                <span class="font-label-md text-label-md uppercase tracking-wider font-semibold text-secondary">Our Purpose &amp; Editorial Ethos</span>
            </div>
            <h1 class="font-display-lg text-display-lg-mobile sm:text-display-lg text-on-surface tracking-tight leading-[1.08] mb-6">
                {{ $page->title }}
            </h1>
            @if(trim($page->content) !== '')
                <div class="font-body-lg text-body-lg text-on-surface-variant max-w-[70ch] leading-relaxed space-y-4">
                    {!! nl2br(e($page->content)) !!}
                </div>
            @else
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-[70ch] leading-relaxed">
                    MyEbookBazaar is an online bookstore selling short, practical ebooks on fitness, skills and money —
                    written in simple language and priced honestly.
                </p>
            @endif
            <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-on-surface-variant font-label-md text-label-md">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">verified</span>
                    <span>100% DRM-Free Formats</span>
                </div>
                <div class="w-1.5 h-1.5 rounded-full bg-outline-variant"></div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">bolt</span>
                    <span>Instant Delivery</span>
                </div>
                <div class="w-1.5 h-1.5 rounded-full bg-outline-variant"></div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-primary">payments</span>
                    <span>Zero Subscriptions</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Why we started --}}
    <section class="w-full bg-surface-container-low/60 py-16 lg:py-24">
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6">
            <div class="flex flex-col lg:flex-row gap-12 lg:gap-16 items-start">
                <div class="lg:w-5/12 flex-shrink-0">
                    <span class="font-label-sm text-label-sm uppercase tracking-widest text-primary font-bold">Why We Started</span>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mt-2 mb-4 leading-tight">
                        Good guidance should be short enough to finish and clear enough to act on.
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed max-w-[65ch]">
                        Most advice on the internet is long, complicated, and written for someone else&rsquo;s life.
                        We publish short ebooks that fit real Indian homes — Indian kitchens, home workouts, small budgets —
                        in plain language anyone can follow.
                    </p>
                    <div class="p-6 rounded-DEFAULT bg-surface-container-lowest shadow-sm flex items-center gap-5">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-primary">
                            <span class="material-symbols-outlined text-[26px]">menu_book</span>
                        </div>
                        <div>
                            <div class="font-headline-sm text-headline-sm text-on-surface mb-0.5">Honest Pricing, Always</div>
                            <div class="font-body-sm text-body-sm text-on-surface-variant">Rupees for India, dollars for the rest of the world — pay once, keep forever.</div>
                        </div>
                    </div>
                </div>
                <div class="lg:w-7/12 grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                    <div class="p-8 rounded-DEFAULT bg-surface-container-lowest shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-5">
                                <span class="material-symbols-outlined text-[22px]">close</span>
                            </div>
                            <h3 class="font-headline-md text-headline-md text-on-surface mb-3">The Noise We Reject</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                                Endless jargon, unrealistic routines, and advice that assumes equipment and ingredients
                                most Indian homes simply don&rsquo;t have.
                            </p>
                        </div>
                        <div class="mt-8 pt-4 flex items-center gap-2 font-label-sm text-label-sm text-secondary font-semibold">
                            <span class="material-symbols-outlined text-[18px]">block</span>
                            <span>No padding, no fluff</span>
                        </div>
                    </div>
                    <div class="p-8 rounded-DEFAULT bg-surface-container-lowest shadow-sm flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-primary/5 rounded-full pointer-events-none"></div>
                        <div>
                            <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed mb-5">
                                <span class="material-symbols-outlined text-[22px]">check</span>
                            </div>
                            <h3 class="font-headline-md text-headline-md text-on-surface mb-3">What We Publish</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                                Short, practical handbooks on fitness, skills and money — step-by-step, in simple words,
                                with examples from everyday Indian life.
                            </p>
                        </div>
                        <div class="mt-8 pt-4 flex items-center gap-2 font-label-sm text-label-sm text-primary font-semibold">
                            <span class="material-symbols-outlined text-[18px]">verified</span>
                            <span>Rooted in real life</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Three reader promises (real store facts) --}}
    <section class="w-full max-w-[1120px] mx-auto px-4 sm:px-6 py-20 lg:py-28">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="font-label-sm text-label-sm uppercase tracking-widest text-primary font-bold">Uncompromising Values</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mt-2 mb-4">Our Three Reader Promises</h2>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-[65ch] mx-auto leading-relaxed">
                Publishing digital guides with genuine respect for your attention, privacy, and personal digital bookshelf.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-surface-container-lowest p-8 rounded-DEFAULT shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-surface-container text-secondary flex items-center justify-center mb-6 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">menu_book</span>
                    </div>
                    <div class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-secondary mb-2">Promise 01</div>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-3">100% Practical &amp; Local</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        Designed for real Indian kitchens and schedules. Written in simple, plain language — no jargon walls.
                    </p>
                </div>
                <div class="mt-8 pt-4 flex items-center text-on-surface-variant font-label-sm text-label-sm">
                    <span>Fitness, skills &amp; money guides</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-DEFAULT shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-primary-fixed text-primary flex items-center justify-center mb-6 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">lock_open</span>
                    </div>
                    <div class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-primary mb-2">Promise 02</div>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-3">DRM-Free Lifetime Ownership</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        Once bought, the PDF is yours to keep forever on any device — phone, tablet or computer. No walled apps, no revoked licenses.
                    </p>
                </div>
                <div class="mt-8 pt-4 flex items-center text-on-surface-variant font-label-sm text-label-sm">
                    <span>Re-downloads from your library</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-DEFAULT shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-surface-container text-secondary flex items-center justify-center mb-6 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">bolt</span>
                    </div>
                    <div class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-secondary mb-2">Promise 03</div>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-3">Instant Delivery &amp; Zero Subscriptions</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        One-time fair price, zero recurring charges. Pay securely through Razorpay and get your download link immediately.
                    </p>
                </div>
                <div class="mt-8 pt-4 flex items-center text-on-surface-variant font-label-sm text-label-sm">
                    <span>Razorpay secure instant checkout</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Support / contact strip (real contact points only) --}}
    <section class="w-full max-w-[1120px] mx-auto px-4 sm:px-6 pb-16 lg:pb-24">
        <div class="bg-surface-container-low rounded-xl p-8 sm:p-12 shadow-sm flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest text-secondary font-label-sm text-label-sm mb-4">
                    <span class="material-symbols-outlined text-[16px] text-primary">support_agent</span>
                    <span>Talk to a Human</span>
                </div>
                <h3 class="font-headline-lg text-headline-lg text-on-surface mb-3">Questions before you buy?</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed max-w-[65ch]">
                    Our support team reads every message. Ask about a book, a download, or an order — we reply within 2 working days.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-8 font-body-sm text-body-sm text-on-surface">
                    <div>
                        <div class="text-on-surface-variant font-label-sm text-label-sm uppercase mb-1">Support Email</div>
                        @php($supportEmail = settings('site.support_email', 'support@myebookbazaar.com'))
                        <a class="text-primary font-medium hover:underline flex items-center gap-1.5" href="mailto:{{ $supportEmail }}">
                            <span class="material-symbols-outlined text-[16px]">mail</span>
                            {{ $supportEmail }}
                        </a>
                    </div>
                    <div>
                        <div class="text-on-surface-variant font-label-sm text-label-sm uppercase mb-1">Contact Form</div>
                        <a class="text-primary font-medium hover:underline flex items-center gap-1.5" href="{{ route('contact') }}">
                            <span class="material-symbols-outlined text-[16px]">forum</span>
                            Send us a message
                        </a>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-auto p-6 rounded-DEFAULT bg-surface-container-lowest shadow-sm flex flex-col gap-4 min-w-[280px]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-secondary-container text-secondary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">auto_stories</span>
                    </div>
                    <div>
                        <div class="font-headline-sm text-headline-sm text-on-surface leading-tight">MyEbookBazaar</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">Fitness • Skills • Money</div>
                    </div>
                </div>
                <div class="h-px w-full bg-surface-container"></div>
                <div class="flex items-center justify-between text-on-surface-variant font-label-sm text-label-sm">
                    <span>Digital delivery</span>
                    <span class="font-semibold text-primary">Instant, 24x7</span>
                </div>
                <div class="flex items-center justify-between text-on-surface-variant font-label-sm text-label-sm">
                    <span>Reading format</span>
                    <span class="font-semibold text-on-surface">DRM-free PDF</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="w-full bg-inverse-surface text-inverse-on-surface py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-primary-container/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-tertiary-container/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6 relative z-10 text-center flex flex-col items-center">
            <span class="font-label-sm text-label-sm uppercase tracking-widest text-primary-fixed-dim font-bold mb-4">Start Your Library</span>
            <h2 class="font-display-lg text-display-lg-mobile sm:text-display-lg max-w-3xl leading-tight mb-6">
                Practical guides, built for the way you actually live.
            </h2>
            <p class="font-body-lg text-body-lg text-inverse-on-surface/80 max-w-[65ch] mb-10 leading-relaxed">
                Choose your first ebook today. Every purchase includes a DRM-free PDF ready for any phone, tablet, or computer.
            </p>
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <a class="inline-flex items-center justify-center gap-2.5 h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-md hover:shadow-lg" href="{{ route('books') }}">
                    <span>Browse the catalog</span>
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </a>
                <a class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-full bg-transparent text-inverse-on-surface hover:bg-surface-container-highest/20 font-label-md text-label-md transition-colors" href="{{ route('combos') }}">
                    <span>View discounted bundles</span>
                    <span class="material-symbols-outlined text-[18px]">local_offer</span>
                </a>
            </div>
            <div class="mt-12 flex flex-wrap justify-center items-center gap-8 text-inverse-on-surface/60 font-label-sm text-label-sm">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-primary-fixed-dim">done_all</span>
                    <span>Instant Download</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-primary-fixed-dim">verified</span>
                    <span>Secure Razorpay Checkout</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-primary-fixed-dim">devices</span>
                    <span>Read on Any Device</span>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
