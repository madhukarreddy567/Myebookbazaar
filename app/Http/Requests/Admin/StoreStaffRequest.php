<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'max:100', Password::min(12)->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Staff passwords must be at least 12 characters.',
            'password.symbols' => 'Staff passwords must include at least one symbol (for example !, @ or #).',
        ];
    }
}
