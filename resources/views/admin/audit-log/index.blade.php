@extends('layouts.admin')

@section('title', 'Audit log')
@section('page_title', 'Audit log')
@section('breadcrumb', 'System / Audit log')

@section('content')
<x-admin.page-header title="Audit log" subtitle="Every admin action — who did what, when, and from where. Append-only." />

<form method="GET" action="{{ route('reddy.audit-log.index') }}" class="surface p-4 mb-4 grid md:grid-cols-4 gap-3 md:items-end">
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
        <label for="action" class="field-label">Action contains</label>
        <input id="action" name="action" type="search" value="{{ $filters['action'] ?? '' }}" class="input" autocomplete="off" placeholder="e.g. order">
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
        <a href="{{ route('reddy.audit-log.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($logs->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th></tr></thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="text-sm whitespace-nowrap" style="color: var(--color-muted)">{{ $log->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                    <td class="text-sm font-medium">{{ $log->admin?->email ?? 'system' }}</td>
                    <td><code class="text-xs font-mono">{{ $log->action }}</code></td>
                    <td class="text-sm">{{ $log->target_type }}{{ $log->target_id ? ' #'.$log->target_id : '' }}</td>
                    <td class="text-sm" style="color: var(--color-muted)">{{ $log->ip_address }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $logs->links() }}</div>
    @else
    <x-admin.empty-state title="No audit entries found." hint="Try clearing the filters." icon="list" />
    @endif
</div>
@endsection
