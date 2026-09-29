{{-- Breadcrumbs. Usage: <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Books']]" /> --}}
@props(['items' => []])

<nav aria-label="Breadcrumb">
    <ol class="breadcrumbs">
        @foreach($items as $item)
            <li class="flex items-center gap-2">
                @if(!$loop->first)<x-ui.icon name="chevron-down" class="w-4 h-4 -rotate-90" />@endif
                @if(!empty($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
