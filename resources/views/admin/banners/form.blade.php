@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'Add banner')
@section('page_title', $banner->exists ? 'Edit banner' : 'Add banner')
@section('breadcrumb', 'Store / Offers and banners / '.($banner->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$banner->exists ? 'Edit banner' : 'Add banner'" subtitle="" />

<div class="surface section-card max-w-xl">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $banner->exists ? route('reddy.banners.update', $banner) : route('reddy.banners.store') }}" class="space-y-5">
        @csrf
        @if($banner->exists) @method('PUT') @endif

        <div>
            <label for="title" class="field-label">Title</label>
            <input id="title" name="title" type="text" required maxlength="255"
                   value="{{ old('title', $banner->title) }}" class="input @error('title') input-error @enderror">
            @error('title')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="image" class="field-label">Image {{ $banner->exists ? '(leave empty to keep current)' : '' }}</label>
            @if($banner->exists && $banner->image)
                <img src="{{ $banner->imageUrl() }}" alt="" class="w-48 h-auto rounded-lg mb-2">
            @endif
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                   @if(!$banner->exists) required @endif class="input @error('image') input-error @enderror">
            <p class="field-help">JPG, PNG or WebP, max 2 MB. Wide banners (1200 × 400) look best.</p>
            @error('image')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="link_url" class="field-label">Link URL <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
            <input id="link_url" name="link_url" type="url" maxlength="500" placeholder="https://…"
                   value="{{ old('link_url', $banner->link_url) }}" class="input @error('link_url') input-error @enderror">
            @error('link_url')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="sort_order" class="field-label">Sort order</label>
            <input id="sort_order" name="sort_order" type="number" min="0"
                   value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="input">
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active)) class="w-4 h-4"> Active
        </label>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.banners.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $banner->exists ? 'Save changes' : 'Create banner' }}</button>
        </div>
    </form>
</div>
@endsection
