@extends('layouts.app')

@section('title', 'Login — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-2xl text-brand-teal text-center">Welcome back</h1>

        @if (session('status'))
            <div class="bg-brand-teal-50 border border-brand-teal-200 text-brand-teal text-sm rounded-xl px-4 py-3 mt-4">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email" autofocus
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('email') border-red-500 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal-700"> Remember me
                </label>
                <a href="{{ route('password.request') }}" class="text-brand-teal hover:underline">Forgot password?</a>
            </div>

            <x-turnstile />

            <button type="submit" class="w-full bg-brand-teal hover:bg-brand-teal-darker text-white font-semibold py-3 rounded-full transition">
                Login
            </button>
        </form>

        <p class="text-sm text-center text-gray-600 mt-5">
            New here? <a href="{{ route('register') }}" class="text-brand-orange font-semibold hover:underline">Create an account</a>
        </p>
    </div>
</div>
@endsection
