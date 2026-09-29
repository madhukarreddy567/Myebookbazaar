{{-- Button: Primary (saffron), Secondary (teal outline), Ghost, Danger.
     Sizes: sm, md, lg (min 44px touch target on md/lg).
     Usage: <x-ui.button variant="primary" size="md" href="...">Browse ebooks</x-ui.button>
            <x-ui.button type="submit" loading="saving">Pay now</x-ui.button> --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'icon' => null,
])

@php
$classes = 'btn btn-' . $variant . ' btn-' . $size . ($loading ? ' btn-loading' : '');
$tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($loading) aria-busy="true" disabled @endif
>
    @if($loading)
        <span class="btn-spinner" aria-hidden="true"></span>
        <span>{{ $slot }}</span>
    @else
        @if($icon)<x-ui.icon :name="$icon" class="w-5 h-5" />@endif
        <span>{{ $slot }}</span>
    @endif
</{{ $tag }}>
