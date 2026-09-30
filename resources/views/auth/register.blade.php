{{-- Customer registration — Stitch design: tabbed auth card, register tab active. --}}
@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Create Account — MyEbookBazaar')
@section('meta_description', 'Create your free MyEbookBazaar account. Instant digital delivery of your purchased ebooks to your personal shelf.')

@section('content')
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[420px] mx-auto bg-surface-container-lowest rounded-[16px] shadow-sm p-6 sm:p-8">
        {{-- Top pill segmented switch: register tab active here --}}
        <div class="bg-surface-container p-1 rounded-full flex items-center justify-between mb-8">
            <a href="{{ route('login') }}" class="flex-1 py-2 text-center rounded-full font-label-md text-label-md font-medium text-on-surface-variant hover:text-on-surface transition-all duration-200">
                Log In
            </a>
            <span class="flex-1 py-2 text-center rounded-full font-label-md text-label-md font-semibold bg-surface-container-lowest text-on-surface shadow-sm" aria-current="page">
                Create Account
            </span>
        </div>

        {{-- Header context --}}
        <div class="mb-6 text-center">
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Create your MyEbookBazaar Account</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Start reading digital editions immediately</p>
        </div>

        {{-- Social auth CTA (renders only when Google OAuth is configured) --}}
        <x-ui.google-button label="Sign up with Google" divider="or register with email" :divider-after="true" />

        {{-- Form --}}
        <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ show: false }" id="register-form">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="name">Full Name</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="name" name="name" type="text" placeholder="Ananya Sharma"
                       value="{{ old('name') }}" required autocomplete="name" autofocus
                       @if($errors->first('name')) aria-invalid="true" @endif>
                @if($errors->first('name'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('name') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="email">Email Address</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="email" name="email" type="email" placeholder="name@example.com"
                       value="{{ old('email', request('email')) }}" required autocomplete="email"
                       @if($errors->first('email')) aria-invalid="true" @endif>
                @if($errors->first('email'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('email') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="password">Password</label>
                <div class="relative">
                    <input class="w-full h-12 px-4 pr-11 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                           id="password" name="password" placeholder="••••••••"
                           x-bind:type="show ? 'text' : 'password'"
                           oninput="validatePassword(this.value)"
                           required autocomplete="new-password"
                           @if($errors->first('password')) aria-invalid="true" @endif>
                    <button type="button" aria-label="Show or hide password" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" x-show="!show">visibility</span>
                        <span class="material-symbols-outlined text-[20px]" x-show="show" x-cloak>visibility_off</span>
                    </button>
                </div>
                {{-- Password checklist (Stitch design) --}}
                <div class="flex flex-col gap-1 pt-1.5 pb-1" id="password-checklist">
                    <div class="flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant" id="rule-length">
                        <span class="material-symbols-outlined text-[15px] text-outline">check_circle</span>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant" id="rule-symbol">
                        <span class="material-symbols-outlined text-[15px] text-outline">check_circle</span>
                        <span>Includes a number or symbol</span>
                    </div>
                </div>
                @if($errors->first('password'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('password') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="password_confirmation">Confirm Password</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="password_confirmation" name="password_confirmation" type="password"
                       placeholder="••••••••" required autocomplete="new-password">
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="country_code_search">Country</label>
                <x-ui.country-select name="country_code" :countries="$countries"
                                     :label="null" required
                                     :value="old('country_code')"
                                     :error="$errors->first('country_code')" />
                <p class="font-label-sm text-label-sm text-secondary flex items-start gap-1 mt-0.5">
                    <span class="material-symbols-outlined text-[14px] flex-shrink-0 mt-0.5">info</span>
                    <span>Your country sets your price and cannot be changed later.</span>
                </p>
            </div>

            <div class="flex items-start gap-2.5 pt-1">
                <input type="checkbox" name="terms" value="1" id="terms" required
                       class="w-4 h-4 mt-0.5 rounded accent-primary-container cursor-pointer shrink-0"
                       @if($errors->first('terms')) aria-invalid="true" @endif>
                <label class="font-body-sm text-body-sm text-on-surface-variant select-none cursor-pointer" for="terms">
                    I agree to the
                    <a href="{{ route('terms') }}" class="text-tertiary font-medium hover:underline">Terms of Service</a>,
                    <a href="{{ route('privacy') }}" class="text-tertiary font-medium hover:underline">Privacy Policy</a>,
                    and acknowledge that all digital ebook sales are final under our
                    <a href="{{ route('no-refund') }}" class="text-tertiary font-medium hover:underline">No-Refund Policy</a>.
                </label>
            </div>
            @if($errors->first('terms'))
            <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error" role="alert">
                <span class="material-symbols-outlined text-[16px]">error</span>
                <span>{{ $errors->first('terms') }}</span>
            </p>
            @endif

            <x-security.honeypot />

            <div class="flex items-start gap-2.5 bg-surface-container-low/60 rounded-DEFAULT p-3.5">
                <span class="material-symbols-outlined text-[18px] text-tertiary shrink-0 mt-0.5">shield</span>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    Protected by Cloudflare Turnstile. Smart bot prevention active — your details stay private.
                </p>
            </div>

            <x-turnstile />

            <div class="pt-2">
                <button type="submit"
                        class="w-full h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                    <span>Create Account</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </form>

        {{-- Bottom contextual action --}}
        <div class="mt-6 pt-5 text-center bg-surface-container-low/40 rounded-DEFAULT p-3">
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Already have an account?
                <a class="text-tertiary font-medium hover:underline ml-1" href="{{ route('login') }}">Log in</a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function validatePassword(val) {
    var ruleLen = document.getElementById('rule-length');
    var ruleSym = document.getElementById('rule-symbol');
    if (!ruleLen || !ruleSym) return;
    updateRule(ruleLen, val.length >= 8);
    updateRule(ruleSym, /[0-9!@#$%^&*(),.?":{}|<>]/.test(val));
}
function updateRule(element, isValid) {
    var icon = element.querySelector('span:first-child');
    if (isValid) {
        element.className = 'flex items-center gap-2 font-label-sm text-label-sm text-tertiary';
        icon.className = 'material-symbols-outlined text-[15px] text-tertiary';
        icon.style.fontVariationSettings = "'FILL' 1";
    } else {
        element.className = 'flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant';
        icon.className = 'material-symbols-outlined text-[15px] text-outline';
        icon.style.fontVariationSettings = "'FILL' 0";
    }
}
</script>
@endpush
@endsection
