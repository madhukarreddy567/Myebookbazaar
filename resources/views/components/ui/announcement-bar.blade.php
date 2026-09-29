{{-- Dismissible announcement bar (Teal 900 bg, saffron link).
     Usage: <x-ui.announcement-bar id="launch">…message with <a>link</a>…</x-ui.announcement-bar>
     Note: Alpine expressions can't contain try/catch statements (they're
     evaluated as return-values), so persistence lives in real methods. --}}
@props(['id' => 'site'])

<div class="announcement-bar"
     x-data="{
         show: true,
         key: 'meb-announce-{{ $id }}',
         init() {
             try { this.show = localStorage.getItem(this.key) !== '1'; } catch (e) {}
         },
         dismiss() {
             this.show = false;
             try { localStorage.setItem(this.key, '1'); } catch (e) {}
         }
     }"
     x-show="show" x-cloak>
    <div class="max-w-container mx-auto flex items-center justify-center gap-3 px-10 relative">
        <p class="flex-1">{{ $slot }}</p>
        <button type="button" aria-label="Dismiss announcement"
                @click="dismiss()"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 opacity-80 hover:opacity-100">
            <x-ui.icon name="x" class="w-4 h-4" />
        </button>
    </div>
</div>
