<?php

namespace App\Providers;

use App\Validation\SafeValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->shareFeatureFlags();
        $this->applyMailSettings();
        $this->hardenEmailValidation();
    }

    /**
     * Backport of the upstream GHSA-5vg9-5847-vvmq / CVE-2026-48019 fix
     * (CRLF injection in the default `email` rule). No patched Laravel 11
     * release exists, so every validator in the app uses SafeValidator,
     * which rejects CR/LF in email addresses before the normal checks run.
     */
    protected function hardenEmailValidation(): void
    {
        Validator::resolver(
            fn ($translator, $data, $rules, $messages, $attributes) => new SafeValidator(
                $translator, $data, $rules, $messages, $attributes
            )
        );
    }

    /**
     * Feature-switch flags for the customer layout, so templates can hide
     * registration/login links when the switches are OFF. The routes
     * themselves are already gated by the `feature` middleware.
     * (Values come from the cached settings map: one cache read.)
     */
    protected function shareFeatureFlags(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with('featuresRegistrationEnabled', settings_enabled('features.registration'));
            $view->with('featuresLoginEnabled', settings_enabled('features.login'));
        });
    }

    /**
     * Make the whole application send mail with the SMTP settings stored
     * in the Settings Center. Any setting left empty falls back to the
     * .env value, so nothing breaks before the admin configures SMTP.
     */
    protected function applyMailSettings(): void
    {
        $smtp = config('mail.mailers.smtp', []);

        foreach ([
            'host' => settings('mail.host'),
            'port' => settings('mail.port'),
            'username' => settings('mail.username'),
            'password' => settings('mail.password'),
            'encryption' => settings('mail.encryption'),
        ] as $option => $value) {
            // Empty (or the literal "null" placeholder some .env files use)
            // means "keep the .env default".
            if ($value !== null && $value !== '' && $value !== 'null') {
                $smtp[$option] = $option === 'port' ? (int) $value : $value;
            }
        }

        config(['mail.mailers.smtp' => $smtp]);

        if (($fromAddress = settings('mail.from_address')) !== null && $fromAddress !== '') {
            config(['mail.from.address' => $fromAddress]);
        }

        if (($fromName = settings('mail.from_name')) !== null && $fromName !== '') {
            config(['mail.from.name' => $fromName]);
        }
    }
}
