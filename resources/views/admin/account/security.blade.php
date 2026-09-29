@extends('layouts.admin')

@section('title', 'Account security')
@section('page_title', 'Account security')
@section('breadcrumb', 'Account / Security')

@section('content')
<x-admin.page-header title="Account security" subtitle="Two-factor authentication and recent security events for your admin account." />

@if(session('status'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm px-4 py-3 mb-6" role="status">
        {{ session('status') }}
    </div>
@endif

<div class="grid gap-6 max-w-3xl">
    {{-- 2FA status card --}}
    <div class="surface section-card">
        <div class="flex items-start gap-4">
            <div class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                 style="background: {{ $admin->hasTwoFactorEnabled() ? 'rgb(22 163 74 / 0.12)' : 'rgb(201 60 60 / 0.10)' }}">
                <x-admin.icon name="shield" class="w-6 h-6"
                    style="color: {{ $admin->hasTwoFactorEnabled() ? '#16a34a' : 'var(--color-danger)' }}" />
            </div>
            <div class="flex-1">
                <h2 class="font-bold text-lg">Two-factor authentication</h2>
                @if($admin->hasTwoFactorEnabled())
                    <p class="text-sm text-[var(--color-muted)] mt-1">
                        Enabled {{ $admin->two_factor_enabled_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}.
                        You will be asked for a code from your authenticator app on every sign-in.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('reddy.2fa.disable') }}"
                              onsubmit="return confirm('Turn off two-factor authentication? Your account will be protected by password alone.');">
                            @csrf
                            @method('DELETE')
                            <label for="disable_password" class="field-label">Confirm with your password</label>
                            <div class="flex gap-2 mt-1">
                                <input id="disable_password" name="password" type="password" required
                                       autocomplete="current-password" class="input max-w-xs"
                                       placeholder="Current password">
                                <button type="submit" class="btn shrink-0"
                                        style="background: rgb(201 60 60 / 0.1); color: var(--color-danger)">
                                    Turn off 2FA
                                </button>
                            </div>
                            @error('password')
                                <p class="field-error mt-1"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>
                            @enderror
                        </form>
                    </div>
                @else
                    <p class="text-sm text-[var(--color-muted)] mt-1">
                        Not enabled. Anyone with your password can sign in as you.
                        @if($require2fa && $admin->isSuperAdmin())
                            <strong class="text-[var(--color-danger)]">Required for Super Admins by Settings → Security.</strong>
                        @endif
                    </p>
                    <a href="{{ route('reddy.2fa.setup') }}" class="btn btn-primary mt-4">
                        Set up two-factor authentication
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent security events --}}
    <div class="surface section-card">
        <h2 class="font-bold text-lg mb-1">Recent security events</h2>
        <p class="text-sm text-[var(--color-muted)] mb-4">Logins, 2FA changes and lockouts on your account.</p>

        @if($recentEvents->isEmpty())
            <x-admin.empty-state title="No events yet" message="Security events for your account will appear here." />
        @else
            <ul class="divide-y divide-[var(--color-border)]">
                @foreach($recentEvents as $event)
                    <li class="py-3 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold">{{ $event->message }}</p>
                            <p class="text-xs text-[var(--color-muted)] mt-0.5">
                                <span class="font-mono">{{ $event->event }}</span>
                                · {{ $event->ip_address ?? 'unknown IP' }}
                            </p>
                        </div>
                        <time class="text-xs text-[var(--color-muted)] shrink-0" datetime="{{ $event->created_at->toIso8601String() }}">
                            {{ $event->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
