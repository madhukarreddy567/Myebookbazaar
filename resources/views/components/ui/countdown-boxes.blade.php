{{-- Sale countdown as HH : MM : SS boxes (Stitch style).
     Pass the sale end as an ISO-8601 string, e.g.
     <x-ui.countdown-boxes :ends-at="$book->sale_ends_at->toIso8601String()" />.
     Initial values are rendered server-side so the boxes never show 00
     before Alpine hydrates; Alpine then ticks every second. --}}
@props(['endsAt'])
@php
$secs = max(0, (int) (strtotime((string) $endsAt) - time()));
$hh = str_pad((string) intdiv($secs, 3600), 2, '0', STR_PAD_LEFT);
$mm = str_pad((string) intdiv($secs % 3600, 60), 2, '0', STR_PAD_LEFT);
$ss = str_pad((string) ($secs % 60), 2, '0', STR_PAD_LEFT);
@endphp
<span x-data="{
        end: new Date('{{ $endsAt }}').getTime(),
        h: '{{ $hh }}', m: '{{ $mm }}', s: '{{ $ss }}',
        tick() {
            const x = Math.max(0, Math.floor((this.end - Date.now()) / 1000));
            this.h = String(Math.floor(x / 3600)).padStart(2, '0');
            this.m = String(Math.floor((x % 3600) / 60)).padStart(2, '0');
            this.s = String(x % 60).padStart(2, '0');
        }
    }"
    x-init="tick(); setInterval(() => tick(), 1000)"
    data-countdown-end="{{ $endsAt }}"
    class="inline-flex items-center gap-1.5" role="timer" aria-label="Sale ends in {{ $hh }} hours {{ $mm }} minutes">
    <span class="countdown-box"><span class="num" x-text="h">{{ $hh }}</span><span class="lbl">Hrs</span></span>
    <span class="countdown-sep" aria-hidden="true">:</span>
    <span class="countdown-box"><span class="num" x-text="m">{{ $mm }}</span><span class="lbl">Min</span></span>
    <span class="countdown-sep" aria-hidden="true">:</span>
    <span class="countdown-box"><span class="num" x-text="s">{{ $ss }}</span><span class="lbl">Sec</span></span>
</span>
