@extends('layouts.app')

@section('title', 'My Account — MyEbookBazaar')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12 space-y-6">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-2xl text-brand-teal">My Account</h1>

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between border-b border-gray-100 pb-3">
                <dt class="text-gray-500">Name</dt>
                <dd class="font-medium text-gray-800">{{ $user->name }}</dd>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-3">
                <dt class="text-gray-500">Email</dt>
                <dd class="font-medium text-gray-800">{{ $user->email }}</dd>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-3">
                <dt class="text-gray-500">Country</dt>
                <dd class="font-medium text-gray-800">{{ $user->country_name }} ({{ $user->country_code }})</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Pricing region</dt>
                <dd class="font-medium text-gray-800">{{ $user->pricing_region === 'IN' ? 'India' : 'International' }}</dd>
            </div>
        </dl>
        <p class="text-xs text-gray-400 mt-4">Country cannot be changed after signup. Contact support if you need it updated.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h2 class="font-heading font-semibold text-lg text-brand-teal">Change password</h2>

        <form method="POST" action="{{ route('account.password') }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('current_password') border-red-500 @enderror">
                @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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

            <button type="submit" class="bg-brand-teal hover:bg-brand-teal-darker text-white font-semibold px-6 py-2.5 rounded-full transition">
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection
