@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'Add category')
@section('page_title', $category->exists ? 'Edit category' : 'Add category')
@section('breadcrumb', 'Store / Categories / '.($category->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$category->exists ? 'Edit category' : 'Add category'" subtitle="" />

<div class="surface section-card max-w-xl">
    <form method="POST" action="{{ $category->exists ? route('reddy.categories.update', $category) : route('reddy.categories.store') }}" class="space-y-5">
        @csrf
        @if($category->exists) @method('PUT') @endif

        <div>
            <label for="name" class="field-label">Name</label>
            <input id="name" name="name" type="text" required maxlength="255"
                   value="{{ old('name', $category->name) }}" class="input @error('name') input-error @enderror">
            @error('name')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="field-label">URL slug</label>
            <input id="slug" name="slug" type="text" maxlength="255" placeholder="auto from name"
                   value="{{ old('slug', $category->slug) }}" class="input @error('slug') input-error @enderror">
            @error('slug')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="field-label">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="2000" class="input">{{ old('description', $category->description) }}</textarea>
        </div>
        <div>
            <label for="sort_order" class="field-label">Sort order</label>
            <input id="sort_order" name="sort_order" type="number" min="0"
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="w-4 h-4"> Active
        </label>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.categories.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $category->exists ? 'Save changes' : 'Create category' }}</button>
        </div>
    </form>
</div>
@endsection
