<?php

namespace App\Services;

use App\Support\Security;
use Illuminate\Support\Facades\Http;

/**
 * Google OAuth sign-in (customer login / registration via Google).
 *
 * Credentials live in Settings (auth.google.*) — the Client Secret is
 * encrypted at rest and NEVER leaves the server except to Google's own
 * token endpoint over HTTPS during the real OAuth flow and the
 * connection test. It is never rendered in HTML, logs, or audit trails.
 */
class GoogleOAuthService
{
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(protected SettingsService $settings) {}

    /**
     * Master switch from the Settings Center.
     */
    public function enabled(): bool
    {
        return $this->settings->boolean('auth.google.enabled', false);
    }

    public function clientId(): ?string
    {
        $id = trim((string) $this->settings->get('auth.google.client_id', ''));
        return $id === '' ? null : $id;
    }

    /**
     * Server-side only. Never pass to a view, log, or audit trail.
     */
    public function clientSecret(): ?string
    {
        $secret = trim((string) $this->settings->get('auth.google.client_secret', ''));
        return $secret === '' ? null : $secret;
    }

    /**
     * The redirect URI the site owner must register in Google Cloud Console.
     */
    public function redirectUrl(): string
    {
        return route('auth.google.callback');
    }

    /**
     * True only when the switch is ON and both credentials are saved.
     * The Google buttons render only in this case.
     */
    public function configured(): bool
    {
        return $this->enabled() && $this->clientId() !== null && $this->clientSecret() !== null;
    }

    /**
     * Point Socialite at the Settings-stored credentials (runtime only —
     * nothing is written to config files or .env).
     */
    public function applyConfig(): void
    {
        config([
            'services.google' => [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'redirect' => $this->redirectUrl(),
            ],
        ]);
    }

    /**
     * "Test connection": validates the Client ID / Secret against
     * Google's real token endpoint using a dummy authorization code.
     * Google answers 401 invalid_client when the credentials are wrong,
     * and 400 invalid_grant when they are right (the code itself is fake).
     * The secret is only ever sent to Google over HTTPS — never logged,
     * never included in the returned message.
     */
    public function testConnection(): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'message' => 'Google sign-in is switched OFF. Turn it on first, then test.'];
        }

        $clientId = $this->clientId();
        $clientSecret = $this->clientSecret();

        if ($clientId === null) {
            return ['ok' => false, 'message' => 'No Google Client ID saved yet.'];
        }

        if (! str_ends_with($clientId, '.apps.googleusercontent.com')) {
            return ['ok' => false, 'message' => 'That Client ID does not look like a Google OAuth client ID — it should end with .apps.googleusercontent.com.'];
        }

        if ($clientSecret === null) {
            return ['ok' => false, 'message' => 'No Google Client Secret saved yet.'];
        }

        try {
            $response = Http::timeout(10)->asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => 'meb-connection-test',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $this->redirectUrl(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            Security::log('google.test_failed', 'Google test connection failed: network error.');

            return ['ok' => false, 'message' => 'Could not reach Google. Check the server internet connection and try again.'];
        }

        $error = (string) $response->json('error', '');

        // Right credentials + fake code => Google complains about the code.
        if ($response->status() === 400 && $error === 'invalid_grant') {
            return ['ok' => true, 'message' => 'Connected — Google accepted the Client ID and Secret.'];
        }

        if ($response->status() === 401 || $error === 'invalid_client') {
            Security::log('google.test_failed', 'Google test connection failed: credentials rejected.');

            return ['ok' => false, 'message' => 'Google rejected the credentials. Check the Client ID and Secret for typos or extra spaces.'];
        }

        Security::log('google.test_failed', 'Google test connection failed: unexpected response.', [
            'status' => $response->status(),
        ]);

        return ['ok' => false, 'message' => 'Google gave an unexpected answer (HTTP '.$response->status().'). Try again in a minute.'];
    }
}
