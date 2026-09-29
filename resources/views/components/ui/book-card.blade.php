{{-- Book card (Stitch style): cover on top with sale badge, condensed
     title, short line, saffron price row with struck-through old price,
     live countdown while on sale, action button.
     Usage: <x-ui.book-card title="..." description="..." price="₹149"
               old-price="₹199" :on-sale="true" badge="Coming soon" author="H. Madhukar Reddy" /> --}}
@props([
    'title' => '',
    'author' => null,
    'description' => null,
    'price' => null,
    'oldPrice' => null,
    'onSale' => false,
    'saleEndsAt' => null,
    'cover' => null,
    'coverAlt' => null,
    'badge' => null,
    'actionUrl' => null,
    'actionLabel' => 'View details',
])

<article class="card overflow-hidden flex flex-col hover:shadow-raised transition-shadow">
    <a href="{{ $actionUrl ?? '#' }}" class="block relative bg-teal-900" tabindex="-1" aria-hidden="true">
        <div class="aspect-[3/4] overflow-hidden">
            @if($cover)
                <img src="{{ $cover }}" alt="" class="w-full h-full object-cover" loading="lazy">
            @else
                <div class="book-cover-placeholder" role="img" aria-label="Cover coming soon for {{ $title }}">
                    <span>{{ $title }}</span>
                </div>
            @endif
        </div>
        @if($onSale)
            <span class="off-chip absolute top-3 left-3 shadow-md">Sale</span>
        @endif
        @if($badge)
            <span class="absolute top-3 left-3"><x-ui.badge type="info">{{ $badge }}</x-ui.badge></span>
        @endif
    </a>
    <div class="p-4 flex flex-col gap-1.5 flex-1">
        <h3 class="font-display text-[22px] font-bold uppercase tracking-wide leading-none line-clamp-2">
            <a href="{{ $actionUrl ?? '#' }}">{{ $title }}</a>
        </h3>
        @if($author)<p class="text-[13px] text-mutedtext">by <span class="font-semibold text-inktext">{{ $author }}</span></p>@endif
        @if($description)<p class="text-sm text-mutedtext line-clamp-2">{{ $description }}</p>@endif
        <p class="flex items-baseline gap-2 mt-auto pt-2">
            @if($price)<span class="font-display text-2xl font-extrabold text-saffron-600">{{ $price }}</span>@endif
            @if($oldPrice)<span class="book-card-oldprice">{{ $oldPrice }}</span>@endif
        </p>
        @if($onSale && $saleEndsAt)
        <p class="mt-1"><x-ui.countdown :ends-at="$saleEndsAt" /></p>
        @endif
        @if($actionUrl)
            <x-ui.button :href="$actionUrl" size="sm" class="w-full mt-2">{{ $actionLabel }}</x-ui.button>
        @endif
    </div>
</article>
