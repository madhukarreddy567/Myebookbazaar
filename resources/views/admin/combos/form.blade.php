@extends('layouts.admin')

@section('title', $combo->exists ? 'Edit combo' : 'Add combo')
@section('page_title', $combo->exists ? 'Edit combo' : 'Add combo')
@section('breadcrumb', 'Store / Combos / '.($combo->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$combo->exists ? 'Edit combo' : 'Add combo'" subtitle="One price for the whole bundle." />

<div class="surface section-card max-w-2xl">
    <form method="POST"
          action="{{ $combo->exists ? route('reddy.combos.update', $combo) : route('reddy.combos.store') }}" class="space-y-5">
        @csrf
        @if($combo->exists) @method('PUT') @endif

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="title" class="field-label">Title</label>
                <input id="title" name="title" type="text" required maxlength="255"
                       value="{{ old('title', $combo->title) }}" class="input @error('title') input-error @enderror">
                @error('title')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="slug" class="field-label">Slug <span class="font-normal text-[var(--color-muted)]">(auto from title)</span></label>
                <input id="slug" name="slug" type="text" maxlength="255"
                       value="{{ old('slug', $combo->slug) }}" class="input @error('slug') input-error @enderror">
                @error('slug')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="description" class="field-label">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="5000"
                      class="input @error('description') input-error @enderror">{{ old('description', $combo->description) }}</textarea>
            @error('description')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>

        <div>
            <span class="field-label">Ebooks in this combo (choose at least two)</span>
            <div class="grid md:grid-cols-2 gap-2 mt-2 max-h-56 overflow-y-auto border border-[var(--color-border)] rounded-xl p-3">
                @foreach($books as $book)
                <label class="flex items-center gap-2 text-sm @if(!$book->is_active) opacity-50 @endif">
                    <input type="checkbox" name="book_ids[]" value="{{ $book->id }}"
                           @checked(in_array($book->id, old('book_ids', $combo->book_ids ?? []))) class="w-4 h-4">
                    {{ $book->title }} @if(!$book->is_active)<span class="text-xs">(inactive)</span>@endif
                </label>
                @endforeach
            </div>
            @error('book_ids')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="combo_price_inr" class="field-label">Combo price (INR ₹)</label>
                <input id="combo_price_inr" name="combo_price_inr" type="number" step="0.01" min="0.01" required
                       value="{{ old('combo_price_inr', $combo->combo_price_inr) }}" class="input @error('combo_price_inr') input-error @enderror">
                @error('combo_price_inr')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="combo_price_usd" class="field-label">Combo price (USD $)</label>
                <input id="combo_price_usd" name="combo_price_usd" type="number" step="0.01" min="0.01" required
                       value="{{ old('combo_price_usd', $combo->combo_price_usd) }}" class="input @error('combo_price_usd') input-error @enderror">
                @error('combo_price_usd')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="sale_price_inr" class="field-label">Sale price (INR) <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="sale_price_inr" name="sale_price_inr" type="number" step="0.01" min="0.01"
                       value="{{ old('sale_price_inr', $combo->sale_price_inr) }}" class="input">
                @error('sale_price_inr')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="sale_price_usd" class="field-label">Sale price (USD) <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="sale_price_usd" name="sale_price_usd" type="number" step="0.01" min="0.01"
                       value="{{ old('sale_price_usd', $combo->sale_price_usd) }}" class="input">
                @error('sale_price_usd')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="sale_starts_at" class="field-label">Sale starts <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="sale_starts_at" name="sale_starts_at" type="datetime-local"
                       value="{{ old('sale_starts_at', $combo->sale_starts_at?->format('Y-m-d\TH:i')) }}" class="input">
            </div>
            <div>
                <label for="sale_ends_at" class="field-label">Sale ends <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="sale_ends_at" name="sale_ends_at" type="datetime-local"
                       value="{{ old('sale_ends_at', $combo->sale_ends_at?->format('Y-m-d\TH:i')) }}" class="input">
                @error('sale_ends_at')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="sort_order" class="field-label">Sort order</label>
                <input id="sort_order" name="sort_order" type="number" min="0"
                       value="{{ old('sort_order', $combo->sort_order ?? 0) }}" class="input">
            </div>
            <div>
                <label for="is_active" class="field-label">Status</label>
                <label class="flex items-center gap-2 text-sm font-semibold mt-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $combo->is_active)) class="w-4 h-4"> Active
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.combos.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $combo->exists ? 'Save changes' : 'Create combo' }}</button>
        </div>
    </form>
</div>
@endsection
