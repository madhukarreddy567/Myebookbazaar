@extends('layouts.admin')

@section('title', 'Support tickets')
@section('page_title', 'Support tickets')
@section('breadcrumb', 'Sales / Support')

@section('content')
<x-admin.page-header title="Support tickets" subtitle="Customer inbox — reply, close or reopen. Every action is audit-logged." />

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

{{-- Counters --}}
<div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div class="surface p-5">
        <p class="text-sm font-medium" style="color: var(--color-muted)">Open tickets</p>
        <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $openCount }}</p>
    </div>
    <div class="surface p-5">
        <p class="text-sm font-medium" style="color: var(--color-muted)">Closed tickets</p>
        <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $closedCount }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reddy.tickets.index') }}" class="surface p-4 mb-4 grid md:grid-cols-3 gap-3 md:items-end">
    <div>
        <label for="status" class="field-label">Status</label>
        <select id="status" name="status" class="input">
            <option value="open" @selected(($filters['status'] ?? 'open') === 'open')>Open</option>
            <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
            <option value="all" @selected(($filters['status'] ?? '') === 'all')>All</option>
        </select>
    </div>
    <div>
        <label for="q" class="field-label">Subject or customer</label>
        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" autocomplete="off">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-secondary">Apply</button>
        <a href="{{ route('reddy.tickets.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($tickets->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Subject</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Category</th>
                    <th scope="col">Order</th>
                    <th scope="col">Status</th>
                    <th scope="col">Opened</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                <tr>
                    <td>
                        <a href="{{ route('reddy.tickets.show', $ticket) }}" class="font-semibold hover:underline">#{{ $ticket->id }} — {{ $ticket->subject }}</a>
                    </td>
                    <td>
                        <div>{{ $ticket->user->name ?? '—' }}</div>
                        <div class="text-sm" style="color: var(--color-muted)">{{ $ticket->user->email ?? '' }}</div>
                    </td>
                    <td>{{ $ticket->categoryLabel() }}</td>
                    <td class="font-mono text-xs">{{ $ticket->order?->order_number ?? '—' }}</td>
                    <td>
                        <x-admin.badge :status="match($ticket->status) {
                            'open' => 'pending',
                            'awaiting_customer' => 'active',
                            'awaiting_support' => 'pending',
                            'closed' => 'refunded',
                            default => 'inactive',
                        }">{{ $ticket->statusLabel() }}</x-admin.badge>
                    </td>
                    <td class="whitespace-nowrap text-sm">{{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('reddy.tickets.show', $ticket) }}" class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $tickets->links() }}</div>
    @else
    <x-admin.empty-state title="No tickets" hint="No support tickets match this filter." />
    @endif
</div>
@endsection
