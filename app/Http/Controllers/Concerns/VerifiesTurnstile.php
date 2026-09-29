<?php

namespace App\Http\Controllers\Concerns;

use App\Services\TurnstileService;
use App\Support\Security;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Phase 6: server-side Cloudflare Turnstile verification for controllers.
 *
 * The widget posts its token as `cf-turnstile-response` (classic forms)
 * or `cf_turnstile_response` (JSON bodies, e.g. checkout). Verification
 * is fail-closed: a missing/invalid token or an unreachable Cloudflare
 * rejects the submission.
 */
trait VerifiesTurnstile
{
    /**
     * @throws ValidationException when the token is missing or invalid.
     */
    protected function verifyTurnstile(Request $request, string $form): void
    {
        $turnstile = app(TurnstileService::class);

        if (! $turnstile->requiredFor($form)) {
            return;
        }

        $token = $request->input('cf-turnstile-response')
            ?? $request->input('cf_turnstile_response');

        if (! $turnstile->verify($token, $request->ip())) {
            Security::log('turnstile.failed', "Turnstile check failed on the {$form} form.", [
                'form' => $form,
            ]);

            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Security check failed. Please try again.',
            ]);
        }
    }
}
