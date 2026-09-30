@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Login — MyEbookBazaar')
@section('meta_description', 'Log in to MyEbookBazaar to access your digital library, download links, orders and invoices.')

@section('content')
<div class="st-auth-shell">
    <div class="flex items-center justify-between gap-4 mb-6">
        <p class="st-crumb inline-flex items-center gap-2">
            <x-ui.icon name="lock" class="w-4 h-4" /> Customer Authentication <span class="sep">/</span> <strong class="text-[#101E28]">Account Login</strong>
        </p>
        <p class="text-sm st-muted hidden sm:block">
            New to the bazaar?
            <a href="{{ route('register') }}" class="font-extrabold text-[#D97706] underline underline-offset-4 ml-1">Create an Account</a>
        </p>
    </div>

    <div class="grid lg:grid-cols-[1.5fr_1fr] gap-6 items-start">
        <div class="flex flex-col gap-5">
            {{-- LOGIN CARD --}}
            <div class="st-auth-card st-login-card">
                <p class="st-eyebrow"><x-ui.icon name="book" class="w-4 h-4" /> Secure reader access</p>
                <h1 class="st-auth-title mt-2">Welcome Back, Reader</h1>
                <p class="st-muted text-[15px] mt-3 leading-relaxed">
                    Access your purchased ebooks, instant download tokens, and continuous reading library.
                </p>

                <div class="mt-6">
                    <x-ui.google-button label="Continue with Google" divider="or log in with email" :divider-after="true" />
                </div>

                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5 mt-6">
                    @csrf

                    <div>
                        <label class="st-label" for="email">Email Address <span class="st-hint">Registered email</span></label>
                        <div class="st-input-wrap">
                            <x-ui.icon name="mail" class="st-input-icon" />
                            <input id="email" name="email" type="email" class="st-input has-icon"
                                   placeholder="you@domain.com" value="{{ old('email') }}"
                                   required autocomplete="email" autofocus
                                   @if($errors->first('email')) aria-invalid="true" @endif>
                        </div>
                        @if($errors->first('email'))
                        <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('email') }}</span></p>
                        @endif
                    </div>

                    <div x-data="{ show: false }">
                        <label class="st-label" for="password">Password <span class="st-hint">Case-sensitive</span></label>
                        <div class="st-input-wrap">
                            <x-ui.icon name="lock" class="st-input-icon" />
                            <input id="password" name="password" type="password" x-bind:type="show ? 'text' : 'password'"
                                   class="st-input has-icon has-trailing" placeholder="••••••••••••"
                                   required autocomplete="current-password"
                                   @if($errors->first('password')) aria-invalid="true" @endif>
                            <button type="button" class="st-eye" @click="show = !show" aria-label="Show or hide password">
                                <x-ui.icon name="eye" x-show="!show" />
                                <x-ui.icon name="eye-off" x-show="show" x-cloak />
                            </button>
                        </div>
                        @if($errors->first('password'))
                        <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('password') }}</span></p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none text-sm font-medium">
                            <input type="checkbox" name="remember" value="1" class="st-check !mt-0">
                            <span>Remember this device</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-extrabold text-[#0B3D4A] hover:underline underline-offset-4">Forgot Password?</a>
                    </div>

                    <div class="st-security-box">
                        <x-ui.icon name="shield" class="w-6 h-6 text-[#0B3D4A] shrink-0" />
                        <p>
                            <strong class="text-[#0B3D4A]">Security Protected:</strong>
                            Max 5 login attempts per minute. The account locks automatically
                            on consecutive failed tries to safeguard your digital licenses.
                        </p>
                    </div>

                    <div class="st-turnstile-box">
                        <p class="flex items-center gap-2 text-sm font-bold">
                            <span class="w-2 h-2 rounded-full bg-[#F5A623]" aria-hidden="true"></span>
                            Protected by Cloudflare Turnstile
                            <span class="st-muted font-medium">• Smart bot prevention active</span>
                            <span class="ml-auto inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-[#0B3D4A]">
                                <x-ui.icon name="lock" class="w-3.5 h-3.5" /> Encrypted
                            </span>
                        </p>
                        <x-turnstile form="login" />
                    </div>

                    <button type="submit" class="st-btn st-btn-teal w-full !text-[17px]">
                        Sign In to My Account <x-ui.icon name="arrow-right" class="w-5 h-5" />
                    </button>
                </form>

                <p class="text-center text-sm st-muted mt-5">
                    Having trouble signing in? Reset your password below or write to
                    <a href="mailto:{{ settings('site.support_email', 'support@myebookbazaar.com') }}" class="font-bold text-[#0B3D4A] hover:underline">{{ settings('site.support_email', 'support@myebookbazaar.com') }}</a>
                </p>
            </div>

            {{-- RESET PASSWORD PANEL (real password.email endpoint) --}}
            <div class="st-card p-6 md:p-7" x-data="{ open: false }">
                <button type="button" @click="open = !open" :aria-expanded="open" class="w-full flex items-center justify-between gap-3 text-left">
                    <span>
                        <span class="st-h text-[20px] block">Need to reset your password?</span>
                        <span class="text-sm st-muted">Recover reading credentials in under 60 seconds</span>
                    </span>
                    <x-ui.icon name="chevron-down" class="w-5 h-5 st-muted shrink-0 transition-transform" ::class="{ 'rotate-180': open }" />
                </button>
                <div x-show="open" x-cloak class="mt-5">
                    @if(session('status'))
                        <div class="st-security-box !bg-[#E9F7EE] !border-[#BFE6CC] mb-4">
                            <x-ui.icon name="check-circle" class="w-5 h-5 text-[#1E8E5A] shrink-0" />
                            <p class="text-sm">{{ session('status') }}</p>
                        </div>
                    @endif
                    <p class="text-sm st-muted leading-relaxed">
                        Enter your registered email address. We&rsquo;ll dispatch a secure,
                        time-limited reset link valid for <strong class="text-[#101E28]">60 minutes</strong>.
                        If an account matches, the recovery token arrives instantly.
                    </p>
                    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col sm:flex-row gap-3 mt-4">
                        @csrf
                        <label for="reset-email" class="sr-only">Registered email address</label>
                        <div class="st-input-wrap flex-1">
                            <x-ui.icon name="mail" class="st-input-icon" />
                            <input id="reset-email" name="email" type="email" required
                                   class="st-input has-icon" placeholder="your.registered@email.com"
                                   value="{{ old('email') }}" autocomplete="email">
                        </div>
                        <button type="submit" class="st-btn st-btn-amber !min-h-[54px] whitespace-nowrap">
                            <x-ui.icon name="mail" class="w-4 h-4" /> Send Reset Link
                        </button>
                    </form>
                    @error('email')
                    <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $message }}</span></p>
                    @enderror
                    <div class="flex flex-wrap gap-x-6 gap-y-1.5 mt-3 text-[12px] st-muted">
                        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="clock" class="w-4 h-4" /> Rate limit: 3 requests / min</span>
                        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="mail" class="w-4 h-4" /> Auto-expires in 60m</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT SIDEBAR --}}
        <aside class="flex flex-col gap-5">
            <div class="st-sidepanel p-6">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#0B3D4A]">Your digital bookshelf awaits</p>
                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-[#D97706] bg-white rounded-full px-2.5 py-1 border border-[#E0E8FA]">
                        <x-ui.icon name="zap" class="w-3.5 h-3.5" /> Instant Access
                    </span>
                </div>
                <div class="flex flex-col gap-3.5">
                    <div class="bg-white rounded-2xl border border-[#E0E8FA] p-5">
                        <p class="font-extrabold text-[16px]">Instant PDF Re-downloads</p>
                        <p class="text-sm st-muted mt-1 leading-relaxed">Download your purchased editions anytime in DRM-free PDF format. Read on phone, tablet or computer.</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-[#E0E8FA] p-5">
                        <p class="font-extrabold text-[16px]">Purchase History</p>
                        <p class="text-sm st-muted mt-1 leading-relaxed">Keep receipts, order invoices, and Razorpay transaction IDs organized for lifetime record-keeping.</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-[#E0E8FA] p-5">
                        <p class="font-extrabold text-[16px]">Lifetime Library Access</p>
                        <p class="text-sm st-muted mt-1 leading-relaxed">Every purchased ebook stays in your personal library forever, with signed download links generated on demand.</p>
                    </div>
                </div>
            </div>

            @if($spotlight ?? null)
            <a href="{{ route('books.show', $spotlight->slug) }}" class="st-card p-5 flex gap-4 items-center hover:border-[#0B3D4A] transition-colors">
                @php($spotCover = $spotlight->displayCoverUrl())
                @if($spotCover)
                <img src="{{ $spotCover }}" alt="" class="w-16 rounded-lg shadow-md shrink-0" loading="lazy">
                @endif
                <div class="min-w-0">
                    <p class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#FFF0D6] text-[#B45309] rounded-md px-2 py-0.5">Featured</span>
                        @if($spotlight->edition)<span class="text-[11px] font-bold st-muted uppercase tracking-wider">Edition {{ $spotlight->edition }}</span>@endif
                    </p>
                    <p class="font-extrabold text-[16px] mt-1 line-clamp-1">{{ $spotlight->title }}</p>
                    <p class="text-sm st-muted line-clamp-1">{{ $spotlight->short_description }}</p>
                    <p class="flex items-baseline gap-2 mt-1">
                        <span class="text-xl font-extrabold text-[#D97706]">{{ $spotlight->price_display }}</span>
                        @if($spotlight->price_regular_display)
                        <span class="book-card-oldprice text-sm">{{ $spotlight->price_regular_display }}</span>
                        @endif
                        <span class="ml-auto text-sm font-extrabold text-[#0B3D4A] inline-flex items-center gap-1">Preview <x-ui.icon name="arrow-right" class="w-4 h-4" /></span>
                    </p>
                </div>
            </a>
            @endif

            <div class="st-sidepanel p-6">
                <p class="font-extrabold text-[15px] inline-flex items-center gap-2"><x-ui.icon name="lock" class="w-5 h-5 text-[#0B3D4A]" /> Protected Authentication Perimeter</p>
                <p class="text-sm st-muted mt-2 leading-relaxed">
                    Encrypted connection • HttpOnly &amp; SameSite=Lax session cookies.
                    Passwords are one-way hashed and never stored in plain text.
                    We never share your reading habits.
                </p>
            </div>
        </aside>
    </div>

    <div class="st-trust-row mt-10">
        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="download" class="w-4 h-4" /> Instant PDF</span>
        <span class="text-[#C3CDD8]" aria-hidden="true">•</span>
        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="zap" class="w-4 h-4" /> Zero DRM friction</span>
        <span class="text-[#C3CDD8]" aria-hidden="true">•</span>
        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="shield" class="w-4 h-4" /> Razorpay checkout</span>
        <span class="text-[#C3CDD8]" aria-hidden="true">•</span>
        <span class="inline-flex items-center gap-1.5"><x-ui.icon name="book" class="w-4 h-4" /> 24/7 library access</span>
    </div>
</div>
@endsection
