@extends('layouts.admin')

@section('title', 'Two-factor authentication enabled')
@section('page_title', 'Two-factor authentication enabled')
@section('breadcrumb', 'Account / Security / Recovery codes')

@section('content')
<x-admin.page-header title="Two-factor authentication is on"
                     subtitle="Step 3 of 3 — save these recovery codes now." />

<div class="max-w-2xl">
    <div class="surface section-card border-2" style="border-color: var(--color-saffron-500)">
        <div class="flex items-start gap-3 mb-4">
            <x-admin.icon name="alert" class="w-6 h-6 shrink-0" style="color: var(--color-saffron-600)" />
            <p class="text-sm">
                <strong>These codes are shown exactly once.</strong>
                Copy them somewhere safe (a password manager is ideal).
                Each code works a single time if you lose access to your authenticator app.
            </p>
        </div>

        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-5">
            @foreach($recoveryCodes as $code)
                <li><code class="block px-3 py-2 rounded-lg bg-[var(--color-bg-soft)] font-mono text-sm tracking-widest text-center select-all">{{ $code }}</code></li>
            @endforeach
        </ul>

        <div class="flex flex-wrap gap-3">
            <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(@json(implode("\n", $recoveryCodes))); this.textContent='Copied!';">
                Copy all codes
            </button>
            <a href="{{ route('reddy.account.security') }}" class="btn">I've saved them — continue</a>
        </div>
    </div>
</div>
@endsection
