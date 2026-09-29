{{-- Sale countdown timer (Alpine.js). Pass the sale end as an ISO-8601
     string, e.g. <x-ui.countdown :ends-at="$book->sale_ends_at->toIso8601String()" />.
     The end timestamp is rendered in a data attribute so tests and
     no-JS fallbacks can see it. --}}
@props(['endsAt'])
<span x-data="{
        end: new Date('{{ $endsAt }}').getTime(),
        label: '',
        tick() {
            let s = Math.max(0, Math.floor((this.end - Date.now()) / 1000));
            const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
            this.label = 'Sale ends in ' + (d > 0 ? d + 'd ' : '') + h + 'h ' + m + 'm';
        }
    }"
    x-init="tick(); setInterval(() => tick(), 30000)"
    x-text="label"
    data-countdown-end="{{ $endsAt }}"
    class="countdown-timer text-sm font-semibold text-saffron-700"></span>
