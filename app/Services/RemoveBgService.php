<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Optional remove.bg integration for the site logo.
 *
 * - Only used when the admin has saved a remove.bg API key
 *   (site.remove_bg_api_key) AND uploads a logo.
 * - remove.bg returns a PNG with a transparent background; the stored
 *   file is replaced with those exact bytes (never flattened or
 *   converted to JPEG).
 * - Any API/network failure returns null — the caller keeps the
 *   original upload and flashes a warning, so the upload never breaks.
 * - The API key is sent only to https://api.remove.bg over HTTPS and is
 *   never written to logs, views, or audit records.
 */
class RemoveBgService
{
    public const ENDPOINT = 'https://api.remove.bg/v1.0/removebg';

    public function __construct(protected SettingsService $settings) {}

    /**
     * The configured API key, or null when background removal is
     * unavailable (no key saved).
     */
    public function apiKey(): ?string
    {
        $key = trim((string) $this->settings->get('site.remove_bg_api_key', ''));

        return $key === '' ? null : $key;
    }

    public function enabled(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * Send an image to remove.bg and return the transparent PNG bytes,
     * or null when the API call fails for any reason (no key, network
     * error, non-200 response, unexpected payload).
     */
    public function removeBackground(string $sourcePath, ?string $apiKey = null): ?string
    {
        $key = $apiKey ?? $this->apiKey();

        if ($key === null || ! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return null;
        }

        try {
            $response = Http::withHeaders(['X-Api-Key' => $key])
                ->timeout(30)
                ->attach('image_file', file_get_contents($sourcePath), basename($sourcePath))
                ->post(self::ENDPOINT, ['size' => 'auto']);
        } catch (\Throwable $e) {
            // Network failure: the caller falls back to the original upload.
            // Never log the exception message: HTTP client errors can echo
            // request details (URL/headers) that would expose the API key.
            Log::warning('remove.bg request failed ('.get_class($e).'); keeping the original upload.');

            return null;
        }

        if (! $response->successful()) {
            // Never log the key — only the status code.
            Log::warning('remove.bg returned HTTP '.$response->status().'; keeping the original upload.');

            return null;
        }

        $bytes = $response->body();

        // Sanity check: a real PNG starts with the PNG magic bytes.
        if ($bytes === '' || ! str_starts_with($bytes, "\x89PNG")) {
            Log::warning('remove.bg returned an unexpected payload; keeping the original upload.');

            return null;
        }

        return $bytes;
    }
}
