{{-- Customer login — Stitch design: tabbed auth card, tabs link to login/register routes. --}}
@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Log In — MyEbookBazaar')
@section('meta_description', 'Log in to MyEbookBazaar to access your digital library, download links, orders and invoices.')

@section('content')
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[420px] mx-auto bg-surface-container-lowest rounded-[16px] shadow-sm p-6 sm:p-8">
        {{-- Top pill segmented switch: tabs link to routes, login active here --}}
        <div class="bg-surface-container p-1 rounded-full flex items-center justify-between mb-8">
            <span class="flex-1 py-2 text-center rounded-full font-label-md text-label-md font-semibold bg-surface-container-lowest text-on-surface shadow-sm" aria-current="page">
                Log In
            </span>
            <a href="{{ route('register') }}" class="flex-1 py-2 text-center rounded-full font-label-md text-label-md font-medium text-on-surface-variant hover:text-on-surface transition-all duration-200">
                Create Account
            </a>
        </div>

        {{-- Header context --}}
        <div class="mb-6 text-center">
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Welcome Back, Reader</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Access your reading library and purchases</p>
        </div>

        {{-- Social auth CTA (renders only when Google OAuth is configured) --}}
        <x-ui.google-button label="Continue with Google" divider="or continue with email" :divider-after="true" />

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ show: false }">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="email">Email Address</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="email" name="email" type="email" placeholder="name@example.com"
                       value="{{ old('email') }}" required autocomplete="email" autofocus
                       @if($errors->first('email')) aria-invalid="true" @endif>
                @if($errors->first('email'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('email') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label class="font-label-md text-label-md text-on-surface font-medium" for="password">Password</label>
                    <a class="font-body-sm text-body-sm text-tertiary hover:underline" href="{{ route('password.request') }}">Forgot Password?</a>
                </div>
                <div class="relative">
                    <input class="w-full h-12 px-4 pr-11 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                           id="password" name="password" placeholder="••••••••"
                           x-bind:type="show ? 'text' : 'password'"
                           required autocomplete="current-password"
                           @if($errors->first('password')) aria-invalid="true" @endif>
                    <button type="button" aria-label="Show or hide password" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" x-show="!show">visibility</span>
                        <span class="material-symbols-outlined text-[20px]" x-show="show" x-cloak>visibility_off</span>
                    </button>
                </div>
                @if($errors->first('password'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('password') }}</span>
                </p>
                @endif
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2.5 cursor-pointer select-none font-body-sm text-body-sm text-on-surface-variant">
                    <input type="checkbox" name="remember" value="1"
                           class="w-4 h-4 rounded accent-primary-container cursor-pointer">
                    Remember this device
                </label>
            </div>

            <div class="flex items-start gap-2.5 bg-surface-container-low/60 rounded-DEFAULT p-3.5">
                <span class="material-symbols-outlined text-[18px] text-tertiary shrink-0 mt-0.5">shield</span>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    Protected by Cloudflare Turnstile. Smart bot prevention active — your session is encrypted and never shared.
                </p>
            </div>

            <x-turnstile form="login" />

            <div class="pt-2">
                <button type="submit"
                        class="w-full h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                    <span>Log In</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </form>

        {{-- Bottom contextual action --}}
        <div class="mt-6 pt-5 text-center bg-surface-container-low/40 rounded-DEFAULT p-3">
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Don&rsquo;t have an account?
                <a class="text-tertiary font-medium hover:underline ml-1" href="{{ route('register') }}">Create one</a>
            </p>
        </div>

        {{-- Inline reset-password panel (posts to the real password.email endpoint) --}}
        <div class="mt-6 bg-surface-container-lowest rounded-DEFAULT shadow-sm p-5 sm:p-6" x-data="{ open: false }">
            <button type="button" @click="open = !open" :aria-expanded="open"
                    class="w-full flex items-center justify-between gap-3 text-left">
                <span>
                    <span class="font-label-md text-label-md font-semibold text-on-surface block">Need to reset your password?</span>
                    <span class="font-body-sm text-body-sm text-on-surface-variant">Get a secure reset link by email</span>
                </span>
                <span class="w-8 h-8 rounded-full bg-surface-container text-on-surface-variant flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[20px] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </span>
            </button>
            <div x-show="open" x-cloak class="mt-4">
                @if(session('status'))
                <p class="font-body-sm text-body-sm text-on-surface bg-surface-container-low rounded-DEFAULT px-4 py-3 mb-4" role="status">{{ session('status') }}</p>
                @endif
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    Enter your registered email address and we&rsquo;ll send you a secure, time-limited reset link.
                </p>
                <form method="POST" action="{{ route('password.email') }}" class="flex flex-col sm:flex-row gap-3 mt-4">
                    @csrf
                    <label for="reset-email" class="sr-only">Registered email address</label>
                    <input id="reset-email" name="email" type="email" required
                           class="flex-1 h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                           placeholder="your.registered@email.com"
                           value="{{ old('email') }}" autocomplete="email">
                    <button type="submit"
                            class="h-12 px-5 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm whitespace-nowrap">
                        Send Reset Link
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if($spotlight ?? null)
    <a href="{{ route('books.show', $spotlight->slug) }}"
       class="block w-full max-w-[420px] mx-auto mt-6 bg-surface-container-lowest rounded-DEFAULT p-4 shadow-sm hover:shadow transition-shadow">
        <div class="flex items-center gap-4">
            @php($spotCover = $spotlight->displayCoverUrl())
            @if($spotCover)
            <img src="{{ $spotCover }}" alt="" class="w-12 rounded-md shadow-sm shrink-0" loading="lazy">
            @endif
            <div class="min-w-0 flex-1">
                <p class="font-label-sm text-label-sm text-secondary font-semibold uppercase tracking-wider">Featured this week</p>
                <p class="font-headline-sm text-headline-sm text-on-surface truncate">{{ $spotlight->title }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    <span class="font-semibold text-primary">{{ $spotlight->price_display }}</span>
                    @if($spotlight->price_regular_display)
                    <span class="line-through text-on-surface-variant/60 ml-1.5">{{ $spotlight->price_regular_display }}</span>
                    @endif
                </p>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant shrink-0">arrow_forward</span>
        </div>
    </a>
    @endif
</div>
@endsection
