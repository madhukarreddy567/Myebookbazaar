<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Throttle key: per IP + email, as required by the spec.
     */
    public function throttleKey(): string
    {
        return 'admin-login:'.Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
