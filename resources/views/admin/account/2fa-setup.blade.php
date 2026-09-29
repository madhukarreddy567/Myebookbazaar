@extends('layouts.admin')

@section('title', 'Set up two-factor authentication')
@section('page_title', 'Set up two-factor authentication')
@section('breadcrumb', 'Account / Security / Set up 2FA')

@section('content')
<x-admin.page-header title="Set up two-factor authentication"
                     subtitle="Three steps, about a minute. You need an authenticator app (Google Authenticator, 1Password, Authy…)." />

@if(session('status'))
    <div class="rounded-xl border border-[rgb(183_121_31/0.35)] bg-[var(--color-saffron-100)] text-sm px-4 py-3 mb-6 max-w-2xl" role="status">
        {{ session('status') }}
    </div>
@endif

<div class="grid gap-6 max-w-2xl">
    {{-- Step 1: scan --}}
    <div class="surface section-card">
        <h2 class="font-bold"><span class="text-[var(--color-saffron-700)]">Step 1.</span> Scan this QR code</h2>
        <p class="text-sm text-[var(--color-muted)] mt-1 mb-4">Open your authenticator app and scan:</p>
        <div class="bg-white rounded-xl p-4 inline-block border border-[var(--color-border)]">
            {!! $qrSvg !!}
        </div>
        <p class="text-sm text-[var(--color-muted)] mt-4">
            Can't scan? Enter this secret manually:
        </p>
        <code class="block mt-1 px-3 py-2 rounded-lg bg-[var(--color-bg-soft)] font-mono text-sm tracking-widest break-all select-all">{{ $secret }}</code>
    </div>

    {{-- Step 2: confirm --}}
    <div class="surface section-card">
        <h2 class="font-bold"><span class="text-[var(--color-saffron-700)]">Step 2.</span> Enter the 6-digit code</h2>
        <p class="text-sm text-[var(--color-muted)] mt-1 mb-4">Type the current code shown in your app to confirm:</p>

        @if ($errors->any())
            <div class="flex items-start gap-2.5 rounded-xl border border-[rgb(201_60_60/0.3)] bg-[rgb(201_60_60/0.08)] text-[var(--color-danger)] text-sm px-4 py-3 mb-4" role="alert">
                <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('reddy.2fa.confirm') }}" class="flex gap-2 max-w-sm">
            @csrf
            <input name="code" type="text" required inputmode="numeric" autocomplete="one-time-code"
                   placeholder="123456" maxlength="6"
                   class="input text-center tracking-[0.3em] text-lg">
            <button type="submit" class="btn btn-primary shrink-0">Confirm</button>
        </form>
    </div>

    {{-- Step 3 preview --}}
    <div class="surface section-card">
        <h2 class="font-bold"><span class="text-[var(--color-saffron-700)]">Step 3.</span> Save your recovery codes</h2>
        <p class="text-sm text-[var(--color-muted)] mt-1">
            After confirming, you'll get 10 one-time recovery codes. Store them somewhere safe —
            they're your way back in if you lose your phone.
        </p>
    </div>
</div>
@endsection
