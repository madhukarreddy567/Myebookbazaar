@extends('layouts.admin')

@section('title', 'Offers')
@section('page_title', 'Offers')
@section('breadcrumb', 'Store / Offers')

@section('content')
<x-admin.page-header title="Offers" subtitle="Every book and combo with a sale price — and whether the sale is live right now." />

@if(!$switchOn)
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-warning)">
    The “Sales and offers” switch is OFF — no sale prices are shown to customers until it is turned on in Settings.
</div>
@endif

<div class="surface overflow-hidden mb-6">
    <div class="p-4 border-b border-[var(--color-border)]"><h2 class="font-bold">Books on sale</h2></div>
    @if($books->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th scope="col">Book</th><th scope="col">Regular</th><th scope="col">Sale</th><th scope="col">Window</th><th scope="col">Live now</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach($books as $book)
                <tr>
                    <td class="font-semibold">{{ $book->title }}</td>
                    <td>₹{{ $book->price_inr }} / ${{ $book->price_usd }}</td>
                    <td>{{ $book->sale_price_inr ? '₹'.$book->sale_price_inr : '—' }} / {{ $book->sale_price_usd ? '$'.$book->sale_price_usd : '—' }}</td>
                    <td class="text-sm">{{ $book->sale_starts_at?->format('d M Y') ?? '—' }} → {{ $book->sale_ends_at?->format('d M Y') ?? '—' }}</td>
                    <td><x-admin.badge :status="$book->sale_live && $switchOn ? 'paid' : 'disabled'">{{ $book->sale_live && $switchOn ? 'Live' : 'Not live' }}</x-admin.badge></td>
                    <td class="text-right"><a href="{{ route('reddy.books.edit', $book) }}" class="btn btn-ghost btn-sm">Edit</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <x-admin.empty-state title="No book sales" hint="Set a sale price on any book to feature it here." />
    @endif
</div>

<div class="surface overflow-hidden">
    <div class="p-4 border-b border-[var(--color-border)]"><h2 class="font-bold">Combos on sale</h2></div>
    @if($combos->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th scope="col">Combo</th><th scope="col">Regular</th><th scope="col">Sale</th><th scope="col">Live now</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach($combos as $combo)
                <tr>
                    <td class="font-semibold">{{ $combo->title }}</td>
                    <td>₹{{ $combo->combo_price_inr }} / ${{ $combo->combo_price_usd }}</td>
                    <td>{{ $combo->sale_price_inr ? '₹'.$combo->sale_price_inr : '—' }} / {{ $combo->sale_price_usd ? '$'.$combo->sale_price_usd : '—' }}</td>
                    <td><x-admin.badge :status="$combo->sale_live && $switchOn ? 'paid' : 'disabled'">{{ $combo->sale_live && $switchOn ? 'Live' : 'Not live' }}</x-admin.badge></td>
                    <td class="text-right"><a href="{{ route('reddy.combos.edit', $combo) }}" class="btn btn-ghost btn-sm">Edit</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <x-admin.empty-state title="No combo sales" hint="Set a sale price on any combo to feature it here." />
    @endif
</div>
@endsection
