@extends('layouts.admin')

@section('title', 'Change Password')
@section('page_title', 'Change Password')

@section('content')
<div class="max-w-lg bg-white rounded-2xl shadow p-8 border border-gray-100">
    <form method="POST" action="{{ route('reddy.password.update') }}" class="space-y-4">
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
@endsection
