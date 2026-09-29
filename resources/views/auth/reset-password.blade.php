@extends('layouts.app')

@section('title', 'Reset Password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-2xl text-brand-teal text-center">Choose a new password</h1>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ $email ?? old('email') }}"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('email') border-red-500 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('password') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Min 8 characters, with upper + lower case and a number.</p>
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700">
            </div>

            <button type="submit" class="w-full bg-brand-teal hover:bg-brand-teal-darker text-white font-semibold py-3 rounded-full transition">
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
