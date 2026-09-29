<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // not_regex: defense-in-depth against the CRLF email-rule
            // class (GHSA-5vg9-5847-vvmq) — the address is used in
            // outgoing mail headers.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'not_regex:/[\r\n]/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,iso_code'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.mixed' => 'The password must contain both upper and lower case letters.',
        ];
    }
}
