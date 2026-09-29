@extends('layouts.admin')

@section('title', 'Combos')
@section('page_title', 'Combos')
@section('breadcrumb', 'Store / Combos')

@section('content')
<x-admin.page-header title="Combos" subtitle="Book bundles sold at one price — one order item per book, so every book stays downloadable.">
    <a href="{{ route('reddy.combos.create') }}" class="btn btn-primary">
        <x-admin.icon name="layers" class="w-4 h-4" /> Add combo
    </a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<div class="surface overflow-hidden">
    @if($combos->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Combo</th>
                    <th scope="col" class="text-right">Books</th>
                    <th scope="col" class="text-right">INR</th>
                    <th scope="col" class="text-right">USD</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($combos as $combo)
                <tr>
                    <td>
                        <p class="font-semibold">{{ $combo->title }}</p>
                        <p class="text-xs text-[var(--color-muted)]">/{{ $combo->slug }}</p>
                    </td>
                    <td class="text-right">{{ count($combo->book_ids ?? []) }}</td>
                    <td class="text-right">₹{{ $combo->combo_price_inr }}</td>
                    <td class="text-right">${{ $combo->combo_price_usd }}</td>
                    <td><x-admin.badge :status="$combo->is_active ? 'active' : 'disabled'">{{ $combo->is_active ? 'Active' : 'Disabled' }}</x-admin.badge></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.combos.edit', $combo) }}" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $combos->links() }}</div>
    @else
    <x-admin.empty-state title="No combos yet" hint="Bundle two or more ebooks at one price."
        action="Add combo" href="{{ route('reddy.combos.create') }}" />
    @endif
</div>
@endsection
