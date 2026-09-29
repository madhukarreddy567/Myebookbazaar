@extends('layouts.admin')

@section('title', 'Coupons')
@section('page_title', 'Coupons')
@section('breadcrumb', 'Store / Coupons')

@section('content')
<x-admin.page-header title="Coupons" subtitle="Discount codes — every discount is calculated on the server.">
    <a href="{{ route('reddy.coupons.create') }}" class="btn btn-primary">
        <x-admin.icon name="ticket" class="w-4 h-4" /> Add coupon
    </a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<form method="GET" action="{{ route('reddy.coupons.index') }}" class="surface p-4 mb-4 flex flex-col md:flex-row gap-3 md:items-end">
    <div class="flex-1">
        <label for="q" class="field-label">Search code</label>
        <input id="q" name="q" type="search" value="{{ request('q') }}" class="input" autocomplete="off">
    </div>
    <button type="submit" class="btn btn-secondary">Apply</button>
</form>

<div class="surface overflow-hidden">
    @if($coupons->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Code</th>
                    <th scope="col">Discount</th>
                    <th scope="col">Currency</th>
                    <th scope="col" class="text-right">Used</th>
                    <th scope="col">Valid window</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($coupons as $coupon)
                <tr>
                    <td><a href="{{ route('reddy.coupons.show', $coupon) }}" class="font-mono font-bold">{{ $coupon->code }}</a></td>
                    <td>{{ $coupon->type === 'percent' ? rtrim(rtrim($coupon->value, '0'), '.').'%' : ($coupon->currency === 'INR' ? '₹' : ($coupon->currency === 'USD' ? '$' : '₹/$')).$coupon->value }}</td>
                    <td>{{ $coupon->currency }}</td>
                    <td class="text-right">{{ $coupon->usages_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                    <td class="text-sm">
                        {{ $coupon->starts_at ? $coupon->starts_at->format('d M Y') : '—' }}
                        →
                        {{ $coupon->ends_at ? $coupon->ends_at->format('d M Y') : '—' }}
                    </td>
                    <td><x-admin.badge :status="$coupon->is_active ? 'active' : 'disabled'">{{ $coupon->is_active ? 'Active' : 'Disabled' }}</x-admin.badge></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.coupons.edit', $coupon) }}" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $coupons->links() }}</div>
    @else
    <x-admin.empty-state title="No coupons yet" hint="Create a discount code for a sale or a partner promotion."
        action="Add coupon" href="{{ route('reddy.coupons.create') }}" />
    @endif
</div>
@endsection
