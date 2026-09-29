{{-- Badge: fully round. Types: sale, info, success, warning, danger, neutral.
     Usage: <x-ui.badge type="sale">Sale</x-ui.badge> --}}
@props(['type' => 'neutral'])

<span {{ $attributes->merge(['class' => 'badge badge-' . $type]) }}>{{ $slot }}</span>
