{{-- Password reset request — Stitch auth design language. --}}
@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Forgot Password — MyEbookBazaar')

@section('content')
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[420px] mx-auto bg-surface-container-lowest rounded-[16px] shadow-sm p-6 sm:p-8">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 rounded-full bg-primary-fixed/60 text-primary flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[24px]">lock_reset</span>
            </div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Reset your password</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Enter your email and we&rsquo;ll send you a secure reset link.</p>
        </div>

        @if(session('status'))
        <div class="flex items-start gap-2.5 bg-surface-container-low border border-outline-variant/40 rounded-DEFAULT p-3.5 mb-4" role="status">
            <span class="material-symbols-outlined text-[18px] text-tertiary shrink-0 mt-0.5">check_circle</span>
            <p class="font-body-sm text-body-sm text-on-surface">{{ session('status') }}</p>
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

            <x-turnstile form="forgot" />

            <div class="pt-2">
                <button type="submit"
                        class="w-full h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                    <span>Send Reset Link</span>
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 text-center bg-surface-container-low/40 rounded-DEFAULT p-3">
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Remembered it?
                <a class="text-tertiary font-medium hover:underline ml-1" href="{{ route('login') }}">Back to log in</a>
            </p>
        </div>
    </div>
</div>
@endsection
