@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Verify email — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="st-auth-card text-center">
        <div class="st-empty-icon mx-auto">
            <x-ui.icon name="mail" class="w-8 h-8" />
        </div>
        <h1 class="st-h text-[28px] mt-4">Verify your email</h1>
        <p class="st-muted text-sm mt-3">We sent a verification link to your email address. Click it to activate your account.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="st-btn st-btn-teal w-full !text-[16px]">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm st-muted hover:underline underline-offset-4">Logout</button>
        </form>
    </div>
</div>
@endsection
