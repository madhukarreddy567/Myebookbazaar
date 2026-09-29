{{-- Phase 6: honeypot bot traps. Renders nothing visible.
     - `website_url`: a real text field hidden with CSS. Humans never see
       it; bots that fill every field trip it.
     - `form_loaded_at`: server timestamp; submissions under 2 seconds
       after render are treated as bots.
     Rendered only when the honeypot switch is on. --}}
@if(app(\App\Services\HoneypotService::class)->enabled())
    <div class="hp-trap" aria-hidden="true">
        <label for="hp-website">Website</label>
        <input type="text" id="hp-website" name="website_url" value=""
               tabindex="-1" autocomplete="off">
        <input type="hidden" name="form_loaded_at" value="{{ time() }}">
    </div>
    @once
        @push('head')
            <style>
                /* Honeypot trap: hidden from humans AND assistive tech,
                   but present in the DOM for naive bots. */
                .hp-trap {
                    position: absolute !important;
                    left: -9999px !important;
                    top: auto !important;
                    width: 1px !important;
                    height: 1px !important;
                    overflow: hidden !important;
                }
            </style>
        @endpush
    @endonce
@endif
