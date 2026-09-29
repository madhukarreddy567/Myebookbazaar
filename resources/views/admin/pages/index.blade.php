@extends('layouts.admin')

@section('title', 'Content pages')
@section('page_title', 'Content pages')
@section('breadcrumb', 'Store / Pages')

@section('content')
<x-admin.page-header title="Content pages" subtitle="Admin-editable pages like About and FAQ. Legal pages live in Settings Center instead.">
    <a href="{{ route('reddy.pages.create') }}" class="btn btn-primary">Add page</a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<div class="surface overflow-hidden">
    @if($pages->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Slug</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                <tr>
                    <td class="font-semibold">{{ $page->title }}</td>
                    <td class="text-sm text-[var(--color-muted)]">/{{ $page->slug }}</td>
                    <td><x-admin.badge :status="$page->is_active ? 'active' : 'disabled'">{{ $page->is_active ? 'Active' : 'Disabled' }}</x-admin.badge></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.pages.edit', $page) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form id="del-page-{{ $page->id }}" method="POST" action="{{ route('reddy.pages.destroy', $page) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="window.confirmAction({title: 'Delete page', message: 'Delete “{{ addslashes($page->title) }}”? This cannot be undone.', confirmLabel: 'Delete page', requireText: 'DELETE', formId: 'del-page-{{ $page->id }}'})">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $pages->links() }}</div>
    @else
    <x-admin.empty-state title="No pages yet" hint="Create an About or FAQ page."
        action="Add page" :href="route('reddy.pages.create')" icon="list" />
    @endif
</div>
@endsection
