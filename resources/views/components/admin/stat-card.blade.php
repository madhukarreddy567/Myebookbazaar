{{-- Stat card: large tabular number, small label, optional change indicator.
      Usage: <x-admin.stat-card value="1,240" label="Paid orders" change="+12%" up /> --}}
@props(['value' => '0', 'label' => '', 'change' => null, 'up' => null])

<div {{ $attributes->merge(['class' => 'surface stat-card']) }}>
    <p class="stat-value">{{ $value }}</p>
    <p class="stat-label">{{ $label }}</p>
    @if($change)
        <p class="mt-2 inline-flex items-center gap-1 text-xs font-semibold {{ $up === false ? 'text-[var(--color-danger)]' : 'text-[var(--color-success)]' }}">
            <x-admin.icon :name="$up === false ? 'chevron' : 'check'" class="w-3.5 h-3.5" />
            {{ $change }}
        </p>
    @endif
</div>
