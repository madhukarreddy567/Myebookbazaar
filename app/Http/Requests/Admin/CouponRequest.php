<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 4: coupon validation. Codes are stored uppercase; percent
 * discounts cannot exceed 100.
 */
class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => mb_strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                $coupon ? Rule::unique('coupons', 'code')->ignore($coupon) : 'unique:coupons,code',
            ],
            'type' => ['required', 'in:percent,flat'],
            'value' => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'percent' && $value > 100) {
                        $fail('A percent discount cannot be more than 100.');
                    }
                },
            ],
            'currency' => [
                'required',
                'in:INR,USD,BOTH',
                function ($attribute, $value, $fail) {
                    // A flat amount is denominated in a currency — with
                    // BOTH it is ambiguous (₹100 off a $4.99 order would
                    // make it free), so flat coupons must pick INR or USD.
                    if ($this->input('type') === 'flat' && $value === 'BOTH') {
                        $fail('A flat-amount coupon must be in INR or USD, not both.');
                    }
                },
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'max_uses_per_user' => ['required', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'applies_to' => ['required', 'in:all,books,categories'],
            'applies_book_ids' => ['nullable', 'array', 'required_if:applies_to,books'],
            'applies_book_ids.*' => ['integer', 'exists:books,id'],
            'applies_category_ids' => ['nullable', 'array', 'required_if:applies_to,categories'],
            'applies_category_ids.*' => ['integer', 'exists:categories,id'],
            'first_order_only' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code may only contain letters, numbers, dashes and underscores.',
            'applies_book_ids.required_if' => 'Choose at least one book this coupon applies to.',
            'applies_category_ids.required_if' => 'Choose at least one category this coupon applies to.',
        ];
    }
}
