@extends('layouts.app')

@section('title', 'Forgot password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <x-ui.card class="p-6 md:p-8">
        <div class="text-center">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar" class="w-12 h-12 rounded-2xl object-cover mx-auto mb-3 shadow-sm">
            <h1 class="display-md text-teal-900 uppercase">Reset password</h1>
            <p class="text-mutedtext text-sm mt-1">Enter your email and we will send you a reset link.</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="mt-6">
            @csrf
            <x-ui.input label="Email" name="email" type="email" :value="old('email')"
                        required :error="$errors->first('email')" />

            <x-turnstile form="forgot" />

            <x-ui.button type="submit" class="w-full mt-4">Send reset link</x-ui.button>
        </form>

        <p class="text-sm text-center text-mutedtext mt-5">
            <a href="{{ route('login') }}" class="text-teal-700 font-bold hover:underline underline-offset-4">Back to login</a>
        </p>
    </x-ui.card>
</div>
@endsection
