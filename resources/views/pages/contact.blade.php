@extends('layouts.stitch')

@section('title', 'Contact us — MyEbookBazaar')
@section('meta_description', 'Contact MyEbookBazaar support.')

@php
$supportEmail = trim((string) settings('site.support_email', 'support@myebookbazaar.com'));
$supportPhone = trim((string) settings('site.contact_phone', ''));
$supportAddress = trim((string) settings('site.address', ''));
@endphp

@section('content')
<div class="st-page w-full max-w-[1040px] mx-auto px-4 sm:px-6 py-10 md:py-14">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10 pb-6 border-b border-surface-variant/40">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-fixed/40 text-on-primary-fixed-variant font-label-sm text-label-sm mb-3">
                <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                Reader Support · Average reply within 2 working days
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Contact Reader Support</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-xl">
                Have a question about your download or order? Write to us — we typically respond within 2 working days.
            </p>
        </div>
        <div class="hidden lg:flex items-center gap-3 text-secondary bg-surface-container-low px-4 py-2.5 rounded-xl">
            <span class="material-symbols-outlined text-primary-container text-[22px]">verified_user</span>
            <span class="font-label-md text-label-md text-on-secondary-container">Human technical assistance</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Form --}}
        <section class="lg:col-span-7 bg-surface-container-lowest p-6 sm:p-8 rounded-xl shadow-sm">
            <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="fullName">
                        Full Name <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[20px] pointer-events-none">person</span>
                        <input class="w-full h-12 pl-11 pr-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all @error('name') ring-2 ring-error/50 @enderror" id="fullName" name="name" placeholder="Your name" required maxlength="100" type="text" value="{{ old('name') }}" autocomplete="name"/>
                    </div>
                    @error('name')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="emailAddress">
                        Email Address <span class="font-normal text-on-surface-variant text-[13px]">(use your purchase email for order queries)</span> <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[20px] pointer-events-none">mail</span>
                        <input class="w-full h-12 pl-11 pr-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all @error('email') ring-2 ring-error/50 @enderror" id="emailAddress" name="email" placeholder="you@example.com" required maxlength="255" type="email" value="{{ old('email') }}" autocomplete="email"/>
                    </div>
                    @error('email')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="subjectField">
                        Subject <span class="font-normal text-on-surface-variant text-[13px]">(optional)</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[20px] pointer-events-none">tag</span>
                        <input class="w-full h-12 pl-11 pr-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all @error('subject') ring-2 ring-error/50 @enderror" id="subjectField" name="subject" placeholder="e.g. Download issue with my ebook" maxlength="150" type="text" value="{{ old('subject') }}"/>
                    </div>
                    @error('subject')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="messageContent">
                        Message <span class="text-error">*</span>
                    </label>
                    <textarea class="w-full p-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all resize-none @error('message') ring-2 ring-error/50 @enderror" id="messageContent" name="message" placeholder="Describe what you need help with..." required maxlength="5000" rows="4">{{ old('message') }}</textarea>
                    @error('message')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>

                <x-security.honeypot />

                <x-turnstile form="contact" />

                <div class="pt-2">
                    <button class="w-full sm:w-auto min-w-[200px] h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold tracking-wide hover:bg-primary shadow-sm active:scale-[0.99] transition-all inline-flex items-center justify-center gap-2 cursor-pointer" type="submit">
                        <span>Send Message</span>
                        <span class="material-symbols-outlined text-[18px]">send</span>
                    </button>
                    <p class="font-body-sm text-body-sm text-on-surface-variant/80 mt-3 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-primary">shield</span>
                        No spam. Your details are only used to answer your query.
                    </p>
                </div>
            </form>
        </section>

        {{-- Side panel --}}
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-surface-container-lowest p-6 sm:p-7 rounded-xl shadow-sm">
                <div class="flex items-center gap-2.5 mb-5 pb-4 border-b border-surface-variant/40">
                    <span class="material-symbols-outlined text-primary text-[24px]">support_agent</span>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Direct Desk Details</h2>
                </div>
                <div class="space-y-4 font-body-md text-body-md">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-full bg-secondary-container/60 flex items-center justify-center text-on-secondary-container shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[19px]">mail</span>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant font-medium">Reader Support Email</p>
                            <a class="text-on-surface font-semibold hover:text-primary transition-colors block" href="mailto:{{ $supportEmail }}">
                                {{ $supportEmail }}
                            </a>
                            <span class="font-body-sm text-body-sm text-on-surface-variant/75">Replies within 2 working days</span>
                        </div>
                    </div>
                    @if($supportPhone !== '')
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-full bg-secondary-container/60 flex items-center justify-center text-on-secondary-container shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[19px]">call</span>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant font-medium">Phone</p>
                            <a class="text-on-surface font-semibold hover:text-primary transition-colors block" href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">
                                {{ $supportPhone }}
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($supportAddress !== '')
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-full bg-secondary-container/60 flex items-center justify-center text-on-secondary-container shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[19px]">pin_drop</span>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant font-medium">Operating Office</p>
                            <p class="text-on-surface leading-snug font-medium">
                                MyEbookBazaar
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant/80">
                                {{ $supportAddress }}
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-surface-container-low/70 p-6 sm:p-7 rounded-xl">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-primary text-[20px]">bolt</span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold text-[18px]">Quick Self-Help</h3>
                </div>
                <div class="space-y-3">
                    <a class="group flex items-start gap-3 p-3 rounded-lg bg-surface-container-lowest/80 hover:bg-surface-container-lowest transition-all" href="{{ route('faq') }}">
                        <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5 shrink-0 group-hover:text-primary-container transition-colors">menu_book</span>
                        <div class="flex-1">
                            <p class="font-label-md text-label-md text-on-surface font-medium group-hover:text-primary transition-colors">Download or reading trouble?</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Check the FAQ for Kindle and PDF setup guides.</p>
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant/40 group-hover:translate-x-0.5 transition-transform text-[18px]">chevron_right</span>
                    </a>
                    <a class="group flex items-start gap-3 p-3 rounded-lg bg-surface-container-lowest/80 hover:bg-surface-container-lowest transition-all" href="{{ route('library') }}">
                        <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5 shrink-0 group-hover:text-primary-container transition-colors">cloud_download</span>
                        <div class="flex-1">
                            <p class="font-label-md text-label-md text-on-surface font-medium group-hover:text-primary transition-colors">Didn't receive your files?</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Access your ebooks any time from My Library.</p>
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant/40 group-hover:translate-x-0.5 transition-transform text-[18px]">chevron_right</span>
                    </a>
                    <a class="group flex items-start gap-3 p-3 rounded-lg bg-surface-container-lowest/80 hover:bg-surface-container-lowest transition-all" href="{{ route('no-refund') }}">
                        <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5 shrink-0 group-hover:text-primary-container transition-colors">gavel</span>
                        <div class="flex-1">
                            <p class="font-label-md text-label-md text-on-surface font-medium group-hover:text-primary transition-colors">Refund policy question?</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Review terms &amp; replacement coverage for corrupted files.</p>
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant/40 group-hover:translate-x-0.5 transition-transform text-[18px]">chevron_right</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-12 p-6 rounded-xl bg-surface-container-low flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4 text-center md:text-left">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0 mx-auto">
                <span class="material-symbols-outlined text-[26px]">mark_email_read</span>
            </div>
            <div>
                <h4 class="font-headline-sm text-headline-sm text-on-surface text-[17px]">Instant delivery on every order</h4>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Paid ebooks unlock immediately in your library with signed download links.</p>
            </div>
        </div>
        <a class="h-10 px-5 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary/90 transition-colors whitespace-nowrap inline-flex items-center gap-2" href="{{ route('books') }}">
            <span>Browse Catalog</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>
</div>
@endsection
