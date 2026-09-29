<?php

namespace App\Services;

use App\Support\Security;
use Illuminate\Http\Request;

/**
 * Phase 6: honeypot bot traps for public forms.
 *
 * Two traps, rendered by the <x-security.honeypot /> Blade component:
 *  - `website_url`: a normal-looking text field hidden with CSS. Humans
 *    never see or fill it; bots that auto-fill every field trip it.
 *  - `form_loaded_at`: unix timestamp rendered server-side when the form
 *    HTML is produced. Submissions faster than MIN_SECONDS are bots.
 *
 * Bots are never told they were caught: the controller returns the
 * normal "success" response without performing the action, and the hit
 * is written to the security log.
 */
class HoneypotService
{
    public const FIELD_URL = 'website_url';
    public const FIELD_TIME = 'form_loaded_at';
    public const MIN_SECONDS = 2;

    public function __construct(protected SettingsService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->enabled('security.honeypot_enabled', true);
    }

    /**
     * True when the request looks automated.
     */
    public function isBot(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        // Trap 1: the invisible field was filled.
        if (trim((string) $request->input(self::FIELD_URL, '')) !== '') {
            return true;
        }

        // Trap 2: submitted impossibly fast after the form was rendered.
        $loadedAt = $request->input(self::FIELD_TIME);
        if (is_numeric($loadedAt) && (int) $loadedAt > 0) {
            return (time() - (int) $loadedAt) < self::MIN_SECONDS;
        }

        return false;
    }

    /**
     * Record the hit. Callers then return the form's normal success
     * response WITHOUT performing the action.
     */
    public function recordHit(Request $request, string $form): void
    {
        Security::log('honeypot.hit', "Honeypot caught a bot on the {$form} form.", [
            'form' => $form,
            'filled_url_field' => trim((string) $request->input(self::FIELD_URL, '')) !== '',
        ]);
    }
}
