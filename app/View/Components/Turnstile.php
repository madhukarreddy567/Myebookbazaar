<?php

namespace App\View\Components;

use App\Services\TurnstileService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Phase 6: Cloudflare Turnstile widget.
 *
 * Renders the widget only when Turnstile is enabled in Settings and
 * required for the given form (register|login|forgot|contact|checkout|
 * admin_login). Server-side verification happens in the controllers via
 * the VerifiesTurnstile concern (fail-closed).
 */
class Turnstile extends Component
{
    public function __construct(public string $form = 'register')
    {
    }

    public function render(): View|string
    {
        $turnstile = app(TurnstileService::class);

        if (! $turnstile->requiredFor($this->form)) {
            return '';
        }

        return view('components.turnstile', [
            'form' => $this->form,
            'siteKey' => $turnstile->siteKey(),
        ]);
    }
}
