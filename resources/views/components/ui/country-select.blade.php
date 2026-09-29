{{-- Searchable country select: type to filter, keyboard friendly
     (ArrowUp/ArrowDown + Enter + Escape). Sets a hidden input.
     Usage: <x-ui.country-select name="country_code" :countries="$countries"
               label="Country" required :value="old('country_code')"
               hint="Your country sets your price and cannot be changed later."
               :error="$errors->first('country_code')" /> --}}
@props([
    'name' => 'country_code',
    'countries' => [],
    'label' => 'Country',
    'required' => false,
    'value' => '',
    'hint' => null,
    'error' => null,
    'placeholder' => 'Type to search countries…',
    'icon' => null,
    'iconClass' => 'auth-input-icon',
])

@php
$list = collect($countries)->map(fn($c) => [
    'code' => is_array($c) ? $c['code'] : $c->iso_code,
    'name' => is_array($c) ? $c['name'] : $c->name,
    'phone' => is_array($c) ? ($c['phone'] ?? '') : ($c->phone_code ?? ''),
])->values();
@endphp

<div class="field" x-data="countrySelect(@js($list), @js((string) $value))">
    @if($label)
        <label for="{{ $name }}_search" class="field-label">
            {{ $label }} @if($required)<span class="field-required" aria-hidden="true">*</span>@endif
        </label>
    @endif
    <div class="relative">
        @if($icon)<x-ui.icon :name="$icon" class="{{ $iconClass }}" />@endif
        <input id="{{ $name }}_search" type="text" autocomplete="off"
               placeholder="{{ $placeholder }}"
               x-model="query" @focus="open = true" @input="open = true; highlight = 0"
               @keydown.escape="open = false"
               @keydown.arrow-down.prevent="move(1)"
               @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter.prevent="chooseHighlighted()"
               role="combobox" aria-expanded="false" :aria-expanded="open"
               aria-controls="{{ $name }}_list" aria-autocomplete="list"
               {{ $attributes->merge(['class' => 'field-input']) }}
               @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
        <input type="hidden" name="{{ $name }}" :value="selected">
        <div x-show="open" @click.away="open = false" x-cloak
             class="country-list" role="listbox" id="{{ $name }}_list">
            <template x-for="(c, i) in filtered()" :key="c.code">
                <button type="button" role="option" :aria-selected="c.code === selected"
                        @click="choose(c)" @mouseenter="highlight = i"
                        class="country-option" :class="{ 'active': i === highlight }">
                    <span x-text="c.name"></span>
                    <span class="phone" x-text="c.phone"></span>
                </button>
            </template>
            <p x-show="filtered().length === 0" class="px-4 py-3 text-sm text-[var(--color-muted)]">No country found.</p>
        </div>
    </div>
    @if($hint)<p class="field-hint">{{ $hint }}</p>@endif
    @if($error)
        <p class="field-error" id="{{ $name }}-error" role="alert">
            <x-ui.icon name="alert" class="w-4 h-4" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>

@once
<script>
function countrySelect(countries, initial) {
    return {
        query: '',
        open: false,
        selected: initial || '',
        highlight: 0,
        init() {
            const found = countries.find(c => c.code === this.selected);
            if (found) this.query = found.name;
        },
        filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return countries.slice(0, 50);
            return countries.filter(c =>
                c.name.toLowerCase().includes(q) || c.code.toLowerCase() === q
            ).slice(0, 50);
        },
        move(dir) {
            const n = this.filtered().length;
            if (!n) return;
            this.open = true;
            this.highlight = (this.highlight + dir + n) % n;
            this.$nextTick(() => {
                const el = this.$el.querySelectorAll('.country-option')[this.highlight];
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },
        chooseHighlighted() {
            const c = this.filtered()[this.highlight];
            if (c) this.choose(c);
        },
        choose(c) {
            this.selected = c.code;
            this.query = c.name;
            this.open = false;
        }
    };
}
</script>
@endonce
