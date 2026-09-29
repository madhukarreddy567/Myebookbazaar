@extends('layouts.admin')

@section('title', 'Banners')
@section('page_title', 'Banners')
@section('breadcrumb', 'Store / Offers and banners')

@section('content')
<x-admin.page-header title="Offers and banners" subtitle="Homepage banners shown under the featured books.">
    <a href="{{ route('reddy.banners.create') }}" class="btn btn-primary">Add banner</a>
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif

<div class="surface overflow-hidden">
    @if($banners->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Banner</th>
                    <th scope="col">Link</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($banners as $banner)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $banner->imageUrl() }}" alt="" class="w-24 h-auto rounded-md shrink-0">
                            <p class="font-semibold">{{ $banner->title }}</p>
                        </div>
                    </td>
                    <td class="text-sm text-[var(--color-muted)] truncate max-w-xs">{{ $banner->link_url ?? '—' }}</td>
                    <td><x-admin.badge :status="$banner->is_active ? 'active' : 'disabled'">{{ $banner->is_active ? 'Active' : 'Disabled' }}</x-admin.badge></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.banners.edit', $banner) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form id="del-banner-{{ $banner->id }}" method="POST" action="{{ route('reddy.banners.destroy', $banner) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="window.confirmAction({title: 'Delete banner', message: 'Delete “{{ addslashes($banner->title) }}”? This cannot be undone.', confirmLabel: 'Delete banner', requireText: 'DELETE', formId: 'del-banner-{{ $banner->id }}'})">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $banners->links() }}</div>
    @else
    <x-admin.empty-state title="No banners yet" hint="Add a banner to highlight an offer on the homepage."
        action="Add banner" :href="route('reddy.banners.create')" icon="image" />
    @endif
</div>
@endsection
