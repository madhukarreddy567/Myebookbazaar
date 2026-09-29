@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')
@section('breadcrumb', 'Sales / Users')

@section('content')
<x-admin.page-header title="Users" subtitle="Customers, their regions, orders and account status.">
    <x-slot:actions>
        <a href="{{ route('reddy.users.export', request()->query()) }}" class="btn btn-secondary">Export CSV</a>
    </x-slot:actions>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<form method="GET" action="{{ route('reddy.users.index') }}" class="surface p-4 mb-4 grid md:grid-cols-5 gap-3 md:items-end">
    <div class="md:col-span-2">
        <label for="q" class="field-label">Name or email</label>
        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" autocomplete="off">
    </div>
    <div>
        <label for="country" class="field-label">Country</label>
        <select id="country" name="country" class="input">
            <option value="">All</option>
            @foreach($countries as $c)
            <option value="{{ $c->country_code }}" @selected(($filters['country'] ?? '') === $c->country_code)>{{ $c->country_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="region" class="field-label">Pays in</label>
        <select id="region" name="region" class="input">
            <option value="">All</option>
            <option value="IN" @selected(($filters['region'] ?? '') === 'IN')>INR (India)</option>
            <option value="INTL" @selected(($filters['region'] ?? '') === 'INTL')>USD (International)</option>
        </select>
    </div>
    <div>
        <label for="blocked" class="field-label">Status</label>
        <select id="blocked" name="blocked" class="input">
            <option value="">All</option>
            <option value="0" @selected(($filters['blocked'] ?? '') === '0')>Active</option>
            <option value="1" @selected(($filters['blocked'] ?? '') === '1')>Blocked</option>
        </select>
    </div>
    <div class="md:col-span-5 flex gap-2">
        <button type="submit" class="btn btn-secondary">Apply</button>
        <a href="{{ route('reddy.users.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($users->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Customer</th>
                    <th scope="col">Country</th>
                    <th scope="col">Pays in</th>
                    <th scope="col" class="text-right">Paid orders</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <p class="font-semibold">{{ $user->name }}</p>
                        <p class="text-xs" style="color: var(--color-muted)">{{ $user->email }}</p>
                    </td>
                    <td class="text-sm">{{ $user->country_name ?? $user->country_code }}</td>
                    <td><x-admin.badge :status="$user->pricing_region === 'IN' ? 'inr' : 'usd'">{{ $user->pricing_region === 'IN' ? 'INR' : 'USD' }}</x-admin.badge></td>
                    <td class="text-right tabular-nums">{{ $user->orders_count }}</td>
                    <td><x-admin.badge :status="$user->is_blocked ? 'blocked' : 'active'" /></td>
                    <td class="text-sm" style="color: var(--color-muted)">{{ $user->created_at->setTimezone('Asia/Kolkata')->format('d M Y') }}</td>
                    <td class="text-right"><a href="{{ route('reddy.users.show', $user) }}" class="font-semibold text-[var(--color-teal-700)] hover:underline text-sm">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $users->links() }}</div>
    @else
    <x-admin.empty-state title="No users found." hint="Try clearing the filters." icon="users" />
    @endif
</div>
@endsection
