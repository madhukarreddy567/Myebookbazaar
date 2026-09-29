<?php

namespace App\Settings;

/**
 * Declarative definition of every Settings Center tab and field (Phase 2A).
 * The actual VALUES live in the database / .env — this file only describes
 * the shape of the admin UI and the validation rules.
 */
class Definition
{
    public static function tabs(): array
    {
        return [
            'general' => ['label' => 'General', 'icon' => 'home'],
            'features' => ['label' => 'Feature switches', 'icon' => 'zap'],
            'pricing' => ['label' => 'Pricing and regions', 'icon' => 'tag'],
            'payments' => ['label' => 'Payments', 'icon' => 'cart'],
            'mail' => ['label' => 'Email', 'icon' => 'mail'],
            'security' => ['label' => 'Security', 'icon' => 'shield'],
            'legal' => ['label' => 'Legal and content', 'icon' => 'file-text'],
            'updates' => ['label' => 'Updates and integrations', 'icon' => 'refresh'],
        ];
    }

    /**
     * Fields per tab. Each field: key, label, type, required?, help?, options?
     * Types: text, email, number, boolean, secret, select, textarea, image.
     */
    public static function fields(string $tab): array
    {
        return match ($tab) {
            'general' => [
                ['key' => 'site.name', 'label' => 'Site name', 'type' => 'text', 'required' => true],
                ['key' => 'site.tagline', 'label' => 'Tagline', 'type' => 'text'],
                ['key' => 'site.logo', 'label' => 'Logo', 'type' => 'image', 'help' => 'PNG or SVG, max 2 MB. Shown in the navbar.'],
                ['key' => 'site.favicon', 'label' => 'Favicon', 'type' => 'image', 'help' => 'ICO or PNG, max 2 MB.'],
                ['key' => 'site.support_email', 'label' => 'Support email', 'type' => 'email', 'required' => true],
                ['key' => 'site.phone', 'label' => 'Phone', 'type' => 'text'],
                ['key' => 'site.address', 'label' => 'Business address', 'type' => 'textarea'],
                ['key' => 'site.instagram', 'label' => 'Instagram URL', 'type' => 'text'],
                ['key' => 'site.youtube', 'label' => 'YouTube URL', 'type' => 'text'],
                ['key' => 'site.x', 'label' => 'X (Twitter) URL', 'type' => 'text'],
                ['key' => 'site.facebook', 'label' => 'Facebook URL', 'type' => 'text'],
                ['key' => 'site.telegram', 'label' => 'Telegram URL', 'type' => 'text'],
                ['key' => 'site.footer_text', 'label' => 'Footer text', 'type' => 'textarea'],
                ['key' => 'site.announcement_text', 'label' => 'Announcement bar text', 'type' => 'textarea', 'help' => 'Shown in the top announcement bar when the announcement bar switch is on.'],
                ['key' => 'site.announcement_link', 'label' => 'Announcement bar link', 'type' => 'url', 'help' => 'Optional. The announcement text links to this URL (e.g. an offers page).'],
            ],
            'features' => [
                ['key' => 'features.registration', 'label' => 'User registration', 'type' => 'boolean', 'help' => 'When OFF, the register page and sign-up links are disabled.'],
                ['key' => 'features.login', 'label' => 'User login', 'type' => 'boolean', 'help' => 'When OFF, customers cannot log in.'],
                ['key' => 'features.email_verification', 'label' => 'Email verification required', 'type' => 'boolean', 'help' => 'New customers must verify their email before using their account.'],
                ['key' => 'features.guest_browsing', 'label' => 'Guest browsing', 'type' => 'boolean', 'help' => 'Allow visitors to browse the store without an account. (Store arrives in Phase 2B.)'],
                ['key' => 'features.guest_cart', 'label' => 'Guest cart', 'type' => 'boolean', 'help' => 'When OFF, visitors must log in before adding anything to the cart. (Phase 2B.)'],
                ['key' => 'features.buy_now', 'label' => 'Buy Now button', 'type' => 'boolean', 'help' => 'One-click purchase straight from the book page. (Phase 2B.)'],
                ['key' => 'features.checkout', 'label' => 'Checkout', 'type' => 'boolean', 'help' => 'Master switch for the whole checkout flow. (Phase 3.)'],
                ['key' => 'features.coupons', 'label' => 'Coupons', 'type' => 'boolean', 'help' => 'Discount codes at cart and checkout. (Phase 4.)'],
                ['key' => 'features.combos', 'label' => 'Combos', 'type' => 'boolean', 'help' => 'Book bundles. (Phase 4.)'],
                ['key' => 'features.sales_offers', 'label' => 'Sales and offers', 'type' => 'boolean', 'help' => 'Sale prices, countdowns and offer banners. (Phase 4.)'],
                ['key' => 'features.announcement_bar', 'label' => 'Announcement bar', 'type' => 'boolean', 'help' => 'Top banner for announcements and offers. (Phase 4.)'],
                ['key' => 'features.cookie_banner', 'label' => 'Cookie banner', 'type' => 'boolean', 'help' => 'Privacy consent banner. (Phase 6.)'],
                ['key' => 'features.contact_form', 'label' => 'Contact form', 'type' => 'boolean', 'help' => 'When OFF, the contact page is hidden. (Phase 8.)'],
                ['key' => 'features.downloads', 'label' => 'Downloads', 'type' => 'boolean', 'help' => 'Customer ebook downloads. (Phase 3.)'],
                ['key' => 'downloads.limit', 'label' => 'Download limit per book', 'type' => 'number', 'help' => 'How many times a buyer can download each ebook per order. Default 5.'],
            ],
            'pricing' => [
                ['key' => 'pricing.inr_enabled', 'label' => 'Enable INR pricing', 'type' => 'boolean', 'help' => 'Prices in rupees for Indian customers.'],
                ['key' => 'pricing.usd_enabled', 'label' => 'Enable USD pricing', 'type' => 'boolean', 'help' => 'Prices in US dollars for international customers.'],
                ['key' => 'pricing.default_region', 'label' => 'Default region for unknown visitors', 'type' => 'select', 'options' => ['IN' => 'India (IN)', 'INTL' => 'International (INTL)'], 'help' => 'Used when the visitor region cannot be detected.'],
            ],
            'payments' => [
                ['key' => 'payments.razorpay_enabled', 'label' => 'Enable Razorpay', 'type' => 'boolean', 'help' => 'Master switch for all Razorpay payments.'],
                ['key' => 'payments.razorpay_mode', 'label' => 'Mode', 'type' => 'select', 'options' => ['test' => 'Test', 'live' => 'Live'], 'help' => 'Test mode shows a warning banner in the admin panel.'],
                ['key' => 'payments.razorpay_key_id', 'label' => 'Razorpay Key ID', 'type' => 'text'],
                ['key' => 'payments.razorpay_key_secret', 'label' => 'Razorpay Key Secret', 'type' => 'secret'],
                ['key' => 'payments.razorpay_webhook_secret', 'label' => 'Webhook Secret', 'type' => 'secret'],
                ['key' => 'payments.inr_enabled', 'label' => 'INR payments', 'type' => 'boolean', 'help' => 'Accept rupee payments through Razorpay.'],
                ['key' => 'payments.usd_enabled', 'label' => 'USD payments', 'type' => 'boolean', 'help' => 'Accept dollar payments (needs Razorpay International Payments).'],
            ],
            'mail' => [
                ['key' => 'mail.host', 'label' => 'SMTP host', 'type' => 'text', 'required' => true],
                ['key' => 'mail.port', 'label' => 'SMTP port', 'type' => 'number', 'required' => true],
                ['key' => 'mail.username', 'label' => 'SMTP username', 'type' => 'text'],
                ['key' => 'mail.password', 'label' => 'SMTP password', 'type' => 'secret'],
                ['key' => 'mail.encryption', 'label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']],
                ['key' => 'mail.from_name', 'label' => 'From name', 'type' => 'text', 'required' => true],
                ['key' => 'mail.from_address', 'label' => 'From email', 'type' => 'email', 'required' => true],
            ],
            'security' => [
                ['key' => 'security.turnstile_enabled', 'label' => 'Enable Cloudflare Turnstile', 'type' => 'boolean', 'help' => 'Bot protection. Tokens are verified server-side; failed checks are rejected.'],
                ['key' => 'security.turnstile_site_key', 'label' => 'Turnstile site key', 'type' => 'text'],
                ['key' => 'security.turnstile_secret_key', 'label' => 'Turnstile secret key', 'type' => 'secret'],
                ['key' => 'security.turnstile_register', 'label' => 'Protect: registration form', 'type' => 'boolean'],
                ['key' => 'security.turnstile_login', 'label' => 'Protect: login form', 'type' => 'boolean'],
                ['key' => 'security.turnstile_forgot', 'label' => 'Protect: forgot password form', 'type' => 'boolean'],
                ['key' => 'security.turnstile_contact', 'label' => 'Protect: contact form', 'type' => 'boolean'],
                ['key' => 'security.turnstile_checkout', 'label' => 'Protect: checkout', 'type' => 'boolean'],
                ['key' => 'security.turnstile_admin_login', 'label' => 'Protect: admin login', 'type' => 'boolean'],
                ['key' => 'security.rate_limit_login', 'label' => 'Login attempts per minute', 'type' => 'number', 'help' => 'Per email + IP. Default 5.'],
                ['key' => 'security.rate_limit_register', 'label' => 'Register attempts per minute', 'type' => 'number', 'help' => 'Per IP. Default 5.'],
                ['key' => 'security.rate_limit_forgot', 'label' => 'Forgot-password attempts per minute', 'type' => 'number', 'help' => 'Per IP. Default 3.'],
                ['key' => 'security.lockout_minutes', 'label' => 'Account lockout (minutes)', 'type' => 'number', 'help' => 'How long a throttled login stays blocked. Default 1 minute per attempt window; raise for stricter lockouts.'],
                ['key' => 'security.require_2fa_admin', 'label' => 'Require 2FA for Super Admin', 'type' => 'boolean', 'help' => 'Super Admins without 2FA are sent to the security page until they enable it.'],
                ['key' => 'security.honeypot_enabled', 'label' => 'Enable honeypot traps', 'type' => 'boolean', 'help' => 'Invisible fields on registration and contact forms. Bots that fill them are silently discarded and logged.'],
                ['key' => 'security.maintenance_enabled', 'label' => 'Maintenance mode', 'type' => 'boolean', 'help' => 'When ON, visitors see a maintenance page. /reddy and payment webhooks stay reachable.'],
                ['key' => 'security.maintenance_message', 'label' => 'Maintenance message', 'type' => 'textarea', 'help' => 'Shown on the maintenance page. Leave empty for the default text.'],
                ['key' => 'security.maintenance_bypass_ips', 'label' => 'Maintenance bypass IPs', 'type' => 'textarea', 'help' => 'One IP address per line. These visitors skip maintenance mode.'],
                ['key' => 'security.cookie_consent_enabled', 'label' => 'Show cookie consent banner', 'type' => 'boolean', 'help' => 'Visitors choose Accept or Decline; the choice is stored for a year. Uses the cookie notice text from the Legal tab.'],
            ],
            'legal' => [
                ['key' => 'legal.privacy_policy', 'label' => 'Privacy policy', 'type' => 'textarea'],
                ['key' => 'legal.no_refund_policy', 'label' => 'No-refund policy', 'type' => 'textarea', 'help' => 'All sales are final because products are digital.'],
                ['key' => 'legal.terms', 'label' => 'Terms of service', 'type' => 'textarea'],
                ['key' => 'legal.cookie_notice', 'label' => 'Cookie notice text', 'type' => 'textarea'],
            ],
            'updates' => [
                ['key' => 'updates.enabled', 'label' => 'Enable updates', 'type' => 'boolean', 'help' => 'Master switch. When OFF, no update checks run and the updater refuses to apply anything.'],
                ['key' => 'updates.repo_owner', 'label' => 'Repository owner', 'type' => 'text', 'help' => 'GitHub username or organisation that owns the repository.'],
                ['key' => 'updates.repo_name', 'label' => 'Repository name', 'type' => 'text', 'help' => 'The repository name, e.g. myebookbazaar.'],
                ['key' => 'updates.branch', 'label' => 'Branch', 'type' => 'text', 'help' => 'Branch to track. Default: main.'],
                ['key' => 'updates.channel', 'label' => 'Update channel', 'type' => 'select', 'options' => ['stable' => 'Stable (release tags only)', 'beta' => 'Beta (includes pre-releases)'], 'help' => 'Stable only offers tagged releases; beta also offers pre-releases.'],
                ['key' => 'updates.github_token', 'label' => 'GitHub fine-grained token', 'type' => 'secret', 'help' => 'Read-only fine-grained personal access token with Contents permission on this repository only. Never shared or logged. Use Test connection after saving.'],
                ['key' => 'updates.auto_check', 'label' => 'Automatic update checks', 'type' => 'boolean', 'help' => 'When ON, the site checks GitHub every 6 hours (via the scheduler cron).'],
                ['key' => 'updates.backup_retention', 'label' => 'Keep this many backups', 'type' => 'number', 'help' => 'Automatic backups prune older entries beyond this count. Default 7.'],
                ['key' => 'auth.google.enabled', 'label' => 'Enable Google sign-in', 'type' => 'boolean', 'help' => 'Master switch. When OFF, the "Continue with Google" buttons are hidden and the OAuth routes refuse to start a flow.'],
                ['key' => 'auth.google.client_id', 'label' => 'Google Client ID', 'type' => 'text', 'help' => 'From Google Cloud Console → APIs & Services → Credentials (OAuth 2.0 Client ID, Web application).'],
                ['key' => 'auth.google.client_secret', 'label' => 'Google Client Secret', 'type' => 'secret', 'help' => 'Shown once when you create the OAuth client. Encrypted at rest, never displayed again. Use Test connection after saving.'],
            ],
        };
    }

    public static function tabLabel(string $tab): string
    {
        return self::tabs()[$tab]['label'] ?? ucfirst($tab);
    }
}
