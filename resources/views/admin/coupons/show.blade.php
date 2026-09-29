@extends('layouts.admin')

@section('title', 'Coupon '.$coupon->code)
@section('page_title', 'Coupon '.$coupon->code)
@section('breadcrumb', 'Store / Coupons / '.$coupon->code)

@section('content')
<x-admin.page-header :title="'Coupon '.$coupon->code" subtitle="Usage statistics and details.">
    <a href="{{ route('reddy.coupons.usages.export', $coupon) }}" class="btn btn-ghost">Export usages CSV</a>
    <form method="POST" action="{{ route('reddy.coupons.duplicate', $coupon) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-secondary">Duplicate</button>
    </form>
    <a href="{{ route('reddy.coupons.edit', $coupon) }}" class="btn btn-secondary">Edit coupon</a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<div class="grid md:grid-cols-3 gap-4 mb-6">
    <x-admin.stat-card label="Times used" :value="(string) $coupon->usages_count" />
    <x-admin.stat-card label="Usage limit" :value="$coupon->max_uses ? (string) $coupon->max_uses : 'Unlimited'" />
    <x-admin.stat-card label="Status" :value="$coupon->is_active ? 'Active' : 'Disabled'" />
</div>

<div class="surface p-6 mb-6 max-w-2xl">
    <dl class="grid md:grid-cols-2 gap-4 text-sm">
        <div><dt class="text-[var(--color-muted)]">Discount</dt><dd class="font-semibold">{{ $coupon->type === 'percent' ? rtrim(rtrim($coupon->value, '0'), '.').'%' : $coupon->value.' '.$coupon->currency }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Currency</dt><dd class="font-semibold">{{ $coupon->currency }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Minimum order</dt><dd class="font-semibold">{{ $coupon->min_order_amount ?? '—' }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Per customer</dt><dd class="font-semibold">{{ $coupon->max_uses_per_user }} use(s)</dd></div>
        <div><dt class="text-[var(--color-muted)]">Valid from</dt><dd class="font-semibold">{{ $coupon->starts_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Valid until</dt><dd class="font-semibold">{{ $coupon->ends_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Applies to</dt><dd class="font-semibold">{{ $coupon->applies_to === 'all' ? 'All ebooks' : 'Specific ebooks' }}</dd></div>
        <div><dt class="text-[var(--color-muted)]">Live now</dt><dd class="font-semibold">{{ $coupon->isLive() ? 'Yes' : 'No' }}</dd></div>
    </dl>
</div>

<div class="surface overflow-hidden">
    <div class="p-4 border-b border-[var(--color-border)]"><h2 class="font-bold">Recent redemptions</h2></div>
    @if($usages->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th scope="col">Customer</th><th scope="col">Order</th><th scope="col">Used at</th></tr></thead>
            <tbody>
                @foreach($usages as $usage)
                <tr>
                    <td>{{ $usage->user->name }} <span class="text-xs text-[var(--color-muted)]">{{ $usage->user->email }}</span></td>
                    <td><a href="{{ route('reddy.orders.show', $usage->order) }}">{{ $usage->order->order_number }}</a></td>
                    <td>{{ $usage->used_at->format('d M Y, h:i A') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $usages->links() }}</div>
    @else
    <x-admin.empty-state title="No redemptions yet" hint="When customers use this coupon, each use appears here." />
    @endif
</div>
@endsection
