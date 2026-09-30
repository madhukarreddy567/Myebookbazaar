<?php

namespace App\Http\Requests\Admin;

use App\Settings\Definition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the `super_admin` middleware.
        return true;
    }

    public function rules(): array
    {
        $tab = $this->input('tab', 'general');
        $rules = [
            'tab' => ['required', 'string', 'in:'.implode(',', array_keys(Definition::tabs()))],
        ];

        foreach (Definition::fields($tab) as $field) {
            $formKey = 's.'.$field['key'];
            $rules[$formKey] = match ($field['type']) {
                'text' => [($field['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:255'],
                'email' => [($field['required'] ?? false) ? 'required' : 'nullable', 'email', 'max:255'],
                'url' => [($field['required'] ?? false) ? 'required' : 'nullable', 'url', 'max:2000'],
                'number' => [($field['required'] ?? false) ? 'required' : 'nullable', 'integer', 'min:0', 'max:1000000'],
                'percent' => [($field['required'] ?? false) ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
                'boolean' => ['nullable'],
                'secret' => ['nullable', 'string', 'max:2000'],
                'select' => ['nullable', 'string', 'in:'.implode(',', array_keys($field['options'] ?? []))],
                'textarea' => ['nullable', 'string', 'max:65000'],
                'image' => ['nullable', 'image', 'max:2048'],
                default => ['nullable'],
            };
        }

        // New secret values arrive as secret_<flat_key>; only validated when filled.
        // Uploaded images arrive as img_<flat_key>.
        foreach (Definition::fields($tab) as $field) {
            $flat = str_replace('.', '_', $field['key']);
            if ($field['type'] === 'secret') {
                $rules['secret_'.$flat] = ['nullable', 'string', 'max:2000'];
            }
            if ($field['type'] === 'image') {
                // A field may declare its own mime list (e.g. the favicon
                // allows .ico, which Laravel's `image` rule does not).
                $imageRules = ['nullable', 'max:2048'];
                $imageRules[] = ! empty($field['mimes']) ? 'mimes:'.$field['mimes'] : 'image';
                $rules['img_'.$flat] = $imageRules;
                $rules['remove_img_'.$flat] = ['nullable'];
            }
        }

        // Country region overrides: regions[US] = IN|INTL.
        $rules['regions'] = ['nullable', 'array'];
        $rules['regions.*'] = ['nullable', 'in:IN,INTL'];

        return $rules;
    }

    /**
     * Regular (non-secret, non-image) values for the current tab,
     * flattened to setting keys.
     */
    public function settingValues(): array
    {
        $nested = $this->input('s', []);

        return Arr::dot($nested);
    }
}
