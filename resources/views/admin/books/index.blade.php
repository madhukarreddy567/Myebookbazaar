@extends('layouts.admin')

@section('title', 'Books')
@section('page_title', 'Books')
@section('breadcrumb', 'Store / Books')

@section('content')
<x-admin.page-header title="Books" subtitle="Your ebook catalogue — prices, sale windows, covers and PDFs.">
    <a href="{{ route('reddy.books.create') }}" class="btn btn-primary">
        <x-admin.icon name="book" class="w-4 h-4" /> Add book
    </a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-danger)">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('reddy.books.index') }}" class="surface p-4 mb-4 flex flex-col md:flex-row gap-3 md:items-end">
    <div class="flex-1">
        <label for="q" class="field-label">Search title</label>
        <input id="q" name="q" type="search" value="{{ request('q') }}" class="input" autocomplete="off">
    </div>
    <div class="md:w-60">
        <label for="category_id" class="field-label">Category</label>
        <select id="category_id" name="category_id" class="input">
            <option value="">All categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-secondary">Apply</button>
</form>

<div class="surface overflow-hidden">
    @if($books->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Book</th>
                    <th scope="col">Category</th>
                    <th scope="col" class="text-right">INR</th>
                    <th scope="col" class="text-right">USD</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($books as $book)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($book->coverUrl())
                                <img src="{{ $book->coverUrl() }}" alt="" class="w-10 h-14 object-cover rounded-md shrink-0">
                            @else
                                <span class="w-10 h-14 rounded-md shrink-0 flex items-center justify-center text-xs font-bold"
                                      style="background: var(--color-teal-900); color: #fff">No cover</span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold truncate">{{ $book->title }}</p>
                                <p class="text-xs text-[var(--color-muted)] truncate">/{{ $book->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td>{{ $book->category?->name ?? '—' }}</td>
                    <td class="num">
                        ₹{{ number_format((float) $book->price_inr, 0) }}
                        @if($book->sale_price_inr)<br><span class="text-xs" style="color: var(--color-success)">Sale ₹{{ number_format((float) $book->sale_price_inr, 0) }}</span>@endif
                    </td>
                    <td class="num">
                        ${{ number_format((float) $book->price_usd, 2) }}
                        @if($book->sale_price_usd)<br><span class="text-xs" style="color: var(--color-success)">Sale ${{ number_format((float) $book->sale_price_usd, 2) }}</span>@endif
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            <x-admin.badge :status="$book->is_active ? 'active' : 'disabled'">{{ $book->is_active ? 'Active' : 'Disabled' }}</x-admin.badge>
                            @if($book->is_featured)<x-admin.badge status="pending">Featured</x-admin.badge>@endif
                            @if($book->hasFile())<x-admin.badge status="completed">PDF</x-admin.badge>@endif
                        </div>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.books.edit', $book) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form id="del-book-{{ $book->id }}" method="POST" action="{{ route('reddy.books.destroy', $book) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="window.confirmAction({title: 'Delete book', message: 'Delete “{{ addslashes($book->title) }}”? Its cover and PDF are removed too. This cannot be undone.', confirmLabel: 'Delete book', requireText: 'DELETE', formId: 'del-book-{{ $book->id }}'})">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $books->links() }}</div>
    @else
    <x-admin.empty-state title="No books yet" hint="Add your first ebook to start selling."
        action="Add book" :href="route('reddy.books.create')" icon="book" />
    @endif
</div>
@endsection
