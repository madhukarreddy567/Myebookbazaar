<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Phase 4: combo validation. A combo needs at least two books and a
 * positive price in both currencies.
 */
class ComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        if ($slug === '' && $this->filled('title')) {
            $slug = Str::slug($this->input('title'));
        }

        $this->merge(['slug' => $slug]);
    }

    public function rules(): array
    {
        $combo = $this->route('combo');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                $combo ? Rule::unique('combos', 'slug')->ignore($combo) : 'unique:combos,slug',
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'book_ids' => ['required', 'array', 'min:2'],
            'book_ids.*' => ['integer', 'exists:books,id'],
            'combo_price_inr' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'combo_price_usd' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'sale_price_inr' => ['nullable', 'numeric', 'min:0.01', 'lt:combo_price_inr'],
            'sale_price_usd' => ['nullable', 'numeric', 'min:0.01', 'lt:combo_price_usd'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_ids.min' => 'A combo needs at least two books.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and dashes.',
        ];
    }
}
