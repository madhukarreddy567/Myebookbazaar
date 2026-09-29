{{-- Modal dialog (Alpine). Usage:
     <x-ui.modal id="x"> ... </x-ui.modal>  — open with $dispatch('open-modal') scoped,
     or wrap: <div x-data="{ open: false }"> <button @click="open = true">… --}}
@props(['title' => null, 'maxWidth' => 'max-w-lg'])

<div x-data="{ open: false }"
     @open-modal.window="open = true"
     @close-modal.window="open = false"
     @keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="modal-backdrop" @click.self="open = false"
         x-transition.opacity role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
        <div class="modal-panel {{ $maxWidth }}" x-transition.scale.95>
            @if($title)
                <div class="flex items-start justify-between gap-4 mb-4">
                    <h2 class="text-2xl font-bold">{{ $title }}</h2>
                    <button type="button" @click="open = false" aria-label="Close dialog"
                            class="p-2 -m-2 text-mutedtext hover:text-inktext">
                        <x-ui.icon name="x" class="w-5 h-5" />
                    </button>
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
    {{ $trigger ?? '' }}
</div>
