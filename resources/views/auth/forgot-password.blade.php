@extends('layouts.app')

@section('title', 'Forgot Password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-2xl text-brand-teal text-center">Reset password</h1>
        <p class="text-sm text-gray-500 text-center mt-1">Enter your email and we'll send you a reset link.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('email') border-red-500 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full bg-brand-teal hover:bg-brand-teal-darker text-white font-semibold py-3 rounded-full transition">
                Send Reset Link
            </button>
        </form>

        <p class="text-sm text-center text-gray-600 mt-5">
            <a href="{{ route('login') }}" class="text-brand-teal font-semibold hover:underline">Back to login</a>
        </p>
    </div>
</div>
@endsection
