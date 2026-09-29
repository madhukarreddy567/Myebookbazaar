@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
{{-- Phase 6: real 2FA state. Shows only when this admin has NOT enabled 2FA. --}}
@php
    $me = auth('admin')->user();
@endphp
@if($me && !$me->hasTwoFactorEnabled() && !session('dismissed_2fa_warning'))
<div class="mb-5 flex items-start gap-3 rounded-2xl border border-[rgb(183_121_31/0.35)] bg-[var(--color-saffron-100)] p-4">
    <x-admin.icon name="shield" class="w-5 h-5 shrink-0 mt-0.5 text-[var(--color-warning)]" />
    <p class="text-sm text-[var(--color-text)] flex-1">
        Turn on two-factor authentication to protect this account.
        <a href="{{ route('reddy.2fa.setup') }}" class="font-semibold underline underline-offset-2">Set it up now</a>
    </p>
    <form method="POST" action="{{ route('reddy.dismiss-2fa-warning') }}">
        @csrf
        <button type="submit" class="text-xs font-semibold text-[var(--color-warning)] underline underline-offset-2 hover:opacity-80">Dismiss</button>
    </form>
</div>
@endif

<x-admin.page-header title="Dashboard" subtitle="Here is what is happening in your store." />

<div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
    <x-admin.stat-card :value="'₹' . number_format($revenueInr, 2)" label="Revenue (INR)" />
    <x-admin.stat-card :value="'$' . number_format($revenueUsd, 2)" label="Revenue (USD)" />
    <x-admin.stat-card :value="number_format($ordersToday)" label="Orders today" />
    <x-admin.stat-card :value="number_format($customers)" label="Customers" />
    <x-admin.stat-card :value="number_format($booksSold)" label="Books sold" />
</div>

<div class="grid xl:grid-cols-2 gap-4 mt-4" x-data="{ currency: 'INR' }">
    <div class="surface p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Revenue — last 30 days</h3>
            <div class="flex rounded-full border border-[var(--color-border)] p-0.5 text-xs font-semibold" role="tablist" aria-label="Currency">
                <button type="button" role="tab" :aria-selected="currency === 'INR'" @click="currency = 'INR'"
                    :class="currency === 'INR' ? 'bg-[var(--color-teal-900)] text-white rounded-full px-3 py-1' : 'px-3 py-1 text-[var(--color-muted)]'">INR</button>
                <button type="button" role="tab" :aria-selected="currency === 'USD'" @click="currency = 'USD'"
                    :class="currency === 'USD' ? 'bg-[var(--color-teal-900)] text-white rounded-full px-3 py-1' : 'px-3 py-1 text-[var(--color-muted)]'">USD</button>
            </div>
        </div>
        @php
            $maxVal = max(max($chartInr), max($chartUsd), 1);
            $w = 600; $h = 180; $pad = 8; $n = count($chartLabels);
            $bw = ($w - $pad * 2) / $n;
        @endphp
        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-44" role="img" aria-label="Daily revenue bar chart for the last 30 days">
            @foreach($chartInr as $i => $v)
                <g x-show="currency === 'INR'">
                    <rect x="{{ $pad + $i * $bw + 1 }}" y="{{ $h - 20 - ($v / $maxVal) * ($h - 40) }}"
                        width="{{ max($bw - 2, 1) }}" height="{{ max(($v / $maxVal) * ($h - 40), $v > 0 ? 2 : 0) }}"
                        rx="2" fill="var(--color-teal-700)">
                        <title>{{ $chartLabels[$i] }}: ₹{{ number_format($v, 2) }}</title>
                    </rect>
                </g>
            @endforeach
            @foreach($chartUsd as $i => $v)
                <g x-show="currency === 'USD'" x-cloak>
                    <rect x="{{ $pad + $i * $bw + 1 }}" y="{{ $h - 20 - ($v / $maxVal) * ($h - 40) }}"
                        width="{{ max($bw - 2, 1) }}" height="{{ max(($v / $maxVal) * ($h - 40), $v > 0 ? 2 : 0) }}"
                        rx="2" fill="var(--color-saffron-500)">
                        <title>{{ $chartLabels[$i] }}: ${{ number_format($v, 2) }}</title>
                    </rect>
                </g>
            @endforeach
        </svg>
        <p class="mt-2 text-xs text-[var(--color-muted)]">
            <span x-show="currency === 'INR'">Total last 30 days: ₹{{ number_format(array_sum($chartInr), 2) }} across India (paid orders).</span>
            <span x-show="currency === 'USD'" x-cloak>Total last 30 days: ${{ number_format(array_sum($chartUsd), 2) }} international (paid orders).</span>
            INR and USD are never combined.
        </p>
    </div>

    <div class="surface p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Top selling books</h3>
            <a href="{{ route('reddy.books.index') }}" class="text-sm font-semibold text-[var(--color-teal-700)] hover:underline">All books</a>
        </div>
        @if($topBooks->isEmpty())
            <x-admin.empty-state title="No sales yet." hint="Top sellers appear here after the first paid order." icon="book" />
        @else
            <ul class="divide-y divide-[var(--color-border)]">
                @foreach($topBooks as $row)
                    <li class="py-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-[var(--color-teal-100)] text-[var(--color-teal-900)] font-bold text-sm flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm text-[var(--color-text)] truncate">{{ $row->book?->title ?? 'Deleted book' }}</p>
                            <p class="text-xs text-[var(--color-muted)]">{{ $row->sold }} sold</p>
                        </div>
                        <p class="text-sm font-semibold text-[var(--color-text)] tabular-nums text-right">
                            @if($row->revenue_inr > 0)<span class="block">₹{{ number_format($row->revenue_inr, 2) }}</span>@endif
                            @if($row->revenue_usd > 0)<span class="block">${{ number_format($row->revenue_usd, 2) }}</span>@endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="surface p-6 mt-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Recent orders</h3>
        <a href="{{ route('reddy.orders.index') }}" class="text-sm font-semibold text-[var(--color-teal-700)] hover:underline">All orders</a>
    </div>
    @if($recentOrders->isEmpty())
        <x-admin.empty-state title="No orders yet." hint="Orders appear here as customers check out." icon="cart" />
    @else
        <div class="overflow-x-auto -mx-6 px-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('reddy.orders.show', $order) }}" class="font-semibold text-[var(--color-teal-700)] hover:underline">{{ $order->order_number }}</a></td>
                            <td class="text-sm">{{ $order->user?->email ?? '—' }}</td>
                            <td class="text-right tabular-nums font-semibold">{{ $order->currency === 'INR' ? '₹' : '$' }}{{ number_format($order->grand_total, 2) }} <span class="text-xs font-normal text-[var(--color-muted)]">{{ $order->currency }}</span></td>
                            <td><x-admin.badge :status="$order->status" /></td>
                            <td class="text-sm text-[var(--color-muted)]">{{ $order->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
