{{-- Card wrapper. Usage: <x-ui.card class="p-6">...</x-ui.card> --}}
<div {{ $attributes->merge(['class' => 'card']) }}>
    {{ $slot }}
</div>
