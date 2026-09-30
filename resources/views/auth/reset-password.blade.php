{{-- Set a new password — Stitch auth design language. --}}
@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Choose a New Password — MyEbookBazaar')

@section('content')
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[420px] mx-auto bg-surface-container-lowest rounded-[16px] shadow-sm p-6 sm:p-8">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 rounded-full bg-primary-fixed/60 text-primary flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[24px]">key</span>
            </div>
            <h1 class="font-headline-sm text-headline-sm text-on-surface">Choose a new password</h1>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Pick something strong — your library depends on it.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4" x-data="{ show: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="email">Email Address</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="email" name="email" type="email"
                       value="{{ $email ?? old('email') }}" required autocomplete="email"
                       @if($errors->first('email')) aria-invalid="true" @endif>
                @if($errors->first('email'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('email') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="password">New Password</label>
                <div class="relative">
                    <input class="w-full h-12 px-4 pr-11 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                           id="password" name="password" placeholder="••••••••"
                           x-bind:type="show ? 'text' : 'password'"
                           required autocomplete="new-password"
                           @if($errors->first('password')) aria-invalid="true" @endif>
                    <button type="button" aria-label="Show or hide password" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors p-1">
                        <span class="material-symbols-outlined text-[20px]" x-show="!show">visibility</span>
                        <span class="material-symbols-outlined text-[20px]" x-show="show" x-cloak>visibility_off</span>
                    </button>
                </div>
                <p class="font-label-sm text-label-sm text-secondary flex items-start gap-1 mt-0.5">
                    <span class="material-symbols-outlined text-[14px] flex-shrink-0 mt-0.5">info</span>
                    <span>Min 8 characters, with upper + lower case, a number and a symbol.</span>
                </p>
                @if($errors->first('password'))
                <p class="flex items-center gap-1.5 font-label-sm text-label-sm text-error mt-1" role="alert">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>{{ $errors->first('password') }}</span>
                </p>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="font-label-md text-label-md text-on-surface font-medium" for="password_confirmation">Confirm New Password</label>
                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low text-on-surface placeholder:text-on-surface-variant/50 font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary-container transition-all"
                       id="password_confirmation" name="password_confirmation" type="password"
                       placeholder="••••••••" required autocomplete="new-password">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full h-12 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm flex items-center justify-center gap-2">
                    <span>Save New Password</span>
                    <span class="material-symbols-outlined text-[18px]">check</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
