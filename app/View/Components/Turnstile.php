<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Cloudflare Turnstile widget.
 *
 * Renders the widget only when TURNSTILE_SITE_KEY is configured;
 * otherwise renders nothing (disabled). Server-side verification
 * is NOT wired yet — that arrives in Phase 6.
 */
class Turnstile extends Component
{
    public function render(): View|string
    {
        if (empty(config('services.turnstile.site_key'))) {
            return '<!-- turnstile disabled: no site key configured -->';
        }

        return view('components.turnstile');
    }
}
