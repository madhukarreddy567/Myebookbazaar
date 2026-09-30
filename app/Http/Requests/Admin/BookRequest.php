<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the `admin` middleware.
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('books', 'slug')->ignore($bookId)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'edition' => ['nullable', 'string', 'max:50'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'language' => ['nullable', 'string', 'max:50'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:65000'],
            'price_inr' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'sale_price_inr' => ['nullable', 'numeric', 'min:0', 'lt:price_inr'],
            'sale_price_usd' => ['nullable', 'numeric', 'min:0', 'lt:price_usd'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:50'],
            'preview_enabled' => ['nullable', 'boolean'],
            'preview_pages' => ['nullable', 'integer', 'min:1', 'max:50'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_cover' => ['nullable', 'boolean'],
            // Ebook PDFs up to 200 MB. The Docker image must allow this too
            // (Dockerfile sets upload_max_filesize=200M / post_max_size=210M);
            // otherwise PHP rejects the upload before Laravel ever sees it.
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:204800'],
            'remove_pdf' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price_inr.lt' => 'The INR sale price must be lower than the regular INR price.',
            'sale_price_usd.lt' => 'The USD sale price must be lower than the regular USD price.',
            'pdf.max' => 'The PDF must not be larger than 200 MB.',
        ];
    }
}
