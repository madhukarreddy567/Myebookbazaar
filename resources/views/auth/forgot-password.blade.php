@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Forgot password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="st-auth-card">
        <div class="text-center">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar" class="w-12 h-12 rounded-2xl object-cover mx-auto mb-3 shadow-sm">
            <h1 class="st-h text-[28px]">Reset password</h1>
            <p class="st-muted text-sm mt-1">Enter your email and we will send you a reset link.</p>
        </div>

        @if(session('status'))
            <div class="st-security-box !bg-[#E9F7EE] !border-[#BFE6CC] mt-5">
                <x-ui.icon name="check-circle" class="w-5 h-5 text-[#1E8E5A] shrink-0" />
                <p class="text-sm">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-4">
            @csrf
            <div>
                <label class="st-label" for="email">Email</label>
                <div class="st-input-wrap">
                    <x-ui.icon name="mail" class="st-input-icon" />
                    <input id="email" name="email" type="email" class="st-input has-icon"
                           placeholder="you@domain.com" value="{{ old('email') }}"
                           required autocomplete="email" autofocus>
                </div>
                @if($errors->first('email'))
                <p class="st-field-error" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('email') }}</span></p>
                @endif
            </div>

            <x-turnstile form="forgot" />

            <button type="submit" class="st-btn st-btn-teal w-full !text-[16px]">Send reset link</button>
        </form>

        <p class="text-sm text-center st-muted mt-5">
            <a href="{{ route('login') }}" class="text-[#0B3D4A] font-bold hover:underline underline-offset-4">Back to login</a>
        </p>
    </div>
</div>
@endsection
