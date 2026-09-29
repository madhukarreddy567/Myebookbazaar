@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'Add page')
@section('page_title', $page->exists ? 'Edit page' : 'Add page')
@section('breadcrumb', 'Store / Pages / '.($page->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$page->exists ? 'Edit page' : 'Add page'" subtitle="" />

<div class="surface section-card max-w-2xl">
    <form method="POST" action="{{ $page->exists ? route('reddy.pages.update', $page) : route('reddy.pages.store') }}" class="space-y-5">
        @csrf
        @if($page->exists) @method('PUT') @endif

        <div>
            <label for="title" class="field-label">Title</label>
            <input id="title" name="title" type="text" required maxlength="255"
                   value="{{ old('title', $page->title) }}" class="input @error('title') input-error @enderror">
            @error('title')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="field-label">URL slug</label>
            <input id="slug" name="slug" type="text" required maxlength="255" placeholder="about"
                   value="{{ old('slug', $page->slug) }}" class="input @error('slug') input-error @enderror">
            <p class="field-help">Lowercase letters, numbers and dashes only.</p>
            @error('slug')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="content" class="field-label">Content</label>
            <textarea id="content" name="content" rows="12" required maxlength="65000" class="input font-mono text-sm">{{ old('content', $page->content) }}</textarea>
            <p class="field-help">Plain text — blank lines start new paragraphs. For the <span class="font-mono">faq</span> page: one question-and-answer per block; the first line is the question, the rest is the answer.</p>
            @error('content')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page->is_active)) class="w-4 h-4"> Active
        </label>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.pages.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $page->exists ? 'Save changes' : 'Create page' }}</button>
        </div>
    </form>
</div>
@endsection
