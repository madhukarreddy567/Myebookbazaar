@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Reset password — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="st-auth-card">
        <div class="text-center">
            <img src="{{ asset('images/stitch/logo.jpg') }}" alt="MyEbookBazaar" class="w-12 h-12 rounded-2xl object-cover mx-auto mb-3 shadow-sm">
            <h1 class="st-h text-[28px]">Choose a new password</h1>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="st-label" for="email">Email</label>
                <input id="email" name="email" type="email" class="st-input"
                       value="{{ $email ?? old('email') }}" required autocomplete="email">
                @if($errors->first('email'))
                <p class="st-field-error" role="alert">{{ $errors->first('email') }}</p>
                @endif
            </div>

            <div>
                <label class="st-label" for="password">New password</label>
                <input id="password" name="password" type="password" class="st-input"
                       required autocomplete="new-password">
                <p class="st-hint mt-1.5">Min 8 characters, with upper + lower case, a number and a symbol.</p>
                @if($errors->first('password'))
                <p class="st-field-error" role="alert">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div>
                <label class="st-label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="st-input"
                       required autocomplete="new-password">
            </div>

            <button type="submit" class="st-btn st-btn-teal w-full !text-[16px]">Save new password</button>
        </form>
    </div>
</div>
@endsection
