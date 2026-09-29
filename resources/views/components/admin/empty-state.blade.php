{{-- Friendly empty state with next action.
      Usage: <x-admin.empty-state title="No coupons yet." action="Create your first coupon" href="..." /> --}}
@props(['title' => 'Nothing here yet.', 'hint' => '', 'action' => null, 'href' => '#', 'icon' => 'list'])

<div class="surface p-10 text-center">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-teal-100)] text-[var(--color-teal-700)]">
        <x-admin.icon :name="$icon" class="w-6 h-6" />
    </div>
    <p class="font-display text-xl font-bold text-[var(--color-text)]">{{ $title }}</p>
    @if($hint)<p class="mt-1 text-sm text-[var(--color-muted)]">{{ $hint }}</p>@endif
    @if($action)
        <a href="{{ $href }}" class="btn btn-primary btn-sm mt-5">{{ $action }}</a>
    @endif
</div>
