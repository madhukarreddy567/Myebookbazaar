<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\TotpService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 6: Security & Maintenance.
 *
 * Covers: maintenance mode (middleware + artisan command), Turnstile
 * verification, login hardening, honeypots, admin TOTP 2FA, the security
 * log viewer, and customer privacy controls.
 */
class SecurityPhase6Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    protected function set(string $key, mixed $value): void
    {
        app(SettingsService::class)->set($key, $value);
    }

    protected function makeAdmin(string $role = 'super_admin'): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => 'Password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));
    }

    protected function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Reg User',
            'email' => 'reg-'.uniqid().'@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country_code' => 'IN',
            // Honeypot fields as a real browser would render them.
            'website_url' => '',
            'form_loaded_at' => (string) (time() - 10),
        ], $overrides);
    }

    /** Enable Turnstile for the given forms with faked Cloudflare answers. */
    protected function enableTurnstile(array $forms, bool $verifyOk = true): void
    {
        $this->set('security.turnstile_enabled', '1');
        $this->set('security.turnstile_site_key', 'test-site-key');
        $this->set('security.turnstile_secret_key', 'test-secret-key');
        foreach ($forms as $form) {
            $this->set('security.turnstile_'.$form, '1');
        }

        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response(['success' => $verifyOk], 200),
        ]);
    }

    // ------------------------------------------------------------------
    // Maintenance mode
    // ------------------------------------------------------------------

    public function test_maintenance_mode_blocks_public_pages_with_custom_message(): void
    {
        $this->set('security.maintenance_enabled', '1');
        $this->set('security.maintenance_message', 'Back in 10 minutes!');

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('Back in 10 minutes!');
    }

    public function test_maintenance_mode_leaves_admin_and_webhooks_reachable(): void
    {
        $this->set('security.maintenance_enabled', '1');

        $this->get('/reddy/login')->assertOk();
        $this->post('/webhooks/razorpay', [], ['HTTP_X-Razorpay-Signature' => 'x'])
            ->assertStatus(400); // reached the controller (bad sig), not 503
    }

    public function test_maintenance_mode_bypass_ip(): void
    {
        $this->set('security.maintenance_enabled', '1');
        $this->set('security.maintenance_bypass_ips', "127.0.0.1\n");

        $this->get('/')->assertOk();
    }

    public function test_maintenance_artisan_command_toggles_flag(): void
    {
        $this->artisan('site:maintenance', ['state' => 'on'])->assertSuccessful();
        $this->assertTrue(settings_bool('security.maintenance_enabled'));

        $this->get('/')->assertStatus(503);

        $this->artisan('site:maintenance', ['state' => 'off'])->assertSuccessful();
        $this->assertFalse(settings_bool('security.maintenance_enabled'));

        $this->get('/')->assertOk();
    }

    // ------------------------------------------------------------------
    // Turnstile
    // ------------------------------------------------------------------

    public function test_turnstile_disabled_allows_register_without_token(): void
    {
        $payload = $this->registerPayload();
        $this->post('/register', $payload)->assertRedirect('/verify-email');
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    public function test_turnstile_enabled_rejects_missing_token(): void
    {
        $this->enableTurnstile(['register']);

        $payload = $this->registerPayload();
        $response = $this->post('/register', $payload);
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_turnstile_enabled_rejects_invalid_token(): void
    {
        $this->enableTurnstile(['register'], false);

        $payload = $this->registerPayload(['cf-turnstile-response' => 'bogus-token']);
        $response = $this->post('/register', $payload);
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->assertDatabaseHas('security_logs', ['event' => 'turnstile.failed']);
    }

    public function test_turnstile_enabled_accepts_valid_token(): void
    {
        $this->enableTurnstile(['register'], true);

        $payload = $this->registerPayload(['cf-turnstile-response' => 'valid-token']);
        $this->post('/register', $payload)->assertRedirect('/verify-email');
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    public function test_turnstile_fails_closed_when_cloudflare_unreachable(): void
    {
        $this->set('security.turnstile_enabled', '1');
        $this->set('security.turnstile_site_key', 'test-site-key');
        $this->set('security.turnstile_secret_key', 'test-secret-key');
        $this->set('security.turnstile_register', '1');

        // Cloudflare cannot be reached: the verification throws, and the
        // form must still reject the submission (fail closed, never open).
        Http::fake([
            'https://challenges.cloudflare.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('connection refused');
            },
        ]);

        $payload = $this->registerPayload(['cf-turnstile-response' => 'some-token']);
        $response = $this->post('/register', $payload);
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_turnstile_widget_renders_on_forms_when_enabled(): void
    {
        // Widget hidden when Turnstile is off.
        $this->get('/register')->assertDontSee('cf-turnstile', false);

        $this->enableTurnstile(['register', 'login', 'forgot', 'contact'], true);

        foreach (['/register' => 'register', '/login' => 'login', '/forgot-password' => 'forgot', '/contact' => 'contact'] as $url => $form) {
            $this->get($url)
                ->assertOk()
                ->assertSee('cf-turnstile', false)
                ->assertSee('data-sitekey="test-site-key"', false)
                ->assertSee('data-form="'.$form.'"', false);
        }

        // Admin login widget too.
        $this->set('security.turnstile_admin_login', '1');
        $this->get('/reddy/login')
            ->assertOk()
            ->assertSee('cf-turnstile', false);
    }

    public function test_turnstile_rejects_forgot_password_without_valid_token(): void
    {
        $this->enableTurnstile(['forgot'], false);

        // Missing/invalid token → rejected, no reset email attempted.
        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertSessionHasErrors('cf-turnstile-response');
    }

    public function test_turnstile_accepts_forgot_password_with_valid_token(): void
    {
        $this->enableTurnstile(['forgot'], true);
        $this->makeUser(['email' => 'user@example.com']);

        $this->post('/forgot-password', [
            'email' => 'user@example.com',
            'cf-turnstile-response' => 'valid-token',
        ])->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------------
    // Login hardening
    // ------------------------------------------------------------------

    public function test_failed_login_is_logged_and_response_does_not_enumerate(): void
    {
        $user = $this->makeUser();

        $wrong = $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123']);
        $unknown = $this->post('/login', ['email' => 'nobody-'.uniqid().'@example.com', 'password' => 'WrongPass123']);

        $wrong->assertSessionHasErrors('email');
        $unknown->assertSessionHasErrors('email');
        // Identical message for unknown email and wrong password.
        $this->assertSame(
            $wrong->getSession()->get('errors')->get('email'),
            $unknown->getSession()->get('errors')->get('email')
        );
        $this->assertDatabaseHas('security_logs', ['event' => 'login.failed']);
    }

    public function test_login_lockout_uses_settings_limit_and_is_logged(): void
    {
        $this->set('security.rate_limit_login', '2');
        $this->set('security.lockout_minutes', '15');
        $user = $this->makeUser();

        // 3rd attempt trips the lockout (limit 2 per minute).
        $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            $response->getSession()->get('errors')->first('email')
        );
        $this->assertDatabaseHas('security_logs', ['event' => 'login.lockout']);
    }

    public function test_admin_failed_login_is_logged(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/reddy/login', ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertDatabaseHas('security_logs', ['event' => 'login.failed']);
    }

    // ------------------------------------------------------------------
    // Honeypots
    // ------------------------------------------------------------------

    public function test_honeypot_filled_field_silently_discards_registration(): void
    {
        $payload = $this->registerPayload(['website_url' => 'http://spam.example']);

        $response = $this->post('/register', $payload);
        // Generic success — the bot is never told it was caught.
        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->assertDatabaseHas('security_logs', ['event' => 'honeypot.hit']);
    }

    public function test_honeypot_fast_submit_is_silently_discarded(): void
    {
        $payload = $this->registerPayload(['form_loaded_at' => (string) time()]);

        $this->post('/register', $payload)->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->assertDatabaseHas('security_logs', ['event' => 'honeypot.hit']);
    }

    public function test_honeypot_passes_for_humans(): void
    {
        $payload = $this->registerPayload();
        $this->post('/register', $payload)->assertRedirect('/verify-email');
        $this->assertDatabaseHas('users', ['email' => $payload['email']]);
    }

    // ------------------------------------------------------------------
    // Admin TOTP 2FA
    // ------------------------------------------------------------------

    protected function enable2fa(Admin $admin): array
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $recovery = $totp->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => $recovery['hashed'],
        ])->save();

        return ['secret' => $secret, 'recovery_plain' => $recovery['plain']];
    }

    public function test_2fa_setup_flow_enables_with_valid_code(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');

        $this->get('/reddy/account/security/2fa')->assertOk()->assertSee('Scan this QR code', false);

        $secret = session('two_factor_setup_secret');
        $this->assertNotEmpty($secret);

        $code = app(TotpService::class)->currentCode($secret);
        $response = $this->post('/reddy/account/security/2fa', ['code' => $code]);

        $response->assertOk();
        $response->assertSee('recovery codes', false);
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('security_logs', ['event' => '2fa.enabled']);

        // The raw secret is encrypted in the database.
        $raw = \DB::table('admins')->where('id', $admin->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertSame($secret, $admin->fresh()->two_factor_secret);
    }

    public function test_2fa_setup_rejects_wrong_code(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');

        $this->get('/reddy/account/security/2fa')->assertOk();
        $this->post('/reddy/account/security/2fa', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_2fa_login_challenge_flow(): void
    {
        $admin = $this->makeAdmin();
        ['secret' => $secret] = $this->enable2fa($admin);

        // Correct password → challenge, NOT authenticated yet.
        $response = $this->post('/reddy/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('/reddy/2fa/challenge');
        $this->assertGuest('admin');

        $this->get('/reddy/2fa/challenge')->assertOk();

        // Wrong code → error, still guest, logged.
        $this->post('/reddy/2fa/challenge', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');
        $this->assertDatabaseHas('security_logs', ['event' => '2fa.challenge_failed']);

        // Right code → fully signed in.
        $code = app(TotpService::class)->currentCode($secret);
        $this->post('/reddy/2fa/challenge', ['code' => $code])
            ->assertRedirect('/reddy/dashboard');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_2fa_recovery_code_is_single_use(): void
    {
        $admin = $this->makeAdmin();
        ['recovery_plain' => $plain] = $this->enable2fa($admin);
        $code = $plain[0];

        // First login via recovery code.
        $this->post('/reddy/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect('/reddy/2fa/challenge');
        $this->post('/reddy/2fa/challenge', ['code' => $code])
            ->assertRedirect('/reddy/dashboard');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertDatabaseHas('security_logs', ['event' => '2fa.recovery_used']);

        // Sign out; the same code must NOT work twice.
        $this->post('/reddy/logout');
        $this->post('/reddy/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect('/reddy/2fa/challenge');
        $this->post('/reddy/2fa/challenge', ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_2fa_disable_requires_password(): void
    {
        $admin = $this->makeAdmin();
        $this->enable2fa($admin);
        $this->actingAs($admin, 'admin');

        $this->delete('/reddy/account/security/2fa', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());

        $this->delete('/reddy/account/security/2fa', ['password' => 'password123'])
            ->assertRedirect('/reddy/account/security');
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('security_logs', ['event' => '2fa.disabled']);
    }

    public function test_require_2fa_forces_super_admin_to_setup(): void
    {
        $this->set('security.require_2fa_admin', '1');
        $admin = $this->makeAdmin('super_admin');
        $this->actingAs($admin, 'admin');

        $this->get('/reddy/dashboard')->assertRedirect('/reddy/account/security/2fa');
        // Setup pages stay reachable.
        $this->get('/reddy/account/security/2fa')->assertOk();

        // Staff without 2FA are NOT forced.
        $staff = $this->makeAdmin('staff');
        $this->actingAs($staff, 'admin');
        $this->get('/reddy/dashboard')->assertOk();
    }

    // ------------------------------------------------------------------
    // Security log viewer
    // ------------------------------------------------------------------

    public function test_security_log_is_super_admin_only(): void
    {
        SecurityLog::create(['event' => 'test.event', 'message' => 'hello']);

        $this->get('/reddy/security-log')->assertRedirect('/reddy/login');

        $staff = $this->makeAdmin('staff');
        $this->actingAs($staff, 'admin');
        $this->get('/reddy/security-log')->assertForbidden();

        $super = $this->makeAdmin('super_admin');
        $this->actingAs($super, 'admin');
        $this->get('/reddy/security-log')->assertOk()->assertSee('test.event');
    }

    public function test_security_log_filter_by_event(): void
    {
        $super = $this->makeAdmin('super_admin');
        $this->actingAs($super, 'admin');

        SecurityLog::create(['event' => 'login.failed', 'message' => 'nope']);
        SecurityLog::create(['event' => 'honeypot.hit', 'message' => 'bot']);

        $response = $this->get('/reddy/security-log?event=login.failed');
        $response->assertOk();
        $response->assertSee('login.failed');
        $response->assertDontSee('honeypot.hit');
    }

    // ------------------------------------------------------------------
    // Privacy controls
    // ------------------------------------------------------------------

    public function test_blocked_customer_gets_identical_failure_response(): void
    {
        $user = $this->makeUser(['is_blocked' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        // Identical to a wrong-password failure — blocked status is not enumerable.
        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);
        $this->assertGuest();
    }

    public function test_disabled_admin_gets_identical_failure_response(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['is_active' => false])->save();

        $response = $this->post('/reddy/login', [
            'email' => $admin->email,
            'password' => 'Password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('auth.failed'),
        ]);
        $this->assertGuest('admin');
    }

    public function test_customer_data_export_contains_profile_orders_downloads(): void
    {
        $user = $this->makeUser();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        $response = $this->get('/account/export');
        $response->assertOk();
        $data = $response->json();

        $this->assertSame($user->email, $data['profile']['email']);
        $this->assertArrayHasKey('orders', $data);
        $this->assertArrayHasKey('downloads', $data);
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition')
        );
        $this->assertDatabaseHas('security_logs', ['event' => 'privacy.export']);
    }

    public function test_customer_delete_anonymizes_but_keeps_orders(): void
    {
        $user = $this->makeUser();
        $user->markEmailAsVerified();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.uniqid(),
            'currency' => 'INR',
            'subtotal' => 149,
            'grand_total' => 149,
            'status' => 'paid',
        ]);

        $this->actingAs($user);

        // Wrong password / confirmation are refused.
        $this->delete('/account', ['password' => 'nope', 'confirmation' => 'DELETE'])
            ->assertSessionHasErrors('password');
        $this->delete('/account', ['password' => 'Password123', 'confirmation' => 'nope'])
            ->assertSessionHasErrors('confirmation');

        $this->delete('/account', ['password' => 'Password123', 'confirmation' => 'DELETE'])
            ->assertRedirect('/');

        $fresh = $user->fresh();
        $this->assertSame('Deleted user', $fresh->name);
        $this->assertStringStartsWith('deleted-', $fresh->email);
        $this->assertTrue($fresh->is_blocked);
        $this->assertGuest();

        // Tax records survive.
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('security_logs', ['event' => 'privacy.account_deleted']);
    }

    public function test_cookie_consent_banner_shows_only_when_enabled(): void
    {
        // Disabled: no banner.
        $this->get('/')->assertDontSee('cookie-consent-banner', false);

        // Enabled: banner appears for a first-time visitor.
        $this->set('security.cookie_consent_enabled', '1');
        $this->get('/')->assertSee('cookie-consent-banner', false);

        // Visitor already chose: banner gone.
        $this->withCookie('meb_cookie_consent', 'declined')
            ->get('/')->assertDontSee('cookie-consent-banner', false);
    }

    // ------------------------------------------------------------------
    // Webhook signature logging
    // ------------------------------------------------------------------

    public function test_bad_webhook_signature_is_security_logged(): void
    {
        $this->post('/webhooks/razorpay', [], ['HTTP_X-Razorpay-Signature' => 'bogus'])
            ->assertStatus(400);
        $this->assertDatabaseHas('security_logs', ['event' => 'webhook.bad_signature']);
    }

    // ------------------------------------------------------------------
    // Integrations verification: maintenance toggle, Turnstile on every
    // form, and route rate limits
    // ------------------------------------------------------------------

    public function test_admin_settings_toggle_flips_maintenance_mode(): void
    {
        $admin = $this->makeAdmin('super_admin');
        $this->actingAs($admin, 'admin');

        // Toggle ON through the same form the Settings Center renders.
        $this->put('/reddy/settings', [
            'tab' => 'security',
            's' => ['security' => ['maintenance_enabled' => '1']],
        ])->assertRedirect();
        $this->assertTrue(settings_bool('security.maintenance_enabled'));

        $this->get('/')->assertStatus(503)->assertViewIs('errors.503');

        // Log the admin back out so /reddy/login is hit as a guest —
        // it must stay reachable (200), never a 503.
        $this->post(route('reddy.logout'));
        $this->get('/reddy/login')->assertOk();

        // An unchecked checkbox is absent from the request => OFF.
        $this->actingAs($admin, 'admin');
        $this->put('/reddy/settings', ['tab' => 'security'])->assertRedirect();
        $this->assertFalse(settings_bool('security.maintenance_enabled'));

        $this->post(route('reddy.logout'));
        $this->get('/')->assertOk();
    }

    public function test_maintenance_page_is_branded_503_view_with_custom_message(): void
    {
        $this->set('security.maintenance_enabled', '1');
        $this->set('security.maintenance_message', 'Back in 10 minutes!');

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertViewIs('errors.503');
        $response->assertSee('Back in 10 minutes!');
        $response->assertSee('We are doing quick maintenance');
    }

    public function test_turnstile_widget_hidden_when_keys_missing_but_enabled(): void
    {
        // Master switch ON but no keys saved => widget must not render.
        $this->set('security.turnstile_enabled', '1');
        $this->set('security.turnstile_site_key', null);
        $this->set('security.turnstile_secret_key', null);
        $this->set('security.turnstile_register', '1');
        $this->set('security.turnstile_login', '1');

        $this->get('/register')->assertOk()->assertDontSee('cf-turnstile', false);
        $this->get('/login')->assertOk()->assertDontSee('cf-turnstile', false);
    }

    public function test_turnstile_rejects_login_without_valid_token(): void
    {
        $this->enableTurnstile(['login'], false);
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    public function test_turnstile_accepts_login_with_valid_token(): void
    {
        $this->enableTurnstile(['login'], true);
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123',
            'cf-turnstile-response' => 'valid-token',
        ]);

        // Email verification is on by default, so the login lands on the
        // verification notice — proving the Turnstile check passed.
        $response->assertRedirect('/verify-email');
        $this->assertAuthenticated();
    }

    public function test_turnstile_disabled_login_works_without_token(): void
    {
        // Keys not configured => verification is skipped, form still works.
        $user = $this->makeUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertRedirect('/verify-email');
        $this->assertAuthenticated();
    }

    public function test_turnstile_rejects_contact_without_valid_token(): void
    {
        Mail::fake();
        $this->enableTurnstile(['contact'], false);

        $response = $this->post('/contact', [
            'name' => 'A Reader',
            'email' => 'reader@example.com',
            'subject' => 'A question',
            'message' => 'Hello, I have a question about the book.',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        Mail::assertNothingSent();
    }

    public function test_turnstile_accepts_contact_with_valid_token(): void
    {
        Mail::fake();
        $this->enableTurnstile(['contact'], true);

        $response = $this->post('/contact', [
            'name' => 'A Reader',
            'email' => 'reader@example.com',
            'subject' => 'A question',
            'message' => 'Hello, I have a question about the book.',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        Mail::assertSent(\App\Mail\ContactMessageMail::class);
    }

    public function test_turnstile_rejects_checkout_without_valid_token(): void
    {
        $this->enableTurnstile(['checkout'], false);
        $user = $this->makeUser();
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'cf_turnstile_response' => 'bogus-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cf-turnstile-response');
    }

    public function test_turnstile_passes_checkout_with_valid_token(): void
    {
        $this->enableTurnstile(['checkout'], true);
        $user = $this->makeUser();
        $user->forceFill(['email_verified_at' => now()])->save();

        // Empty cart: Turnstile passes, then the controller reports the
        // empty cart — proving verification did not block the request.
        $response = $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'cf_turnstile_response' => 'valid-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Your cart is empty.']);
    }

    public function test_login_throttle_returns_429_after_five_attempts(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123']);
        }

        // The 6th attempt inside the minute window is refused by the
        // throttle:5,1 middleware before the controller runs.
        $this->post('/login', ['email' => $user->email, 'password' => 'WrongPass123'])
            ->assertStatus(429);
    }

    public function test_register_throttle_returns_429_after_five_attempts(): void
    {
        // Successful registrations log the user in, so log back out
        // between attempts — every POST must reach the throttle:5,1
        // route middleware as a guest.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $this->registerPayload())->assertRedirect('/verify-email');
            $this->post('/logout')->assertRedirect();
        }

        // The 6th attempt inside the minute window is refused with 429.
        $this->post('/register', $this->registerPayload())
            ->assertStatus(429);
    }
}
