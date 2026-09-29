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
