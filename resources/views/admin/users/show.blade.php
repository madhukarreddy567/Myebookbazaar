@extends('layouts.admin')

@section('title', 'User — ' . $user->email)
@section('page_title', 'User')
@section('breadcrumb', 'Sales / Users / Detail')

@section('content')
<x-admin.page-header :title="$user->name" :subtitle="$user->email">
    <x-slot:actions>
        <a href="{{ route('reddy.users.index') }}" class="btn btn-ghost">Back to users</a>
    </x-slot:actions>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<div class="grid xl:grid-cols-3 gap-4">
    <div class="surface p-6">
        <h3 class="font-display text-xl font-bold mb-4">Profile</h3>
        <dl class="space-y-3 text-sm">
            <div><dt class="font-medium" style="color: var(--color-muted)">Country</dt><dd class="font-semibold">{{ $user->country_name ?? '—' }} ({{ $user->country_code }})</dd></div>
            <div><dt class="font-medium" style="color: var(--color-muted)">Pays in</dt><dd><x-admin.badge :status="$user->pricing_region === 'IN' ? 'inr' : 'usd'">{{ $user->pricing_region === 'IN' ? 'INR' : 'USD' }}</x-admin.badge></dd></div>
            <div><dt class="font-medium" style="color: var(--color-muted)">Email verified</dt><dd class="font-semibold">{{ $user->hasVerifiedEmail() ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="font-medium" style="color: var(--color-muted)">Joined</dt><dd class="font-semibold">{{ $user->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</dd></div>
            <div><dt class="font-medium" style="color: var(--color-muted)">Status</dt><dd><x-admin.badge :status="$user->is_blocked ? 'blocked' : 'active'" /></dd></div>
        </dl>
        @if(auth('admin')->user()?->isSuperAdmin())
        <div class="mt-5 pt-5 border-t border-[var(--color-border)]">
            @if($user->is_blocked)
            <form method="POST" action="{{ route('reddy.users.unblock', $user) }}">
                @csrf
                <button type="submit" class="btn btn-secondary w-full">Unblock account</button>
            </form>
            @else
            <form method="POST" action="{{ route('reddy.users.block', $user) }}" onsubmit="return confirm('Block {{ $user->email }}? They will not be able to log in.');">
                @csrf
                <button type="submit" class="btn btn-danger w-full">Block account</button>
            </form>
            @endif
            <p class="mt-2 text-xs" style="color: var(--color-muted)">Blocking is audited. Blocked users cannot log in.</p>
        </div>
        <div class="mt-5 pt-5 border-t border-[var(--color-border)]">
            <h4 class="font-bold text-sm mb-2" style="color: var(--color-text)">Change country</h4>
            <form method="POST" action="{{ route('reddy.users.country', $user) }}">
                @csrf
                @method('PUT')
                <label for="country_code" class="sr-only">Country</label>
                <select id="country_code" name="country_code" class="input w-full" required>
                    @foreach($countries as $c)
                    <option value="{{ $c->iso_code }}" @selected($user->country_code === $c->iso_code)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('country_code')<p class="field-error mt-1"><x-admin.icon name="alert" class="w-4 h-4" />{{ $message }}</p>@enderror
                <button type="submit" class="btn btn-secondary w-full mt-2">Update country</button>
            </form>
            <p class="mt-2 text-xs" style="color: var(--color-muted)">Also updates the pricing region (India → INR, others → USD). Audited. Customers cannot change their own country.</p>
        </div>
        @endif
    </div>

    <div class="surface p-6">
        <h3 class="font-display text-xl font-bold mb-4">Lifetime spend</h3>
        <p class="text-3xl font-extrabold tabular-nums">₹{{ number_format($spentInr, 2) }}</p>
        <p class="text-xs mb-4" style="color: var(--color-muted)">Paid orders in INR</p>
        <p class="text-3xl font-extrabold tabular-nums">${{ number_format($spentUsd, 2) }}</p>
        <p class="text-xs" style="color: var(--color-muted)">Paid orders in USD — kept separate, never combined</p>
    </div>

    <div class="surface p-6">
        <h3 class="font-display text-xl font-bold mb-4">Recent downloads</h3>
        @if($downloads->isEmpty())
        <x-admin.empty-state title="No downloads yet." icon="book" />
        @else
        <ul class="space-y-2 text-sm">
            @foreach($downloads as $dl)
            <li class="flex justify-between gap-2">
                <span class="font-medium truncate">{{ $dl->book?->title ?? 'Deleted book' }}</span>
                <span class="text-xs whitespace-nowrap" style="color: var(--color-muted)">{{ $dl->downloaded_at?->setTimezone('Asia/Kolkata')->format('d M Y') }}</span>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>

<div class="surface p-6 mt-4">
    <h3 class="font-display text-xl font-bold mb-4">Orders</h3>
    @if($user->orders->isEmpty())
    <x-admin.empty-state title="No orders yet." icon="cart" />
    @else
    <div class="overflow-x-auto -mx-6 px-6">
        <table class="data-table">
            <thead><tr><th>Order</th><th class="text-right">Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($user->orders as $order)
                <tr>
                    <td><a href="{{ route('reddy.orders.show', $order) }}" class="font-semibold text-[var(--color-teal-700)] hover:underline">{{ $order->order_number }}</a></td>
                    <td class="text-right tabular-nums font-semibold">{{ $order->currency === 'INR' ? '₹' : '$' }}{{ number_format($order->grand_total, 2) }}</td>
                    <td><x-admin.badge :status="$order->status" /></td>
                    <td class="text-sm" style="color: var(--color-muted)">{{ $order->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
