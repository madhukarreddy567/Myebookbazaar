{{-- Page header: title + optional actions slot.
      Usage: <x-admin.page-header title="Books"><a class="btn btn-primary">Add book</a></x-admin.page-header> --}}
@props(['title' => '', 'subtitle' => ''])

<div class="mb-6 flex flex-wrap items-center gap-4">
    <div class="min-w-0 flex-1">
        <h2 class="font-display text-3xl font-bold text-[var(--color-text)]">{{ $title }}</h2>
        @if($subtitle)<p class="mt-1 text-sm text-[var(--color-muted)]">{{ $subtitle }}</p>@endif
    </div>
    <div class="flex items-center gap-3">{{ $slot }}</div>
</div>
