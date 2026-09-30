{{-- Email verification notice — Stitch auth design language. --}}
@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Verify Your Email — MyEbookBazaar')

@section('content')
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[420px] mx-auto bg-surface-container-lowest rounded-[16px] shadow-sm p-6 sm:p-8 text-center">
        <div class="w-12 h-12 rounded-full bg-primary-fixed/60 text-primary flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-[24px]">mark_email_unread</span>
        </div>
        <h1 class="font-headline-sm text-headline-sm text-on-surface">Verify your email</h1>
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 leading-relaxed">
            We sent a verification link to your email address. Click it to activate your account and unlock your library.
        </p>

        @if(session('status'))
        <div class="flex items-start gap-2.5 bg-surface-container-low border border-outline-variant/40 rounded-DEFAULT p-3.5 mt-4 text-left" role="status">
            <span class="material-symbols-outlined text-[18px] text-tertiary shrink-0 mt-0.5">check_circle</span>
            <p class="font-body-sm text-body-sm text-on-surface">{{ session('status') }}</p>
        </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit"
                    class="w-full h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                <span>Resend Verification Email</span>
                <span class="material-symbols-outlined text-[18px]">send</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="font-body-sm text-body-sm text-on-surface-variant hover:text-on-surface hover:underline underline-offset-4 transition-colors">
                Log out
            </button>
        </form>
    </div>
</div>
@endsection
