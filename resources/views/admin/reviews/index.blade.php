@extends('layouts.admin')

@section('title', 'Reviews')
@section('page_title', 'Reviews')
@section('breadcrumb', 'Store / Reviews')

@section('content')
<x-admin.page-header title="Reviews" subtitle="Verified-purchase reviews only — hide or delete abusive ones. Every action is audit-logged." />

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

{{-- Counters --}}
<div class="grid sm:grid-cols-2 gap-4 mb-4">
    <div class="surface p-5">
        <p class="text-sm font-medium" style="color: var(--color-muted)">Total reviews</p>
        <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $totalCount }}</p>
    </div>
    <div class="surface p-5">
        <p class="text-sm font-medium" style="color: var(--color-muted)">Hidden from store</p>
        <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $hiddenCount }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reddy.reviews.index') }}" class="surface p-4 mb-4 grid md:grid-cols-4 gap-3 md:items-end">
    <div>
        <label for="q" class="field-label">Review text, book or customer</label>
        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" autocomplete="off">
    </div>
    <div>
        <label for="stars" class="field-label">Stars</label>
        <select id="stars" name="stars" class="input">
            <option value="">All</option>
            @foreach([5, 4, 3, 2, 1] as $s)
            <option value="{{ $s }}" @selected(($filters['stars'] ?? '') == (string) $s)>{{ $s }} stars</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="visibility" class="field-label">Visibility</label>
        <select id="visibility" name="visibility" class="input">
            <option value="">All</option>
            <option value="visible" @selected(($filters['visibility'] ?? '') === 'visible')>Visible</option>
            <option value="hidden" @selected(($filters['visibility'] ?? '') === 'hidden')>Hidden</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn btn-secondary">Apply</button>
        <a href="{{ route('reddy.reviews.index') }}" class="btn btn-ghost">Clear</a>
    </div>
</form>

<div class="surface overflow-hidden">
    @if($ratings->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Review</th>
                    <th scope="col">Book</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($ratings as $rating)
                <tr>
                    <td>
                        <div style="color: #B45309" role="img" aria-label="{{ $rating->stars }} out of 5 stars">{{ str_repeat('★', $rating->stars) }}{{ str_repeat('☆', 5 - $rating->stars) }}</div>
                        @if($rating->review)
                        <p class="text-sm mt-1">{{ \Illuminate\Support\Str::limit($rating->review, 160) }}</p>
                        @else
                        <p class="text-sm mt-1" style="color: var(--color-muted)">No written review.</p>
                        @endif
                    </td>
                    <td>{{ $rating->book->title ?? '—' }}</td>
                    <td>{{ $rating->user->name ?? '—' }}<br><span class="text-xs" style="color: var(--color-muted)">{{ $rating->user->email ?? '' }}</span></td>
                    <td>
                        <x-admin.badge :status="$rating->is_visible ? 'active' : 'disabled'">{{ $rating->is_visible ? 'Visible' : 'Hidden' }}</x-admin.badge>
                    </td>
                    <td class="whitespace-nowrap">{{ $rating->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('reddy.reviews.toggle', $rating) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">{{ $rating->is_visible ? 'Hide' : 'Unhide' }}</button>
                        </form>
                        <form id="del-rating-{{ $rating->id }}" method="POST" action="{{ route('reddy.reviews.destroy', $rating) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="window.confirmAction({title: 'Delete review', message: 'Permanently delete this {{ $rating->stars }}-star review? This cannot be undone.', confirmLabel: 'Delete review', requireText: 'DELETE', formId: 'del-rating-{{ $rating->id }}'})">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $ratings->links() }}</div>
    @else
    <x-admin.empty-state title="No reviews yet" hint="Reviews appear here once verified buyers start rating books." icon="list" />
    @endif
</div>
@endsection
