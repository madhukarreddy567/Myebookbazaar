@extends('layouts.admin')

@section('title', 'Change password')
@section('page_title', 'Change password')
@section('breadcrumb', 'Account / Change password')

@section('content')
<x-admin.page-header title="Change password" subtitle="Use a strong password you do not use anywhere else." />

<div class="surface section-card max-w-xl">
    <form method="POST" action="{{ route('reddy.password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="field-label">Current password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                   class="input @error('current_password') input-error @enderror" aria-describedby="current_password_error">
            @error('current_password')
                <p id="current_password_error" class="field-error">
                    <x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="input @error('password') input-error @enderror" aria-describedby="password_help password_error">
            <p id="password_help" class="field-help">Minimum 8 characters, with upper and lower case letters and a number.</p>
            @error('password')
                <p id="password_error" class="field-error">
                    <x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password" class="input">
        </div>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.dashboard') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Update password</button>
        </div>
    </form>
</div>
@endsection
