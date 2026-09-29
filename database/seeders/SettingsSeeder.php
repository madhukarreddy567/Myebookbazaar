<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds every Settings Center key with safe defaults (Phase 2A).
 * Idempotent: existing rows are left untouched (only missing keys are
 * created) so admin changes are never overwritten.
 */
class SettingsSeeder extends Seeder
{
    /**
     * [key, group, type, is_secret, default value]
     */
    public const DEFAULTS = [
        // ---- 1. General ----
        ['site.name', 'general', 'text', false, 'MyEbookBazaar'],
        ['site.tagline', 'general', 'text', false, 'Simple ebooks for fitness, skills and money.'],
        ['site.logo', 'general', 'image', false, null],
        ['site.favicon', 'general', 'image', false, null],
        ['site.support_email', 'general', 'text', false, 'support@myebookbazaar.com'],
        ['site.phone', 'general', 'text', false, null],
        ['site.address', 'general', 'textarea', false, null],
        ['site.instagram', 'general', 'text', false, 'https://www.instagram.com/myebookbazaar'],
        ['site.youtube', 'general', 'text', false, null],
        ['site.x', 'general', 'text', false, null],
        ['site.facebook', 'general', 'text', false, null],
        ['site.telegram', 'general', 'text', false, null],
        ['site.footer_text', 'general', 'textarea', false, null],
        ['site.announcement_text', 'general', 'textarea', false, 'Our first ebook is on the way. Create a free account to get launch updates.'],

        // ---- 2. Feature switches ----
        ['features.registration', 'features', 'boolean', false, '1'],
        ['features.login', 'features', 'boolean', false, '1'],
        ['features.email_verification', 'features', 'boolean', false, '1'],
        ['features.guest_browsing', 'features', 'boolean', false, '1'],
        ['features.guest_cart', 'features', 'boolean', false, '1'],
        ['features.buy_now', 'features', 'boolean', false, '1'],
        ['features.checkout', 'features', 'boolean', false, '1'],
        ['features.coupons', 'features', 'boolean', false, '1'],
        ['features.combos', 'features', 'boolean', false, '1'],
        ['features.sales_offers', 'features', 'boolean', false, '1'],
        ['features.announcement_bar', 'features', 'boolean', false, '1'],
        ['features.cookie_banner', 'features', 'boolean', false, '1'],
        ['features.contact_form', 'features', 'boolean', false, '1'],
        ['features.downloads', 'features', 'boolean', false, '1'],
        ['downloads.limit', 'features', 'number', false, '5'],

        // ---- 3. Pricing and regions ----
        ['pricing.inr_enabled', 'pricing', 'boolean', false, '1'],
        ['pricing.usd_enabled', 'pricing', 'boolean', false, '1'],
        ['pricing.default_region', 'pricing', 'select', false, 'IN'],
        // JSON map of ISO code -> IN|INTL overrides. Empty = defaults
        // (India -> IN, everyone else -> INTL).
        ['pricing.country_regions', 'pricing', 'textarea', false, null],

        // ---- 4. Payments ----
        ['payments.razorpay_enabled', 'payments', 'boolean', false, '0'],
        ['payments.razorpay_mode', 'payments', 'select', false, 'test'],
        ['payments.razorpay_key_id', 'payments', 'text', false, null],
        ['payments.razorpay_key_secret', 'payments', 'secret', true, null],
        ['payments.razorpay_webhook_secret', 'payments', 'secret', true, null],
        ['payments.inr_enabled', 'payments', 'boolean', false, '1'],
        ['payments.usd_enabled', 'payments', 'boolean', false, '1'],

        // ---- 5. Email ----
        ['mail.host', 'mail', 'text', false, null],
        ['mail.port', 'mail', 'number', false, '587'],
        ['mail.username', 'mail', 'text', false, null],
        ['mail.password', 'mail', 'secret', true, null],
        ['mail.encryption', 'mail', 'select', false, 'tls'],
        ['mail.from_name', 'mail', 'text', false, 'MyEbookBazaar'],
        ['mail.from_address', 'mail', 'text', false, 'hello@myebookbazaar.com'],

        // ---- 6. Security (wired up in Phase 6; switches stored now) ----
        ['security.turnstile_enabled', 'security', 'boolean', false, '0'],
        ['security.turnstile_site_key', 'security', 'text', false, null],
        ['security.turnstile_secret_key', 'security', 'secret', true, null],
        ['security.turnstile_register', 'security', 'boolean', false, '0'],
        ['security.turnstile_login', 'security', 'boolean', false, '0'],
        ['security.turnstile_forgot', 'security', 'boolean', false, '0'],
        ['security.turnstile_contact', 'security', 'boolean', false, '0'],
        ['security.turnstile_checkout', 'security', 'boolean', false, '0'],
        ['security.turnstile_admin_login', 'security', 'boolean', false, '0'],
        ['security.rate_limit_login', 'security', 'number', false, '5'],
        ['security.rate_limit_register', 'security', 'number', false, '5'],
        ['security.rate_limit_forgot', 'security', 'number', false, '3'],
        ['security.lockout_minutes', 'security', 'number', false, '15'],
        ['security.require_2fa_admin', 'security', 'boolean', false, '0'],
        ['security.honeypot_enabled', 'security', 'boolean', false, '1'],
        ['security.maintenance_enabled', 'security', 'boolean', false, '0'],
        ['security.maintenance_message', 'security', 'textarea', false, null],
        ['security.maintenance_bypass_ips', 'security', 'textarea', false, null],
        ['security.cookie_consent_enabled', 'security', 'boolean', false, '0'],

        // ---- 7. Legal and content ----
        ['legal.privacy_policy', 'legal', 'textarea', false, null],
        ['legal.no_refund_policy', 'legal', 'textarea', false, 'All sales are final. Because our products are digital ebooks delivered instantly, we do not offer refunds.'],
        ['legal.terms', 'legal', 'textarea', false, null],
        ['legal.cookie_notice', 'legal', 'textarea', false, null],

        // ---- 8. Updates (Phase 7) ----
        ['updates.enabled', 'updates', 'boolean', false, '0'],
        ['updates.repo_owner', 'updates', 'text', false, null],
        ['updates.repo_name', 'updates', 'text', false, null],
        ['updates.branch', 'updates', 'text', false, 'main'],
        ['updates.channel', 'updates', 'select', false, 'stable'],
        ['updates.github_token', 'updates', 'secret', true, null],
        ['updates.auto_check', 'updates', 'boolean', false, '1'],
        ['updates.backup_retention', 'updates', 'number', false, '7'],
        ['auth.google.enabled', 'updates', 'boolean', false, '0'],
        ['auth.google.client_id', 'updates', 'text', false, null],
        ['auth.google.client_secret', 'updates', 'secret', true, null],
        ['updates.installed_version', 'updates', 'text', false, null],
        ['updates.installed_sha', 'updates', 'text', false, null],
        ['updates.last_checked_at', 'updates', 'text', false, null],
        ['updates.latest_sha', 'updates', 'text', false, null],
        ['updates.release_tag', 'updates', 'text', false, null],
        ['updates.release_name', 'updates', 'text', false, null],
        ['updates.release_notes', 'updates', 'textarea', false, null],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as [$key, $group, $type, $isSecret, $default]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'type' => $type,
                    'is_secret' => $isSecret,
                    'value' => $default,
                ]
            );
        }
    }
}
