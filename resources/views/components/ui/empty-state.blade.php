{{-- Empty state: centered icon, title, inviting action text.
     Usage: <x-ui.empty-state icon="book" title="…" text="…" action-url="…" action-label="…" /> --}}
@props(['icon' => 'info', 'title' => '', 'text' => null, 'actionUrl' => null, 'actionLabel' => null])

<div class="empty-state">
    <div class="empty-state-icon">
        <x-ui.icon :name="$icon" class="w-8 h-8" />
    </div>
    <h2 class="text-2xl font-bold">{{ $title }}</h2>
    @if($text)<p class="text-mutedtext mt-2 max-w-md mx-auto">{{ $text }}</p>@endif
    @if($actionUrl)
        <div class="mt-6"><x-ui.button :href="$actionUrl">{{ $actionLabel }}</x-ui.button></div>
    @endif
</div>
