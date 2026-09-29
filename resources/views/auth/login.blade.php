{{-- Stitch login screen: logo badge, Google button, email login form. --}}
@extends('layouts.app')

@section('title', 'Login — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 pt-6 pb-10">
    <div class="flex flex-col items-center text-center">
        <div class="auth-logo-badge">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar">
            <span class="auth-pill">Digital Press &amp; Bookstore</span>
        </div>
        <h1 class="auth-title mt-6">Welcome back</h1>
        <p class="text-sm text-mutedtext max-w-[290px] mt-1 leading-snug">
            Log in to access your digital library, downloaded ebooks, and order receipts.
        </p>
    </div>

    <div class="mt-6 flex flex-col gap-4">
        <x-ui.google-button divider="or log in with email" :divider-after="true" />

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-3.5">
            @csrf

            <div>
                <label class="auth-label" for="email">Email Address</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="mail" class="auth-input-icon" />
                    <input id="email" name="email" type="email" class="auth-input"
                           placeholder="your.email@domain.com" value="{{ old('email') }}"
                           required autocomplete="email" autofocus
                           @if($errors->first('email')) aria-invalid="true" @endif>
                </div>
                @if($errors->first('email'))
                <p class="field-error" role="alert">
                    <x-ui.icon name="alert" class="w-4 h-4" />
                    <span>{{ $errors->first('email') }}</span>
                </p>
                @endif
            </div>

            <div x-data="{ show: false }">
                <label class="auth-label" for="password">Password</label>
                <div class="auth-input-wrap">
                    <x-ui.icon name="lock" class="auth-input-icon" />
                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                           class="auth-input has-trailing" placeholder="••••••••••••"
                           required autocomplete="current-password"
                           @if($errors->first('password')) aria-invalid="true" @endif>
                    <button type="button" class="auth-eye" @click="show = !show" aria-label="Show or hide password">
                        <x-ui.icon name="eye-off" x-show="!show" />
                        <x-ui.icon name="eye" x-show="show" x-cloak />
                    </button>
                </div>
                @if($errors->first('password'))
                <p class="field-error" role="alert">
                    <x-ui.icon name="alert" class="w-4 h-4" />
                    <span>{{ $errors->first('password') }}</span>
                </p>
                @endif
            </div>

            <div class="flex items-center justify-between pt-0.5">
                <label class="flex items-center gap-2 cursor-pointer select-none text-sm">
                    <input type="checkbox" name="remember" value="1" class="auth-check">
                    <span>Remember this device</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-bold text-saffron-700 hover:underline underline-offset-4">Forgot password?</a>
            </div>

            <x-turnstile form="login" />

            <button type="submit" class="auth-submit auth-submit-teal mt-1">
                <span>Log in to my library</span>
                <x-ui.icon name="arrow-right" class="w-5 h-5" />
            </button>
        </form>
    </div>

    <div class="auth-card mt-6 text-center">
        <p class="text-sm text-mutedtext">
            New to MyEbookBazaar?
            <a href="{{ route('register') }}" class="font-bold text-saffron-700 hover:underline underline-offset-4 ml-1">Create an account</a>
        </p>
    </div>

    <p class="auth-note mt-5">
        <x-ui.icon name="check-circle" />
        <span>All past purchases remain permanently synced with your registered email.</span>
    </p>
</div>
@endsection
