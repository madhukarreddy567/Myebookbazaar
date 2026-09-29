<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Single cached gateway for every setting in the application.
 *
 * - Values live in the `settings` table; secrets are encrypted with
 *   Laravel Crypt before they touch the database.
 * - When a key has no database row, the matching .env variable is used
 *   as a fallback (explicit map below, then KEY.NAME -> KEY_NAME).
 * - The whole table is cached; the cache is cleared on every write.
 * - All database access is wrapped so a missing/unmigrated table
 *   degrades gracefully to env/default instead of crashing the app.
 */
class SettingsService
{
    public const CACHE_KEY = 'meb.settings.map';

    /**
     * Explicit setting-key -> env-variable mapping. Anything not listed
     * falls back to the generic conversion (site.name -> SITE_NAME).
     */
    public const ENV_MAP = [
        'site.name' => 'APP_NAME',
        'site.support_email' => 'SUPPORT_EMAIL',
        'mail.host' => 'MAIL_HOST',
        'mail.port' => 'MAIL_PORT',
        'mail.username' => 'MAIL_USERNAME',
        'mail.password' => 'MAIL_PASSWORD',
        'mail.encryption' => 'MAIL_ENCRYPTION',
        'mail.from_name' => 'MAIL_FROM_NAME',
        'mail.from_address' => 'MAIL_FROM_ADDRESS',
        'features.email_verification' => 'EMAIL_VERIFICATION_ENABLED',
        'security.turnstile_site_key' => 'TURNSTILE_SITE_KEY',
        'security.turnstile_secret_key' => 'TURNSTILE_SECRET_KEY',
    ];

    /**
     * Load every row once (cached forever, cleared on write).
     *
     * @return array<string, array{value: ?string, type: string, is_secret: bool, group: string}>
     */
    protected function map(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                $rows = [];
                foreach (Setting::query()->get(['key', 'value', 'type', 'is_secret', 'group']) as $row) {
                    $rows[$row->key] = [
                        'value' => $row->value,
                        'type' => $row->type,
                        'is_secret' => (bool) $row->is_secret,
                        'group' => $row->group,
                    ];
                }

                return $rows;
            });
        } catch (QueryException $e) {
            // Table not migrated yet (fresh install) — degrade to env/default.
            return [];
        }
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->map())) {
            return true;
        }

        return env($this->envName($key)) !== null;
    }

    /**
     * Read a setting. Order: database -> matching .env variable -> $default.
     * Secrets are decrypted; values are cast to their declared type.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $map = $this->map();

        if (array_key_exists($key, $map)) {
            $row = $map[$key];

            return $this->cast($this->decrypt($row), $row['type']);
        }

        $envValue = env($this->envName($key));
        if ($envValue !== null && $envValue !== '') {
            return $this->castEnv($key, $envValue);
        }

        return $default;
    }

    /**
     * Boolean convenience: normalises '1', 'true', 'yes', 'on' etc.
     */
    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /** Alias that reads well at call sites: settings_enabled('features.x'). */
    public function enabled(string $key, bool $default = false): bool
    {
        return $this->boolean($key, $default);
    }

    /**
     * Write a setting. Secret values are encrypted before storage and the
     * settings cache is cleared so the new value takes effect immediately.
     */
    public function set(string $key, mixed $value, ?int $adminId = null): Setting
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);

        $raw = $value === null ? null : (string) $value;
        $setting->value = ($setting->is_secret && $raw !== null && $raw !== '')
            ? Crypt::encryptString($raw)
            : $raw;
        $setting->updated_by = $adminId;
        $setting->save();

        $this->forgetCache();

        return $setting;
    }

    /**
     * All settings, optionally filtered by group. Secrets are decrypted.
     */
    public function all(?string $group = null): array
    {
        $out = [];
        foreach ($this->map() as $key => $row) {
            if ($group !== null && $row['group'] !== $group) {
                continue;
            }
            $out[$key] = $this->cast($this->decrypt($row), $row['type']);
        }

        return $out;
    }

    /**
     * Masked display for a secret: never returns the real value.
     * Example: "••••••••abcd". Returns null when nothing is stored.
     */
    public function masked(string $key): ?string
    {
        $map = $this->map();
        if (! array_key_exists($key, $map)) {
            // Fall back to env: mask it the same way, never reveal it.
            $envValue = env($this->envName($key));
            if ($envValue === null || $envValue === '') {
                return null;
            }

            return $this->mask((string) $envValue);
        }

        $plain = $this->decrypt($map[$key]);
        if ($plain === null || $plain === '') {
            return null;
        }

        return $this->mask($plain);
    }

    /**
     * Pricing region override for a country ISO code. India defaults to
     * IN, everything else to INTL; the admin can override any country in
     * the Settings Center (stored as JSON in pricing.country_regions).
     */
    public function regionForCountry(string $isoCode): string
    {
        $iso = strtoupper($isoCode);
        $raw = $this->get('pricing.country_regions');

        if (is_string($raw) && $raw !== '') {
            $map = json_decode($raw, true);
            if (is_array($map) && isset($map[$iso]) && in_array($map[$iso], ['IN', 'INTL'], true)) {
                return $map[$iso];
            }
        }

        return $iso === 'IN' ? 'IN' : 'INTL';
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function envName(string $key): string
    {
        return self::ENV_MAP[$key] ?? strtoupper(str_replace('.', '_', $key));
    }

    protected function decrypt(array $row): ?string
    {
        if (! $row['is_secret'] || $row['value'] === null || $row['value'] === '') {
            return $row['value'];
        }

        try {
            return Crypt::decryptString($row['value']);
        } catch (DecryptException $e) {
            // APP_KEY rotated or value corrupted: treat as missing so the
            // env fallback (or default) applies instead of crashing.
            return null;
        }
    }

    protected function cast(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $this->toBool($value),
            'number' => is_numeric($value) && str_contains((string) $value, '.')
                ? (float) $value
                : (is_numeric($value) ? (int) $value : $value),
            default => (string) $value,
        };
    }

    protected function castEnv(string $key, mixed $value): mixed
    {
        // Env values are strings; boolean-ish settings get normalised.
        if (str_starts_with($key, 'features.') || str_ends_with($key, '_enabled') || str_ends_with($key, '_required')) {
            return $this->toBool($value);
        }

        return $value;
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    protected function mask(string $plain): string
    {
        $last4 = mb_substr($plain, -4);

        return '••••••••'.$last4;
    }
}
