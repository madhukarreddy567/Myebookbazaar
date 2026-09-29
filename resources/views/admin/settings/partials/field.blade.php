{{--
  Settings Center field renderer (Phase 2A).
  Expects $fv = ['field' => [...], 'value' => mixed, 'masked' => ?string, 'has_value' => bool].
--}}
@php
$field = $fv['field'];
$key = $field['key'];
$flat = str_replace('.', '_', $key);
$inputName = 's['.str_replace('.', '][', $key).']';
$oldKey = 's.'.str_replace('.', '.', $key);
$errKey = str_replace('.', '_', $oldKey);
$hasError = $errors->has($oldKey) || $errors->has($errKey);
@endphp

<div class="py-4 {{ $field['type'] === 'boolean' ? 'flex items-start gap-4' : '' }}">
    @if($field['type'] === 'boolean')
        <label class="switch mt-0.5" for="fld-{{ $flat }}">
            <input type="checkbox" id="fld-{{ $flat }}" name="{{ $inputName }}" value="1"
                   @checked(old($oldKey, $fv['value']))>
            <span class="track" aria-hidden="true"></span>
        </label>
        <div class="min-w-0">
            <label for="fld-{{ $flat }}" class="block text-sm font-semibold text-[var(--color-text)] cursor-pointer">{{ $field['label'] }}</label>
            @if(!empty($field['help']))
                <p class="mt-0.5 text-xs text-[var(--color-muted)]">{{ $field['help'] }}</p>
            @endif
        </div>
    @else
        <label for="fld-{{ $flat }}" class="block text-sm font-semibold text-[var(--color-text)] mb-1.5">
            {{ $field['label'] }}
            @if(!empty($field['required']))<span class="text-[var(--color-danger)]" aria-hidden="true">*</span>@endif
        </label>

        @if($field['type'] === 'secret')
            <div x-data="{ changing: false }">
                @if($fv['masked'])
                    <div class="flex flex-wrap items-center gap-3" x-show="!changing">
                        <span class="input font-mono !w-auto flex-1 min-w-[200px] flex items-center gap-2" aria-label="Current value (masked)">
                            <x-admin.icon name="lock" class="w-4 h-4 text-[var(--color-muted)]" />
                            {{ $fv['masked'] }}
                        </span>
                        <button type="button" class="btn btn-secondary btn-sm" @click="changing = true">Change</button>
                    </div>
                    <div x-show="changing" x-cloak>
                        <input type="password" id="fld-{{ $flat }}" name="secret_{{ $flat }}" class="input font-mono"
                               placeholder="Type a new value — the saved one is never shown" autocomplete="new-password">
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="btn btn-ghost btn-sm" @click="changing = false">Cancel</button>
                        </div>
                        <p class="mt-1.5 text-xs text-[var(--color-muted)]">Leave empty to keep the current value. It is stored encrypted.</p>
                    </div>
                @else
                    <input type="password" id="fld-{{ $flat }}" name="secret_{{ $flat }}" class="input font-mono"
                           placeholder="Not set — type a value" autocomplete="new-password">
                    <p class="mt-1.5 text-xs text-[var(--color-muted)]">Stored encrypted. Only the last 4 characters are ever shown.</p>
                @endif
                @error('secret_'.$flat)<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4" />{{ $message }}</p>@enderror
            </div>
        @elseif($field['type'] === 'image')
            <div>
                @if($fv['has_value'])
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ asset($fv['value']) }}" alt="{{ $field['label'] }} preview"
                             class="h-16 w-auto rounded-lg border border-[var(--color-border)] bg-white p-1">
                        <label class="flex items-center gap-2 text-sm text-[var(--color-muted)] cursor-pointer">
                            <input type="checkbox" name="remove_img_{{ $flat }}" value="1" class="h-4 w-4 accent-[var(--color-danger)]">
                            Remove current image
                        </label>
                    </div>
                @endif
                <input type="file" id="fld-{{ $flat }}" name="img_{{ $flat }}" accept="image/*" class="input !py-2.5">
                @error('img_'.$flat)<p class="field-error"><x-admin.icon name="alert" class="w-4 h-4" />{{ $message }}</p>@enderror
            </div>
        @elseif($field['type'] === 'select')
            <select id="fld-{{ $flat }}" name="{{ $inputName }}" class="input max-w-md">
                @foreach($field['options'] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) old($oldKey, $fv['value']) === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        @elseif($field['type'] === 'textarea')
            <textarea id="fld-{{ $flat }}" name="{{ $inputName }}" rows="4"
                      class="input max-w-3xl {{ $hasError ? 'input-error' : '' }}">{{ old($oldKey, $fv['value']) }}</textarea>
        @elseif($field['type'] === 'number')
            <input type="number" id="fld-{{ $flat }}" name="{{ $inputName }}" min="0"
                   value="{{ old($oldKey, $fv['value']) }}" class="input max-w-xs {{ $hasError ? 'input-error' : '' }}">
        @else
            <input type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}" id="fld-{{ $flat }}" name="{{ $inputName }}"
                   value="{{ old($oldKey, $fv['value']) }}" class="input max-w-xl {{ $hasError ? 'input-error' : '' }}"
                   @if($field['type'] === 'url') placeholder="https://…" @endif>
        @endif

        @if(!empty($field['help']) && $field['type'] !== 'secret')
            <p class="mt-1.5 text-xs text-[var(--color-muted)]">{{ $field['help'] }}</p>
        @endif
        @if($hasError)
            <p class="field-error"><x-admin.icon name="alert" class="w-4 h-4" />{{ $errors->first($oldKey) ?: $errors->first($errKey) }}</p>
        @endif
    @endif
</div>
