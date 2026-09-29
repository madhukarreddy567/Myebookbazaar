@extends('layouts.admin')

@section('title', 'Security log')
@section('page_title', 'Security log')
@section('breadcrumb', 'System / Security log')

@section('content')
<x-admin.page-header title="Security log" subtitle="Failed logins, lockouts, bot traps, 2FA events and suspicious webhooks. Append-only — entries are never edited or deleted." />

<form method="GET" action="{{ route('reddy.security-log.index') }}" class="surface p-4 mb-4 grid md:grid-cols-4 gap-3 md:items-end">
    <div>
        <label for="event" class="field-label">Event contains</label>
        <input id="event" name="event" type="search" value="{{ $filters['event'] ?? '' }}" class="input" autocomplete="off" placeholder="e.g. login.failed">
    </div>
    <div>
        <label for="admin_id" class="field-label">Admin</label>
        <select id="admin_id" name="admin_id" class="input">
            <option value="">All</option>
            @foreach($admins as $a)
            <option value="{{ $a->id }}" @selected((string)($filters['admin_id'] ?? '') === (string)$a->id)>{{ $a->name }} ({{ $a->email }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="field-label">From</label>
        <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="input">
    </div>
    <div>
        <label for="to" class="field-label">To</label>
        <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="input">
    </div>
    <div class="md:col-span-4 flex gap-2">
        <button type="submit" class="btn btn-secondary">Apply</button>
        <a href="{{ route('reddy.security-log.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($logs->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Time (IST)</th><th>Event</th><th>Message</th><th>Who</th><th>IP</th></tr></thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-sm whitespace-nowrap" style="color: var(--color-muted)">{{ $log->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                    <td><code class="text-xs font-mono">{{ $log->event }}</code></td>
                    <td class="text-sm">{{ $log->message }}</td>
                    <td class="text-sm font-medium">{{ $log->admin?->email ?? ($log->user?->email ?? '—') }}</td>
                    <td class="text-sm" style="color: var(--color-muted)">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $logs->links() }}</div>
    @else
    <x-admin.empty-state title="No security events found." hint="Try clearing the filters." icon="shield" />
    @endif
</div>
@endsection
