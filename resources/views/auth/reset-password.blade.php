@extends('layouts.app')

@section('title', 'Reset password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <x-ui.card class="p-6 md:p-8">
        <div class="text-center">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar" class="w-12 h-12 rounded-2xl object-cover mx-auto mb-3 shadow-sm">
            <h1 class="display-md text-teal-900 uppercase">Choose a new password</h1>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <x-ui.input label="Email" name="email" type="email" :value="$email ?? old('email')"
                        required :error="$errors->first('email')" />

            <x-ui.input label="New password" name="password" type="password"
                        required autocomplete="new-password"
                        hint="Min 8 characters, with upper + lower case and a number."
                        :error="$errors->first('password')" />

            <x-ui.input label="Confirm new password" name="password_confirmation" type="password"
                        required autocomplete="new-password" />

            <x-ui.button type="submit" class="w-full">Save new password</x-ui.button>
        </form>
    </x-ui.card>
</div>
@endsection
