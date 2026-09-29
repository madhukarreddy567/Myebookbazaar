{{-- Alert banner: info, success, warning, danger.
     Usage: <x-ui.alert type="success">Saved.</x-ui.alert> --}}
@props(['type' => 'info', 'dismissible' => false])

@php
$icons = ['info' => 'info', 'success' => 'check-circle', 'warning' => 'alert', 'danger' => 'alert'];
@endphp

<div class="alert alert-{{ $type }}" role="alert"
     @if($dismissible) x-data="{ show: true }" x-show="show" x-cloak @endif>
    <x-ui.icon :name="$icons[$type] ?? 'info'" class="w-5 h-5" />
    <div class="flex-1">{{ $slot }}</div>
    @if($dismissible)
        <button type="button" @click="show = false" aria-label="Dismiss"
                class="text-current opacity-70 hover:opacity-100">
            <x-ui.icon name="x" class="w-5 h-5" />
        </button>
    @endif
</div>
