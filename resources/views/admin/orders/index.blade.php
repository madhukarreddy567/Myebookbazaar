@extends('layouts.admin')

@section('title', 'Orders')
@section('page_title', 'Orders')
@section('breadcrumb', 'Sales / Orders')

@section('content')
<x-admin.page-header title="Orders" subtitle="Every purchase — INR and USD revenue are always shown separately.">
    <x-slot:actions>
        <a href="{{ route('reddy.orders.export', request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    </x-slot:actions>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

{{-- Revenue cards: one per currency, never combined --}}
<div class="grid sm:grid-cols-2 gap-4 mb-4">
    @foreach(['INR' => '₹', 'USD' => '$'] as $cur => $sym)
    <div class="surface p-5">
        <p class="text-sm font-medium" style="color: var(--color-muted)">Revenue ({{ $cur }})</p>
        <p class="text-3xl font-extrabold tabular-nums mt-1">
            {{ $sym }}{{ number_format((float) ($revenue[$cur]->total ?? 0), $cur === 'INR' ? 0 : 2) }}
        </p>
        <p class="text-sm mt-1" style="color: var(--color-muted)">{{ $revenue[$cur]->count ?? 0 }} paid orders</p>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reddy.orders.index') }}" class="surface p-4 mb-4 grid md:grid-cols-6 gap-3 md:items-end">
    <div class="md:col-span-2">
        <label for="q" class="field-label">Order no. or customer email</label>
        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" autocomplete="off">
    </div>
    <div>
        <label for="status" class="field-label">Status</label>
        <select id="status" name="status" class="input">
            <option value="">All</option>
            @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $v => $l)
            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="currency" class="field-label">Currency</label>
        <select id="currency" name="currency" class="input">
            <option value="">All</option>
            <option value="INR" @selected(($filters['currency'] ?? '') === 'INR')>INR</option>
            <option value="USD" @selected(($filters['currency'] ?? '') === 'USD')>USD</option>
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
    <div class="md:col-span-6 flex gap-2">
        <button type="submit" class="btn btn-secondary">Apply</button>
        <a href="{{ route('reddy.orders.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($orders->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Order</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Total</th>
                    <th scope="col">Date</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td><a href="{{ route('reddy.orders.show', $order) }}" class="font-bold hover:underline">{{ $order->order_number }}</a></td>
                    <td class="text-sm">{{ $order->user->email ?? '—' }}</td>
                    <td><x-admin.badge :type="match($order->status) {
                        'paid' => 'success', 'pending' => 'warning',
                        'failed' => 'danger', default => 'neutral',
                    }">{{ $order->statusLabel() }}</x-admin.badge></td>
                    <td class="text-right tabular-nums font-semibold">{{ $order->currency === 'INR' ? '₹' : '$' }}{{ number_format((float) $order->grand_total, $order->currency === 'INR' ? 0 : 2) }}</td>
                    <td class="text-sm whitespace-nowrap">{{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}</td>
                    <td class="text-right"><a href="{{ route('reddy.orders.show', $order) }}" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $orders->links() }}</div>
    @else
    <x-admin.empty-state title="No orders found" text="Try widening the filters — or wait for the first sale." />
    @endif
</div>
@endsection
