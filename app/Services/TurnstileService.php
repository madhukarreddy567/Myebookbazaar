<?php

namespace App\Services;

use App\Support\Security;
use Illuminate\Support\Facades\Http;

/**
 * Phase 6: Cloudflare Turnstile bot protection.
 *
 * Keys live in Settings → Security (never in .env for production use,
 * though .env fallback exists). Every token is verified server-side
 * against Cloudflare's siteverify endpoint. Fail closed: any transport
 * error or missing secret rejects the submission.
 */
class TurnstileService
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(protected SettingsService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->enabled('security.turnstile_enabled')
            && $this->siteKey() !== null
            && $this->secretKey() !== null;
    }

    /**
     * Is Turnstile required for a given form? $form is one of:
     * register, login, forgot, contact, checkout, admin_login.
     */
    public function requiredFor(string $form): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return $this->settings->enabled('security.turnstile_'.$form);
    }

    public function siteKey(): ?string
    {
        $key = $this->settings->get('security.turnstile_site_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    protected function secretKey(): ?string
    {
        $key = $this->settings->get('security.turnstile_secret_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Verify a token server-side. Returns false (fail closed) when the
     * secret is missing, the token is empty, or Cloudflare cannot be
     * reached / rejects the token.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        $secret = $this->secretKey();

        if ($secret === null || ! is_string($token) || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::timeout(10)->asForm()->post(self::VERIFY_URL, array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));

            if (! $response->successful()) {
                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * "Test connection" for the Settings Center. Cloudflare's siteverify
     * answers a deliberately bogus token with error codes — a wrong
     * secret yields `invalid-input-secret`, while a correct secret
     * yields `invalid-input-response`. Either way the secret itself is
     * never revealed in the message.
     */
    public function testConnection(): array
    {
        $secret = $this->secretKey();

        if ($secret === null) {
            return ['ok' => false, 'message' => 'No Turnstile secret key saved yet.'];
        }

        try {
            $response = Http::timeout(10)->asForm()->post(self::VERIFY_URL, [
                'secret' => $secret,
                'response' => 'meb-connection-test-token',
            ]);

            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'Cloudflare did not answer (HTTP '.$response->status().'). Try again.'];
            }

            $codes = $response->json('error-codes', []);

            if (in_array('invalid-input-secret', (array) $codes, true)
                || in_array('missing-input-secret', (array) $codes, true)) {
                Security::log('turnstile.test_failed', 'Turnstile test connection failed: secret rejected.');

                return ['ok' => false, 'message' => 'Cloudflare rejected the secret key. Check it and try again.'];
            }

            return ['ok' => true, 'message' => 'Connected — Cloudflare accepted the secret key.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'Could not reach Cloudflare: '.mb_substr($e->getMessage(), 0, 200)];
        }
    }
}
