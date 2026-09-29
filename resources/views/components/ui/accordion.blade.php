{{-- FAQ accordion. Usage: <x-ui.accordion :items="[['q' => '…', 'a' => '…']]" /> --}}
@props(['items' => []])

<div x-data="{ open: null }">
    @foreach($items as $i => $item)
        <div class="accordion-item">
            <h3>
                <button type="button" class="accordion-button"
                        @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        :aria-expanded="open === {{ $i }}"
                        aria-controls="acc-panel-{{ $i }}" id="acc-btn-{{ $i }}">
                    <span>{{ $item['q'] }}</span>
                    <x-ui.icon name="chevron-down" class="w-5 h-5" />
                </button>
            </h3>
            <div id="acc-panel-{{ $i }}" role="region" aria-labelledby="acc-btn-{{ $i }}"
                 x-show="open === {{ $i }}" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="accordion-panel">
                <p>{{ $item['a'] }}</p>
            </div>
        </div>
    @endforeach
</div>
