{{-- Star rating summary (Stitch style): amber stars + numeric average and
     verified-review count. Renders nothing when there are no reviews yet.
     Usage: <x-ui.rating-stars :average="$book->rating_average" :count="$book->ratings_count" /> --}}
@props([
    'average' => null,
    'count' => 0,
    'starClass' => 'w-4 h-4',
])

@if((int) $count > 0 && $average !== null)
@php
    $avg = (float) $average;
    $full = (int) round($avg);
@endphp
<span class="inline-flex items-center gap-1.5" role="img" aria-label="Rated {{ number_format($avg, 1) }} out of 5, based on {{ $count }} verified {{ \Illuminate\Support\Str::plural('review', (int) $count) }}">
    <span class="inline-flex items-center gap-0.5" aria-hidden="true">
        @for($i = 1; $i <= 5; $i++)
        <svg viewBox="0 0 24 24" class="{{ $starClass }} {{ $i <= $full ? 'fill-[#F5A623]' : 'fill-[#E3D9C2]' }}"><path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/></svg>
        @endfor
    </span>
    <span class="text-sm font-extrabold text-[#0B3D4A]">{{ number_format($avg, 1) }}</span>
    <span class="text-[13px] text-[#5B6B78]">({{ $count }} verified {{ \Illuminate\Support\Str::plural('review', (int) $count) }})</span>
</span>
@endif
