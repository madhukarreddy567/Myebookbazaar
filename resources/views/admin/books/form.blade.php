@extends('layouts.admin')

@section('title', $book->exists ? 'Edit book' : 'Add book')
@section('page_title', $book->exists ? 'Edit book' : 'Add book')
@section('breadcrumb', 'Store / Books / '.($book->exists ? 'Edit' : 'Add'))

@section('content')
<x-admin.page-header :title="$book->exists ? 'Edit book' : 'Add book'"
    subtitle="Prices are stored for both regions — the store picks the right one per visitor." />

<div class="surface section-card max-w-3xl">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $book->exists ? route('reddy.books.update', $book) : route('reddy.books.store') }}"
          class="space-y-8">
        @csrf
        @if($book->exists) @method('PUT') @endif

        {{-- Details --}}
        <section>
            <h3 class="font-display text-xl font-bold mb-1">Details</h3>
            <p class="text-sm text-[var(--color-muted)] mb-4">What the customer sees on the book page.</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="field-label">Title</label>
                    <input id="title" name="title" type="text" required maxlength="255"
                           value="{{ old('title', $book->title) }}" class="input @error('title') input-error @enderror">
                    @error('title')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="slug" class="field-label">URL slug</label>
                    <input id="slug" name="slug" type="text" maxlength="255" placeholder="auto from title"
                           value="{{ old('slug', $book->slug) }}" class="input @error('slug') input-error @enderror">
                    @error('slug')<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4 shrink-0" />{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category_id" class="field-label">Category</label>
                    <select id="category_id" name="category_id" class="input">
                        <option value="">No category</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) old('category_id', $book->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="subtitle" class="field-label">Subtitle</label>
                    <input id="subtitle" name="subtitle" type="text" maxlength="255"
                           value="{{ old('subtitle', $book->subtitle) }}" class="input">
                </div>
                <div>
                    <label for="author" class="field-label">Author</label>
                    <input id="author" name="author" type="text" maxlength="255"
                           value="{{ old('author', $book->author) }}" class="input">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="edition" class="field-label">Edition</label>
                        <input id="edition" name="edition" type="text" maxlength="50"
                               value="{{ old('edition', $book->edition) }}" class="input">
                    </div>
                    <div>
                        <label for="pages" class="field-label">Pages</label>
                        <input id="pages" name="pages" type="number" min="1"
                               value="{{ old('pages', $book->pages) }}" class="input @error('pages') input-error @enderror">
                        @error('pages')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="language" class="field-label">Language</label>
                        <input id="language" name="language" type="text" maxlength="50"
                               value="{{ old('language', $book->language ?? 'English') }}" class="input">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="short_description" class="field-label">Short description</label>
                    <input id="short_description" name="short_description" type="text" maxlength="500"
                           value="{{ old('short_description', $book->short_description) }}" class="input">
                    <p class="field-help">One line shown on cards and listings.</p>
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="field-label">Full description</label>
                    <textarea id="description" name="description" rows="6" class="input">{{ old('description', $book->description) }}</textarea>
                </div>
                <div>
                    <label for="isbn" class="field-label">ISBN</label>
                    <input id="isbn" name="isbn" type="text" maxlength="50" value="{{ old('isbn', $book->isbn) }}" class="input">
                </div>
                <div>
                    <label for="published_at" class="field-label">Published on</label>
                    <input id="published_at" name="published_at" type="date"
                           value="{{ old('published_at', $book->published_at?->format('Y-m-d')) }}" class="input">
                </div>
            </div>
        </section>

        {{-- Pricing --}}
        <section>
            <h3 class="font-display text-xl font-bold mb-1">Pricing</h3>
            <p class="text-sm text-[var(--color-muted)] mb-4">Both regions are required. Indian customers pay the INR price, everyone else the USD price.</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="price_inr" class="field-label">Price (INR ₹)</label>
                    <input id="price_inr" name="price_inr" type="number" step="0.01" min="0" required
                           value="{{ old('price_inr', $book->price_inr) }}" class="input @error('price_inr') input-error @enderror">
                    @error('price_inr')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="price_usd" class="field-label">Price (USD $)</label>
                    <input id="price_usd" name="price_usd" type="number" step="0.01" min="0" required
                           value="{{ old('price_usd', $book->price_usd) }}" class="input @error('price_usd') input-error @enderror">
                    @error('price_usd')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sale_price_inr" class="field-label">Sale price (INR ₹)</label>
                    <input id="sale_price_inr" name="sale_price_inr" type="number" step="0.01" min="0"
                           value="{{ old('sale_price_inr', $book->sale_price_inr) }}" class="input @error('sale_price_inr') input-error @enderror">
                    @error('sale_price_inr')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sale_price_usd" class="field-label">Sale price (USD $)</label>
                    <input id="sale_price_usd" name="sale_price_usd" type="number" step="0.01" min="0"
                           value="{{ old('sale_price_usd', $book->sale_price_usd) }}" class="input @error('sale_price_usd') input-error @enderror">
                    @error('sale_price_usd')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sale_starts_at" class="field-label">Sale starts</label>
                    <input id="sale_starts_at" name="sale_starts_at" type="datetime-local"
                           value="{{ old('sale_starts_at', $book->sale_starts_at?->format('Y-m-d\TH:i')) }}" class="input">
                </div>
                <div>
                    <label for="sale_ends_at" class="field-label">Sale ends</label>
                    <input id="sale_ends_at" name="sale_ends_at" type="datetime-local"
                           value="{{ old('sale_ends_at', $book->sale_ends_at?->format('Y-m-d\TH:i')) }}" class="input @error('sale_ends_at') input-error @enderror">
                    @error('sale_ends_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="field-help mt-2">Sale prices apply only inside the date window and only while the “Sales and offers” switch is on. Times are UTC.</p>
        </section>

        {{-- Files --}}
        <section>
            <h3 class="font-display text-xl font-bold mb-1">Files</h3>
            <p class="text-sm text-[var(--color-muted)] mb-4">Covers are public images. PDFs stay in private storage — never reachable by URL.</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="cover" class="field-label">Cover image</label>
                    @if($book->coverUrl())
                        <img src="{{ $book->coverUrl() }}" alt="" class="w-24 h-auto rounded-lg mb-2">
                        <label class="flex items-center gap-2 text-sm mb-2">
                            <input type="checkbox" name="remove_cover" value="1" class="w-4 h-4"> Remove current cover
                        </label>
                    @endif
                    <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="input">
                    <p class="field-help">JPG, PNG or WebP, max 2 MB.</p>
                    @error('cover')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pdf" class="field-label">Ebook PDF</label>
                    @if($book->hasFile())
                        <p class="text-sm mb-2">Current file: <span class="font-mono text-xs">{{ basename($book->file_path) }}</span>
                        @if($book->file_size) ({{ number_format($book->file_size / 1048576, 1) }} MB) @endif</p>
                        <label class="flex items-center gap-2 text-sm mb-2">
                            <input type="checkbox" name="remove_pdf" value="1" class="w-4 h-4"> Remove current PDF
                        </label>
                    @endif
                    <input id="pdf" name="pdf" type="file" accept="application/pdf" class="input">
                    <p class="field-help">PDF, max 50 MB. Stored privately.</p>
                    @error('pdf')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Visibility --}}
        <section>
            <h3 class="font-display text-xl font-bold mb-1">Visibility</h3>
            <div class="grid md:grid-cols-3 gap-4 mt-4">
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $book->is_active)) class="w-4 h-4"> Active
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $book->is_featured)) class="w-4 h-4"> Featured on home
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="preview_enabled" value="1" @checked(old('preview_enabled', $book->preview_enabled ?? true)) class="w-4 h-4"> Enable preview
                </label>
                <div>
                    <label for="sku" class="field-label">SKU (auto-generated if blank)</label>
                    <input id="sku" name="sku" type="text" maxlength="50"
                           value="{{ old('sku', $book->sku) }}" class="input"
                           placeholder="e.g. FIT-001-E1">
                </div>
                <div>
                    <label for="preview_pages" class="field-label">Preview pages (max)</label>
                    <input id="preview_pages" name="preview_pages" type="number" min="1" max="50"
                           value="{{ old('preview_pages', $book->preview_pages ?? 5) }}" class="input">
                </div>
                <div>
                    <label for="sort_order" class="field-label">Sort order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0"
                           value="{{ old('sort_order', $book->sort_order ?? 0) }}" class="input">
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3 border-t border-[var(--color-border)] pt-5">
            <a href="{{ route('reddy.books.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $book->exists ? 'Save changes' : 'Create book' }}</button>
        </div>
    </form>
</div>
@endsection
