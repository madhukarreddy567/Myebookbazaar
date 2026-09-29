@extends('layouts.app')

@section('title', 'Verify Email — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100 text-center">
        <h1 class="font-heading font-bold text-2xl text-brand-teal">Verify your email</h1>
        <p class="text-sm text-gray-600 mt-3">We've sent a verification link to your email address. Click it to activate your account.</p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold py-3 rounded-full transition">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:underline">Logout</button>
        </form>
    </div>
</div>
@endsection
