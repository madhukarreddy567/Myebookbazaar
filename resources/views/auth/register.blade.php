@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Create account — MyEbookBazaar')
@section('meta_description', 'Create your free MyEbookBazaar account. Instant digital delivery of your purchased ebooks to your personal shelf.')

@section('content')
<div class="st-auth-shell">
    <div class="grid lg:grid-cols-[1.5fr_1fr] gap-6 items-start">

        {{-- LEFT: form card --}}
        <div class="st-auth-card">
            <p class="st-eyebrow">Begin your reading journey</p>
            <h1 class="st-auth-title mt-2">Create your MyEbookBazaar Account</h1>
            <p class="st-muted text-[15px] mt-3 leading-relaxed">
                Join the bazaar — instant digital delivery directly to your personal shelf.
                Free to create, yours to keep.
            </p>

            <div class="mt-6">
                <x-ui.google-button label="Sign up with Google" divider="or register with email" :divider-after="true" />
            </div>

            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5 mt-6">
                @csrf

                <div>
                    <label class="st-label" for="name">Full Legal Name</label>
                    <div class="st-input-wrap">
                        <x-ui.icon name="user" class="st-input-icon" />
                        <input id="name" name="name" type="text" class="st-input has-icon"
                               placeholder="e.g. Maya Chen" value="{{ old('name') }}"
                               required autocomplete="name" autofocus
                               @if($errors->first('name')) aria-invalid="true" @endif>
                    </div>
                    @if($errors->first('name'))
                    <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('name') }}</span></p>
                    @endif
                </div>

                <div>
                    <label class="st-label" for="email">
                        Email Address
                        <span class="st-hint"><span class="inline-flex items-center gap-1"><x-ui.icon name="zap" class="w-3.5 h-3.5" /> Instant download delivery</span></span>
                    </label>
                    <div class="st-input-wrap">
                        <x-ui.icon name="mail" class="st-input-icon" />
                        <input id="email" name="email" type="email" class="st-input has-icon"
                               placeholder="you@domain.com" value="{{ old('email', request('email')) }}"
                               required autocomplete="email"
                               @if($errors->first('email')) aria-invalid="true" @endif>
                    </div>
                    <p class="st-field-note"><x-ui.icon name="info" class="w-4 h-4 shrink-0 mt-0.5" /><span>We&rsquo;ll send your order receipts, invoices and password resets here.</span></p>
                    @if($errors->first('email'))
                    <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('email') }}</span></p>
                    @endif
                </div>

                <div class="grid sm:grid-cols-2 gap-5" x-data="mebPwdStrength()">
                    <div>
                        <label class="st-label" for="password">Password</label>
                        <div class="st-input-wrap">
                            <x-ui.icon name="lock" class="st-input-icon" />
                            <input id="password" name="password" type="password" x-bind:type="show ? 'text' : 'password'"
                                   class="st-input has-icon has-trailing" placeholder="••••••••••••"
                                   required autocomplete="new-password"
                                   @input="update($event.target.value)"
                                   @if($errors->first('password')) aria-invalid="true" @endif>
                            <button type="button" class="st-eye" @click="show = !show" aria-label="Show or hide password">
                                <x-ui.icon name="eye" x-show="!show" />
                                <x-ui.icon name="eye-off" x-show="show" x-cloak />
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="st-label" for="password_confirmation">Confirm Password</label>
                        <div class="st-input-wrap">
                            <x-ui.icon name="lock" class="st-input-icon" />
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                   class="st-input has-icon" placeholder="••••••••••••"
                                   required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="sm:col-span-2 rounded-2xl bg-[#F6F8FC] border border-[#E4E8F2] p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[13px] font-bold text-[#18262E]">Security Strength</span>
                            <span class="text-[13px] font-bold text-[#D97706]" x-text="labelText"></span>
                        </div>
                        <div class="st-strength-track">
                            <div class="st-strength-bar" :class="barClass(1)"></div>
                            <div class="st-strength-bar" :class="barClass(2)"></div>
                            <div class="st-strength-bar" :class="barClass(3)"></div>
                            <div class="st-strength-bar" :class="barClass(4)"></div>
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2.5 text-[12px] st-muted">
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="check-circle" class="w-3.5 h-3.5" /> 8+ characters</span>
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="check-circle" class="w-3.5 h-3.5" /> Mixed uppercase &amp; lowercase</span>
                            <span class="inline-flex items-center gap-1"><x-ui.icon name="info" class="w-3.5 h-3.5" /> Includes number or symbol</span>
                        </div>
                    </div>
                    @if($errors->first('password'))
                    <p class="st-field-error sm:col-span-2 !mt-0" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('password') }}</span></p>
                    @endif
                </div>

                <div>
                    <x-ui.country-select name="country_code" :countries="$countries"
                                         label="Country &amp; Tax Jurisdiction" icon="globe" required
                                         :value="old('country_code')"
                                         hint="Your country sets your pricing region and cannot be changed later."
                                         :error="$errors->first('country_code')" />
                    <div class="rounded-2xl bg-[#EEF3FF] border border-[#E0E8FA] p-4 mt-3">
                        <p class="text-sm font-extrabold text-[#0B3D4A] inline-flex items-center gap-2">
                            <x-ui.icon name="globe" class="w-4 h-4" /> Pricing Region
                        </p>
                        <p class="text-sm st-muted mt-1">Localized INR pricing for India; USD pricing for international readers.</p>
                        <p class="text-[13px] st-muted mt-2 flex gap-1.5">
                            <x-ui.icon name="lock" class="w-4 h-4 shrink-0 mt-0.5" />
                            <span>Country cannot be modified after registration (admin-only change) to keep your pricing region accurate.</span>
                        </p>
                    </div>
                </div>

                <div class="st-turnstile-box">
                    <p class="flex items-center gap-2 text-sm font-extrabold text-[#0B3D4A]">
                        <span class="w-8 h-8 rounded-full bg-[#0B3D4A] text-white inline-flex items-center justify-center shrink-0"><x-ui.icon name="check" class="w-4 h-4" /></span>
                        Protected by Cloudflare Turnstile
                        <span class="text-[11px] font-bold text-[#1E8E5A] bg-[#E3F4EA] rounded-full px-2.5 py-0.5">Verified</span>
                    </p>
                    <p class="text-[13px] st-muted mt-1.5 ml-10">Smart bot prevention • Privacy-first challenge</p>
                    <x-turnstile />
                </div>

                <div class="st-terms-box">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" name="terms" value="1" required class="st-check">
                        <span class="text-sm leading-snug flex-1">
                            I agree to the
                            <a href="{{ route('terms') }}" class="font-bold text-[#0B3D4A] underline underline-offset-2">Terms of Service</a>,
                            <a href="{{ route('privacy') }}" class="font-bold text-[#0B3D4A] underline underline-offset-2">Privacy Policy</a>,
                            and acknowledge that all digital ebook sales are final under our
                            <a href="{{ route('no-refund') }}" class="font-bold text-[#0B3D4A] underline underline-offset-2">No-Refund Policy</a>.
                        </span>
                    </label>
                </div>

                <x-security.honeypot />

                <div>
                    <button type="submit" class="st-btn st-btn-teal w-full !text-[17px]">
                        Create My Account &amp; Start Reading <x-ui.icon name="arrow-right" class="w-5 h-5" />
                    </button>
                    <p class="text-center text-[13px] st-muted mt-3 inline-flex items-center justify-center gap-1.5 w-full">
                        <x-ui.icon name="lock" class="w-4 h-4" />
                        Protected with bot checks &amp; encryption &nbsp;•&nbsp; Max 5 registration attempts/min
                    </p>
                </div>
            </form>

            <p class="text-center text-sm st-muted mt-6 pt-5 border-t border-[#E4E8F2]">
                Already part of the bazaar?
                <a href="{{ route('login') }}" class="font-extrabold text-[#0B3D4A] underline underline-offset-4 ml-1">Log In</a>
            </p>
        </div>

        {{-- RIGHT: member advantages (real benefits only) --}}
        <aside class="flex flex-col gap-5">
            <div class="st-sidepanel p-7">
                <p class="st-eyebrow">Member advantages</p>
                <h2 class="st-h text-[26px] mt-2">Why register with MyEbookBazaar?</h2>
                <div class="flex flex-col gap-5 mt-6">
                    <div class="st-adv-item">
                        <span class="st-adv-icon"><x-ui.icon name="zap" /></span>
                        <div>
                            <p class="font-extrabold text-[15px]">Instant Delivery</p>
                            <p class="text-sm st-muted mt-1 leading-relaxed">Direct access to DRM-free PDFs right after successful payment.</p>
                        </div>
                    </div>
                    <div class="st-adv-item">
                        <span class="st-adv-icon"><x-ui.icon name="download" /></span>
                        <div>
                            <p class="font-extrabold text-[15px]">Re-downloads</p>
                            <p class="text-sm st-muted mt-1 leading-relaxed">Download any purchased ebook again at no extra fee — {{ settings('downloads.limit', 5) }} downloads per book, lifetime library access.</p>
                        </div>
                    </div>
                    <div class="st-adv-item">
                        <span class="st-adv-icon"><x-ui.icon name="tag" /></span>
                        <div>
                            <p class="font-extrabold text-[15px]">Tailored Regional Pricing</p>
                            <p class="text-sm st-muted mt-1 leading-relaxed">INR pricing for India, USD for international readers — charged via secure Razorpay checkout.</p>
                        </div>
                    </div>
                    <div class="st-adv-item">
                        <span class="st-adv-icon"><x-ui.icon name="shield" /></span>
                        <div>
                            <p class="font-extrabold text-[15px]">Verified &amp; Private</p>
                            <p class="text-sm st-muted mt-1 leading-relaxed">Zero spam. We never sell your data — card details are handled securely by Razorpay and never touch our servers.</p>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('contact') }}" class="st-card p-5 flex items-center justify-between gap-3 hover:border-[#0B3D4A] transition-colors">
                <span class="inline-flex items-center gap-2.5 font-bold text-[15px]">
                    <x-ui.icon name="info" class="w-5 h-5 text-[#0B3D4A]" /> Need bulk or team licensing?
                </span>
                <span class="text-sm font-extrabold text-[#0B3D4A] inline-flex items-center gap-1 whitespace-nowrap">Contact Sales <x-ui.icon name="arrow-right" class="w-4 h-4" /></span>
            </a>
        </aside>
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
        get labelText() {
            if (this.score < 0) return 'Enter password';
            if (this.score <= 1) return 'Weak';
            if (this.score === 2) return 'Fair';
            if (this.score === 3) return 'Good';
            return 'Strong';
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
