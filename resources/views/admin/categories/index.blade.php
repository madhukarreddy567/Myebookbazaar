@extends('layouts.admin')

@section('title', 'Categories')
@section('page_title', 'Categories')
@section('breadcrumb', 'Store / Categories')

@section('content')
<x-admin.page-header title="Categories" subtitle="Group your ebooks for browsing and filtering.">
    <a href="{{ route('reddy.categories.create') }}" class="btn btn-primary">Add category</a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-danger)">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<div class="surface overflow-hidden">
    @if($categories->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="num">Books</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td>
                        <p class="font-semibold">{{ $category->name }}</p>
                        <p class="text-xs text-[var(--color-muted)]">/{{ $category->slug }}</p>
                    </td>
                    <td class="num">{{ $category->active_books_count }}</td>
                    <td><x-admin.badge :status="$category->is_active ? 'active' : 'disabled'">{{ $category->is_active ? 'Active' : 'Disabled' }}</x-admin.badge></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.categories.edit', $category) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form id="del-cat-{{ $category->id }}" method="POST" action="{{ route('reddy.categories.destroy', $category) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="window.confirmAction({title: 'Delete category', message: 'Delete “{{ addslashes($category->name) }}”? Only empty categories can be deleted.', confirmLabel: 'Delete category', requireText: 'DELETE', formId: 'del-cat-{{ $category->id }}'})">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $categories->links() }}</div>
    @else
    <x-admin.empty-state title="No categories yet" hint="Create your first category."
        action="Add category" :href="route('reddy.categories.create')" icon="tag" />
    @endif
</div>
@endsection
