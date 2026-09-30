<?php

use App\Services\SettingsService;

if (! function_exists('settings')) {
    /**
     * Read a setting from the Settings Center (database), falling back to
     * the matching .env variable and then to $default.
     *
     *     settings('site.name')
     *     settings('features.registration', true)
     */
    function settings(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('settings_bool')) {
    /**
     * Boolean-safe settings read: settings_bool('features.registration').
     */
    function settings_bool(string $key, bool $default = false): bool
    {
        return app(SettingsService::class)->boolean($key, $default);
    }
}

if (! function_exists('settings_enabled')) {
    /**
     * Feature-switch read: settings_enabled('features.coupons').
     */
    function settings_enabled(string $key, bool $default = false): bool
    {
        return app(SettingsService::class)->enabled($key, $default);
    }
}

if (! function_exists('safe_url')) {
    /**
     * URL allow-list for values rendered into href/src attributes that
     * come from admin-editable settings (social links, announcement
     * link). Only http/https URLs pass through — anything else
     * (javascript:, data:, vbscript:, …) becomes '' so a stored value
     * can never turn into a stored-XSS vector.
     */
    function safe_url(mixed $value): string
    {
        $url = trim((string) $value);

        if ($url === '') {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }
}
