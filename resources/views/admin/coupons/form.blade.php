@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit coupon' : 'Add coupon')
@section('page_title', $coupon->exists ? 'Edit coupon' : 'Add coupon')
@section('breadcrumb', 'Store / Coupons / '.($coupon->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$coupon->exists ? 'Edit coupon' : 'Add coupon'" subtitle="Discounts are always calculated on the server — the browser never decides a price." />

<div class="surface section-card max-w-2xl">
    <form method="POST"
          action="{{ $coupon->exists ? route('reddy.coupons.update', $coupon) : route('reddy.coupons.store') }}" class="space-y-5">
        @csrf
        @if($coupon->exists) @method('PUT') @endif

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="code" class="field-label">Code</label>
                <input id="code" name="code" type="text" required maxlength="50" placeholder="DIWALI20"
                       value="{{ old('code', $coupon->code) }}" class="input font-mono uppercase @error('code') input-error @enderror">
                <p class="field-help">Uppercase letters, numbers, dashes and underscores.</p>
                @error('code')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="is_active" class="field-label">Status</label>
                <label class="flex items-center gap-2 text-sm font-semibold mt-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active)) class="w-4 h-4"> Active
                </label>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div>
                <label for="type" class="field-label">Discount type</label>
                <select id="type" name="type" class="input @error('type') input-error @enderror">
                    <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percent (%)</option>
                    <option value="flat" @selected(old('type', $coupon->type) === 'flat')>Flat amount</option>
                </select>
                @error('type')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="value" class="field-label">Value</label>
                <input id="value" name="value" type="number" step="0.01" min="0.01" required
                       value="{{ old('value', $coupon->value) }}" class="input @error('value') input-error @enderror">
                <p class="field-help">Percent: 1–100. Flat: amount in the coupon currency.</p>
                @error('value')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="currency" class="field-label">Currency</label>
                <select id="currency" name="currency" class="input @error('currency') input-error @enderror">
                    <option value="BOTH" @selected(old('currency', $coupon->currency) === 'BOTH')>INR and USD</option>
                    <option value="INR" @selected(old('currency', $coupon->currency) === 'INR')>INR only</option>
                    <option value="USD" @selected(old('currency', $coupon->currency) === 'USD')>USD only</option>
                </select>
                <p class="field-help">INR and USD revenue are never mixed.</p>
                @error('currency')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            <div>
                <label for="min_order_amount" class="field-label">Minimum order <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="min_order_amount" name="min_order_amount" type="number" step="0.01" min="0"
                       value="{{ old('min_order_amount', $coupon->min_order_amount) }}" class="input">
                @error('min_order_amount')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="max_uses" class="field-label">Total uses <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="max_uses" name="max_uses" type="number" min="1"
                       value="{{ old('max_uses', $coupon->max_uses) }}" class="input" placeholder="Unlimited">
                @error('max_uses')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="max_uses_per_user" class="field-label">Uses per customer</label>
                <input id="max_uses_per_user" name="max_uses_per_user" type="number" min="1" required
                       value="{{ old('max_uses_per_user', $coupon->max_uses_per_user) }}" class="input">
                @error('max_uses_per_user')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="starts_at" class="field-label">Starts at <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="starts_at" name="starts_at" type="datetime-local"
                       value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="input">
                @error('starts_at')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ends_at" class="field-label">Ends at <span class="font-normal text-[var(--color-muted)]">(optional)</span></label>
                <input id="ends_at" name="ends_at" type="datetime-local"
                       value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}" class="input">
                @error('ends_at')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="applies_to" class="field-label">Applies to</label>
            <select id="applies_to" name="applies_to" class="input">
                <option value="all" @selected(old('applies_to', $coupon->applies_to) === 'all')>All ebooks</option>
                <option value="books" @selected(old('applies_to', $coupon->applies_to) === 'books')>Specific ebooks</option>
                <option value="categories" @selected(old('applies_to', $coupon->applies_to) === 'categories')>Specific categories</option>
            </select>
        </div>

        <div id="applies-books" @if(old('applies_to', $coupon->applies_to) !== 'books') class="hidden" @endif>
            <span class="field-label">Ebooks this coupon applies to</span>
            <div class="grid md:grid-cols-2 gap-2 mt-2 max-h-56 overflow-y-auto border border-[var(--color-border)] rounded-xl p-3">
                @foreach(\App\Models\Book::orderBy('title')->get(['id', 'title']) as $book)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="applies_book_ids[]" value="{{ $book->id }}"
                           @checked(in_array($book->id, old('applies_book_ids', $coupon->applies_book_ids ?? []))) class="w-4 h-4">
                    {{ $book->title }}
                </label>
                @endforeach
            </div>
            @error('applies_book_ids')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>

        <div id="applies-categories" @if(old('applies_to', $coupon->applies_to) !== 'categories') class="hidden" @endif>
            <span class="field-label">Categories this coupon applies to</span>
            <div class="grid md:grid-cols-2 gap-2 mt-2 max-h-56 overflow-y-auto border border-[var(--color-border)] rounded-xl p-3">
                @foreach(\App\Models\Category::orderBy('name')->get(['id', 'name']) as $category)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="applies_category_ids[]" value="{{ $category->id }}"
                           @checked(in_array($category->id, old('applies_category_ids', $coupon->applies_category_ids ?? []))) class="w-4 h-4">
                    {{ $category->name }}
                </label>
                @endforeach
            </div>
            @error('applies_category_ids')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="flex items-center gap-2 text-sm font-semibold">
                <input type="checkbox" name="first_order_only" value="1" @checked(old('first_order_only', $coupon->first_order_only)) class="w-4 h-4">
                First order only
            </label>
            <p class="field-help">When checked, customers who already have a paid order cannot use this coupon.</p>
            @error('first_order_only')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ $coupon->exists ? route('reddy.coupons.show', $coupon) : route('reddy.coupons.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $coupon->exists ? 'Save changes' : 'Create coupon' }}</button>
        </div>
    </form>
</div>

<script>
document.getElementById('applies_to').addEventListener('change', function () {
    document.getElementById('applies-books').classList.toggle('hidden', this.value !== 'books');
    document.getElementById('applies-categories').classList.toggle('hidden', this.value !== 'categories');
});
</script>
@endsection
