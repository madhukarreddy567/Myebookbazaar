@extends('layouts.app')

@section('title', 'Verify email — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <x-ui.card class="p-6 md:p-8 text-center">
        <div class="empty-state-icon">
            <x-ui.icon name="mail" class="w-8 h-8" />
        </div>
        <h1 class="display-md text-teal-900 uppercase">Verify your email</h1>
        <p class="text-mutedtext text-sm mt-3">We sent a verification link to your email address. Click it to activate your account.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <x-ui.button type="submit" class="w-full">Resend verification email</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-mutedtext hover:underline underline-offset-4">Logout</button>
        </form>
    </x-ui.card>
</div>
@endsection
