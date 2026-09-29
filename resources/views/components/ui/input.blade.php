{{-- Form input with visible label, helper text and inline icon error.
     Usage: <x-ui.input label="Email" name="email" type="email" :value="old('email')"
                required :error="$errors->first('email')" hint="We never share your email." /> --}}
@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div class="field">
    @if($label)
        <label for="{{ $attributes->get('id', $name) }}" class="field-label">
            {{ $label }} @if($required)<span class="field-required" aria-hidden="true">*</span>@endif
        </label>
    @endif
    <input
        id="{{ $attributes->get('id', $name) }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'field-input']) }}
        @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
    >
    @if($hint)<p class="field-hint">{{ $hint }}</p>@endif
    @if($error)
        <p class="field-error" id="{{ $name }}-error" role="alert">
            <x-ui.icon name="alert" class="w-4 h-4" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
