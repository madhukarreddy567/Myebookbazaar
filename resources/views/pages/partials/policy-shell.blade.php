{{-- Shared Stitch shell for the policy pages: editorial header, sticky TOC
     linking to all three policy routes, reader-summary card, and a support
     banner under the body.
     Props: 'active' => 'privacy'|'terms'|'no-refund'|''.
     Slots: 'title', 'subtitle', and the default body slot. --}}
@php($supportEmail = settings('site.support_email', 'support@myebookbazaar.com'))
<div class="st-page w-full max-w-[1120px] mx-auto px-4 sm:px-6 py-10 md:py-16">
    {{-- Top editorial header --}}
    <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container/60 text-secondary mb-3">
                <span class="material-symbols-outlined text-[16px]">verified_user</span>
                <span class="font-label-sm text-label-sm uppercase tracking-wider font-semibold">Transparency &amp; Consumer Safety</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">{{ $title }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 leading-relaxed">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Sticky table of contents --}}
        <aside class="lg:col-span-4 xl:col-span-3 lg:sticky lg:top-24 space-y-6">
            <div class="bg-surface-container-lowest rounded-DEFAULT p-5 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-3">
                    <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-secondary">Table of Contents</span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant/80">3 policies</span>
                </div>
                <nav class="space-y-1.5" aria-label="Policies">
                    <a class="group flex items-center justify-between px-3 py-2.5 rounded-DEFAULT font-label-md text-label-md transition-all {{ ($active ?? '') === 'no-refund' ? 'bg-primary-fixed/40 text-on-primary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-medium' }}" href="{{ route('no-refund') }}">
                        <span class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[18px] {{ ($active ?? '') === 'no-refund' ? 'text-primary' : 'text-secondary' }}">assignment_return</span>
                            <span>No-Refund Policy</span>
                        </span>
                        <span class="material-symbols-outlined text-[16px] {{ ($active ?? '') === 'no-refund' ? 'text-primary' : 'text-outline-variant' }} transition-transform group-hover:translate-x-0.5">chevron_right</span>
                    </a>
                    <a class="group flex items-center justify-between px-3 py-2.5 rounded-DEFAULT font-label-md text-label-md transition-all {{ ($active ?? '') === 'privacy' ? 'bg-primary-fixed/40 text-on-primary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-medium' }}" href="{{ route('privacy') }}">
                        <span class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[18px] {{ ($active ?? '') === 'privacy' ? 'text-primary' : 'text-secondary' }}">shield</span>
                            <span>Privacy &amp; Data Rights</span>
                        </span>
                        <span class="material-symbols-outlined text-[16px] {{ ($active ?? '') === 'privacy' ? 'text-primary' : 'text-outline-variant' }} transition-transform group-hover:translate-x-0.5">chevron_right</span>
                    </a>
                    <a class="group flex items-center justify-between px-3 py-2.5 rounded-DEFAULT font-label-md text-label-md transition-all {{ ($active ?? '') === 'terms' ? 'bg-primary-fixed/40 text-on-primary-container font-semibold' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface font-medium' }}" href="{{ route('terms') }}">
                        <span class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[18px] {{ ($active ?? '') === 'terms' ? 'text-primary' : 'text-secondary' }}">contract</span>
                            <span>Terms of Service</span>
                        </span>
                        <span class="material-symbols-outlined text-[16px] {{ ($active ?? '') === 'terms' ? 'text-primary' : 'text-outline-variant' }} transition-transform group-hover:translate-x-0.5">chevron_right</span>
                    </a>
                </nav>
                <div class="pt-4 space-y-3">
                    <div class="p-3 bg-surface-container-low rounded-DEFAULT">
                        <div class="flex items-start gap-2.5">
                            <span class="material-symbols-outlined text-primary text-[18px] mt-0.5">policy</span>
                            <div>
                                <p class="font-label-sm text-label-sm font-semibold text-on-surface">Legal Compliance</p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant text-[13px] leading-tight mt-0.5">Indian Copyright Act 1957 &amp; IT Act 2000 compliant.</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1 text-center sm:text-left">
                        <p class="font-label-sm text-label-sm text-on-surface-variant">Questions regarding our policies?</p>
                        <a class="inline-flex items-center gap-1.5 font-label-md text-label-md font-semibold text-primary hover:text-on-primary-container transition-colors" href="mailto:{{ $supportEmail }}">
                            <span class="material-symbols-outlined text-[16px]">mail</span>
                            <span>{{ $supportEmail }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-inverse-surface text-inverse-on-surface p-5 rounded-DEFAULT shadow-sm">
                <div class="flex items-center gap-2 mb-2 text-primary-fixed-dim">
                    <span class="material-symbols-outlined text-[18px]">verified</span>
                    <span class="font-label-sm text-label-sm uppercase tracking-wider font-semibold">Reader Summary</span>
                </div>
                <p class="font-body-sm text-body-sm text-inverse-on-surface/80 leading-relaxed">
                    All ebooks are delivered instantly as DRM-free PDFs for your personal devices. Sales are final upon delivery, safeguarded by rapid assistance if technical issues arise.
                </p>
            </div>
        </aside>

        {{-- Document body --}}
        <div class="lg:col-span-8 xl:col-span-9 max-w-[780px] w-full">
            <div class="space-y-12">
                {{ $slot }}
            </div>

            {{-- Bottom assistance banner --}}
            <div class="rounded-DEFAULT bg-surface-container-highest p-6 sm:p-8 mt-12 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm">
                <div class="space-y-1.5 text-center sm:text-left">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Have questions about your purchase or these terms?</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant max-w-md">
                        Our support team is on standby to help with delivery, receipts, or technical inquiries.
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <a class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('contact') }}">
                        <span class="material-symbols-outlined text-[18px]">support_agent</span>
                        <span>Contact Support</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
