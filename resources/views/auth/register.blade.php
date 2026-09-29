{{-- Stitch register screen: logo badge, Google button, email registration form. --}}
@extends('layouts.app')

@section('title', 'Create account — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 pt-6 pb-10">
    <div class="flex flex-col items-center text-center">
        <div class="auth-logo-badge" style="width: 64px; height: 64px; border-radius: 18px; padding: 8px;">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar">
        </div>
        <h1 class="auth-title mt-4">Create your account</h1>
        <p class="text-sm text-mutedtext max-w-[290px] mt-1 leading-snug">
            Instant digital shelf access for all your fitness, nutrition &amp; lifestyle guides.
        </p>
    </div>

    <div class="mt-6 flex flex-col gap-4">
        <x-ui.google-button label="Sign up with Google" divider="or register with email" :divider-after="true" />

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-3.5">
            @csrf

            <div>
                <label class="auth-label" for="name">Full Name</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="user" class="auth-input-icon" />
                    <input id="name" name="name" type="text" class="auth-input"
                           placeholder="e.g. Ananya Patel" value="{{ old('name') }}"
                           required autocomplete="name" autofocus
                           @if($errors->first('name')) aria-invalid="true" @endif>
                </div>
                @if($errors->first('name'))
                <p class="field-error" role="alert">
                    <x-ui.icon name="alert" class="w-4 h-4" />
                    <span>{{ $errors->first('name') }}</span>
                </p>
                @endif
            </div>

            <div>
                <label class="auth-label" for="country_code_search">Email Address</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="mail" class="auth-input-icon" />
                    <input id="email" name="email" type="email" class="auth-input"
                           placeholder="e.g. ananya@email.com" value="{{ old('email') }}"
                           required autocomplete="email"
                           @if($errors->first('email')) aria-invalid="true" @endif>
                </div>
                <p class="flex items-center gap-1.5 text-[13px] text-mutedtext mt-1.5 px-1">
                    <x-ui.icon name="mail" class="w-4 h-4 text-teal-700" />
                    Your ebook download links will be sent here
                </p>
                @if($errors->first('email'))
                <p class="field-error" role="alert">
                    <x-ui.icon name="alert" class="w-4 h-4" />
                    <span>{{ $errors->first('email') }}</span>
                </p>
                @endif
            </div>

            <div class="auth-country">
                <x-ui.country-select name="country_code" :countries="$countries"
                                     label="Country / Region" icon="globe" required
                                     :value="old('country_code')"
                                     hint="Your country sets your price and cannot be changed later."
                                     :error="$errors->first('country_code')" />
            </div>

            <div x-data="mebPwdStrength()">
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="lock" class="auth-input-icon" />
                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                           class="auth-input has-trailing" placeholder="At least 8 characters"
                           required autocomplete="new-password"
                           @input="update($event.target.value)"
                           @if($errors->first('password')) aria-invalid="true" @endif>
                    <button type="button" class="auth-eye" @click="show = !show" aria-label="Show or hide password">
                        <x-ui.icon name="eye" x-show="!show" />
                        <x-ui.icon name="eye-off" x-show="show" x-cloak />
                    </button>
                </div>
                <div class="mt-2 px-1">
                    <div class="strength-track">
                        <div class="strength-bar" :class="barClass(1)"></div>
                        <div class="strength-bar" :class="barClass(2)"></div>
                        <div class="strength-bar" :class="barClass(3)"></div>
                        <div class="strength-bar" :class="barClass(4)"></div>
                    </div>
                    <div class="flex items-center justify-between mt-1.5">
                        <span class="text-xs font-bold text-mutedtext" x-text="label"></span>
                        <span class="text-xs text-mutedtext">8+ chars required</span>
                    </div>
                </div>
                @if($errors->first('password'))
                <p class="field-error" role="alert">
                    <x-ui.icon name="alert" class="w-4 h-4" />
                    <span>{{ $errors->first('password') }}</span>
                </p>
                @endif
            </div>

            <div>
                <label class="auth-label" for="password_confirmation">Confirm Password</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="lock" class="auth-input-icon" />
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           class="auth-input" placeholder="Repeat your password"
                           required autocomplete="new-password">
                </div>
            </div>

            <div class="terms-box">
                <label class="flex items-start gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="terms" value="1" required class="terms-check">
                    <span class="text-sm leading-snug flex-1">
                        I agree to the
                        <a href="{{ route('terms') }}" class="font-bold text-teal-700 underline underline-offset-2">Terms of Service</a>
                        &amp;
                        <a href="{{ route('privacy') }}" class="font-bold text-teal-700 underline underline-offset-2">Privacy Policy</a>,
                        and understand digital products have immediate download access.
                    </span>
                </label>
            </div>

            <x-security.honeypot />

            <x-turnstile />

            <button type="submit" class="auth-submit auth-submit-saffron mt-1">
                <span>Create account &amp; access books</span>
                <x-ui.icon name="arrow-right" class="w-5 h-5" />
            </button>
        </form>

        <p class="text-center text-sm text-mutedtext pt-1">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-teal-700 underline underline-offset-4 ml-1">Log in</a>
        </p>

        <div class="auth-card flex items-center justify-center gap-2 text-center">
            <x-ui.icon name="shield" class="w-5 h-5 text-teal-700 shrink-0" />
            <p class="text-xs font-bold text-mutedtext">256-bit encrypted data &bull; Instant lifetime access to all purchased files</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mebPwdStrength() {
    return {
        show: false,
        score: -1,
        update(v) {
            if (!v) { this.score = -1; return; }
            let s = 0;
            if (v.length >= 8) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            this.score = s;
        },
        get label() {
            if (this.score < 0) return 'Strength: Enter password';
            if (this.score <= 1) return 'Strength: Weak';
            if (this.score === 2) return 'Strength: Fair';
            if (this.score === 3) return 'Strength: Good';
            return 'Strength: Strong';
        },
        barClass(i) {
            if (this.score < 0 || i > Math.max(this.score, 1)) return '';
            if (this.score <= 1) return 'on-weak';
            if (this.score === 2) return 'on-fair';
            if (this.score === 3) return 'on-good';
            return 'on-strong';
        }
    };
}
</script>
@endpush
