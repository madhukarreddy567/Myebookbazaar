@extends('layouts.admin')

@section('title', 'Sessions')
@section('page_title', 'Sessions')
@section('breadcrumb', 'Account / Sessions')

@section('content')
<x-admin.page-header title="Sessions" subtitle="Devices and browsers signed in as {{ auth('admin')->user()->email }}." />

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-danger)">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<div class="grid xl:grid-cols-3 gap-4">
    <div class="surface p-6 xl:col-span-2">
        <h3 class="font-display text-xl font-bold mb-4">Active sessions</h3>
        @if(empty($sessions))
        <x-admin.empty-state title="No sessions found." icon="user" />
        @else
        <ul class="divide-y divide-[var(--color-border)]">
            @foreach($sessions as $s)
            <li class="py-3 flex items-center gap-3">
                <x-admin.icon name="user" class="w-8 h-8 shrink-0 p-1.5 rounded-full bg-[var(--color-teal-100)] text-[var(--color-teal-900)]" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate">{{ $s['agent'] ?: 'Unknown device' }}</p>
                    <p class="text-xs" style="color: var(--color-muted)">
                        {{ $s['ip'] }} · last active {{ \Carbon\Carbon::createFromTimestamp($s['last'])->setTimezone('Asia/Kolkata')->diffForHumans() }}
                    </p>
                </div>
                @if($s['current'])
                <x-admin.badge status="active">This device</x-admin.badge>
                @endif
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    <div class="surface p-6">
        <h3 class="font-display text-xl font-bold mb-2">Log out other sessions</h3>
        <p class="text-sm mb-4" style="color: var(--color-muted)">Ends every session except this one. Enter your password to confirm.</p>
        <form method="POST" action="{{ route('reddy.sessions.destroy-others') }}" class="space-y-4">
            @csrf @method('DELETE')
            <div>
                <label for="password" class="field-label">Current password</label>
                <input id="password" name="password" type="password" required class="input" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-danger w-full">Log out other sessions</button>
        </form>
    </div>
</div>
@endsection
