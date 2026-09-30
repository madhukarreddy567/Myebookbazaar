<?php

namespace Tests\Feature;

use App\Mail\SettingsTestMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 2A: Settings Center.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test` (SQLite is
 * forbidden by the project rules); every test rolls back, and the
 * settings cache is cleared between tests because the array cache store
 * is process-global while the database is transactional.
 */
class SettingsTest extends TestCase
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
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // Access control
    // ------------------------------------------------------------------

    public function test_guest_is_redirected_from_settings(): void
    {
        $this->get('/reddy/settings')->assertRedirect('/reddy/login');
    }

    public function test_customer_cannot_access_settings(): void
    {
        $this->actingAs($this->makeUser(), 'web');

        $this->get('/reddy/settings')->assertRedirect('/reddy/login');
    }

    public function test_staff_admin_gets_403_on_settings(): void
    {
        $this->actingAs($this->makeAdmin('staff'), 'admin');

        $this->get('/reddy/settings')->assertForbidden();
        $this->put('/reddy/settings', ['tab' => 'general'])->assertForbidden();
    }

    public function test_super_admin_can_view_settings(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $response = $this->get('/reddy/settings');

        $response->assertOk();
        $response->assertSee('Settings Center');
        $response->assertSee('Feature switches');
        $response->assertSee('Updates and integrations');
    }

    public function test_sidebar_settings_link_is_active_route(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $this->get('/reddy/settings')->assertOk();
        $this->assertTrue(app('router')->has('reddy.settings'));
    }

    // ------------------------------------------------------------------
    // Secrets: encryption + masking
    // ------------------------------------------------------------------

    public function test_secret_is_encrypted_in_database(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $plain = 'sk_test_secretvalue123';

        $this->put('/reddy/settings', [
            'tab' => 'payments',
            'secret_payments_razorpay_key_secret' => $plain,
        ])->assertRedirect();

        $stored = Setting::where('key', 'payments.razorpay_key_secret')->firstOrFail()->value;

        $this->assertNotSame($plain, $stored, 'Secret must not be stored as plaintext');
        $this->assertSame($plain, Crypt::decryptString($stored));
        $this->assertSame($plain, settings('payments.razorpay_key_secret'));
    }

    public function test_secret_is_masked_on_screen_never_shown(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $plain = 'sk_test_secretvalue123';
        app(SettingsService::class)->set('payments.razorpay_key_secret', $plain);

        $response = $this->get('/reddy/settings?tab=payments');

        $response->assertOk();
        $response->assertSee('••••••••'.substr($plain, -4));
        $response->assertDontSee($plain);
    }

    public function test_empty_secret_shows_not_set(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        Setting::where('key', 'security.turnstile_secret_key')->update(['value' => null]);
        app(SettingsService::class)->forgetCache();

        $this->get('/reddy/settings?tab=security')->assertSee('Not set');
    }

    // ------------------------------------------------------------------
    // Audit log
    // ------------------------------------------------------------------

    public function test_settings_changes_are_audited_without_secrets(): void
    {
        $this->actingAs($admin = $this->makeAdmin('super_admin'), 'admin');

        $this->put('/reddy/settings', [
            'tab' => 'general',
            's' => ['site' => ['name' => 'New Store Name', 'support_email' => 'support@example.com']],
        ])->assertRedirect();

        $log = AuditLog::where('action', 'settings.updated')->latest('id')->firstOrFail();

        $this->assertSame($admin->id, $log->admin_id);
        $this->assertSame('MyEbookBazaar', $log->old_values['site.name']);
        $this->assertSame('New Store Name', $log->new_values['site.name']);
        $this->assertNotNull($log->ip_address);

        // Now a secret change: values must never appear in the log.
        $this->put('/reddy/settings', [
            'tab' => 'payments',
            'secret_payments_razorpay_key_secret' => 'sk_live_supersecret',
        ])->assertRedirect();

        $log = AuditLog::where('action', 'settings.updated')->latest('id')->firstOrFail();
        // The scrubber replaces any secret-looking value with a placeholder.
        $this->assertSame('***redacted***', $log->new_values['payments.razorpay_key_secret']);
        $this->assertStringNotContainsString('sk_live_supersecret', json_encode($log));
    }

    // ------------------------------------------------------------------
    // Feature switches change behaviour
    // ------------------------------------------------------------------

    public function test_registration_switch_blocks_routes_with_friendly_message(): void
    {
        // ON by default.
        $this->get('/register')->assertOk();

        // Turn it OFF.
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');
        $this->put('/reddy/settings', ['tab' => 'features'])->assertRedirect();
        $this->assertFalse(settings_enabled('features.registration'));

        $this->get('/register')
            ->assertForbidden()
            ->assertSee('currently unavailable');
        $this->post('/register', [])->assertForbidden();

        // Turn it back ON.
        $this->put('/reddy/settings', [
            'tab' => 'features',
            's' => ['features' => ['registration' => '1']],
        ])->assertRedirect();

        $this->get('/register')->assertOk();
    }

    public function test_login_switch_blocks_routes(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');
        $this->put('/reddy/settings', [
            'tab' => 'features',
            's' => ['features' => ['login' => '0', 'registration' => '1']],
        ])->assertRedirect();

        // Booleans absent from the request are OFF; explicit '0' is OFF too.
        $this->assertFalse(settings_enabled('features.login'));

        $this->get('/login')->assertForbidden();
        $this->post('/login', [])->assertForbidden();
    }

    public function test_email_verification_switch_off_skips_verification(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');
        $this->put('/reddy/settings', [
            'tab' => 'features',
            's' => ['features' => ['email_verification' => '0']],
        ])->assertRedirect();

        $user = $this->makeUser(); // email_verified_at is null
        $this->assertNull($user->email_verified_at);

        $this->actingAs($user, 'web');

        // With verification OFF, the account page opens directly.
        $this->get('/account')->assertOk();
    }

    public function test_email_verification_switch_on_requires_verification(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');

        $this->assertTrue(settings_bool('features.email_verification', true));
        $this->get('/account')->assertRedirect('/verify-email');
    }

    // ------------------------------------------------------------------
    // Test email button
    // ------------------------------------------------------------------

    public function test_test_email_button_sends_and_reports_success(): void
    {
        Mail::fake();
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $response = $this->postJson('/reddy/settings/test-email', [
            'email' => 'tester@example.com',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        Mail::assertSent(SettingsTestMail::class, function ($mail) {
            return $mail->hasTo('tester@example.com');
        });
    }

    public function test_test_email_button_reports_the_exact_error(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        app(SettingsService::class)->set('mail.host', '127.0.0.1');
        app(SettingsService::class)->set('mail.port', '1'); // refused immediately

        $response = $this->postJson('/reddy/settings/test-email', [
            'email' => 'tester@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['ok' => false]);
        $this->assertStringContainsString('Could not send', $response->json('message'));
    }

    public function test_test_email_requires_super_admin(): void
    {
        $this->actingAs($this->makeAdmin('staff'), 'admin');

        $this->postJson('/reddy/settings/test-email', ['email' => 'x@example.com'])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Health panel, cache, fallback
    // ------------------------------------------------------------------

    public function test_health_panel_lists_missing_configuration(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        // mail.host has no default in the seeder.
        $response = $this->get('/reddy/settings');

        $response->assertOk();
        $response->assertSee('SMTP is not configured');
    }

    public function test_settings_cache_clears_on_save(): void
    {
        $service = app(SettingsService::class);

        $this->assertSame('MyEbookBazaar', $service->get('site.name'));

        $this->actingAs($this->makeAdmin('super_admin'), 'admin');
        $this->put('/reddy/settings', [
            'tab' => 'general',
            's' => ['site' => ['name' => 'Cached Name', 'support_email' => 'support@example.com']],
        ])->assertRedirect();

        // A fresh resolution must see the new value (no stale cache).
        $this->assertSame('Cached Name', app(SettingsService::class)->get('site.name'));
        $this->assertSame('Cached Name', settings('site.name'));
    }

    public function test_missing_key_falls_back_to_default(): void
    {
        Setting::where('key', 'site.tagline')->delete();
        app(SettingsService::class)->forgetCache();

        $this->assertSame('fallback', settings('site.tagline', 'fallback'));
    }

    public function test_country_region_override_is_stored(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $this->put('/reddy/settings', [
            'tab' => 'pricing',
            'regions' => ['US' => 'IN', 'DE' => 'INTL'],
        ])->assertRedirect();

        // US overridden to IN; DE equals its default so it is not stored.
        $this->assertSame('IN', app(SettingsService::class)->regionForCountry('US'));
        $this->assertSame('INTL', app(SettingsService::class)->regionForCountry('DE'));
        $this->assertSame('IN', app(SettingsService::class)->regionForCountry('IN'));
    }

    public function test_registration_uses_country_region_override(): void
    {
        // Admin moves the US into the IN pricing region.
        app(SettingsService::class)->set('pricing.country_regions', json_encode(['US' => 'IN']));

        $email = 'region-'.uniqid().'@example.com';

        // Email verification is ON by default, so a successful signup
        // redirects to the verification notice.
        $this->post('/register', [
            'name' => 'Region User',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country_code' => 'US',
        ])->assertRedirect('/verify-email');

        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('US', $user->country_code);
        $this->assertSame('IN', $user->pricing_region);
    }

    public function test_customer_layout_receives_feature_flags(): void
    {
        // Capture the data the AppServiceProvider composer attaches to the
        // customer layout when the homepage renders.
        $seen = [];
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) use (&$seen) {
            $seen = $view->getData();
        });

        $this->get('/')->assertOk();

        $this->assertTrue($seen['featuresRegistrationEnabled'] ?? false);
        $this->assertTrue($seen['featuresLoginEnabled'] ?? false);
    }

    public function test_runtime_mail_config_uses_settings_center_values(): void
    {
        $original = config('mail.mailers.smtp');

        app(SettingsService::class)->set('mail.host', 'smtp.example.com');
        app(SettingsService::class)->set('mail.port', '2525');

        $provider = new \App\Providers\AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'applyMailSettings');
        $method->invoke($provider);

        try {
            $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
            $this->assertSame(2525, config('mail.mailers.smtp.port'));
        } finally {
            config(['mail.mailers.smtp' => $original]);
        }
    }

    public function test_runtime_mail_config_falls_back_to_env_when_unset(): void
    {
        // mail.host has no value in the seeder, so the .env default survives.
        $this->assertNull(settings('mail.host'));

        $provider = new \App\Providers\AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'applyMailSettings');
        $method->invoke($provider);

        $this->assertSame('127.0.0.1', config('mail.mailers.smtp.host'));
    }

    public function test_image_upload_stores_file_and_removal_deletes(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        $base = [
            'tab' => 'general',
            's' => ['site' => ['name' => 'Test', 'support_email' => 'support@example.com']],
        ];

        $this->put('/reddy/settings', $base + [
            'img_site_logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect();

        $path = settings('site.logo');
        $this->assertNotNull($path);
        $this->assertStringStartsWith('uploads/settings/', $path);
        $this->assertFileExists(public_path($path));

        $this->put('/reddy/settings', $base + ['remove_img_site_logo' => '1'])
            ->assertRedirect();

        $this->assertNull(settings('site.logo'));
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_razorpay_test_reports_configuration_status(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        // No credentials configured yet.
        $this->postJson('/reddy/settings/test-razorpay')
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        app(SettingsService::class)->set('payments.razorpay_enabled', '1');
        app(SettingsService::class)->set('payments.razorpay_key_id', 'rzp_test_123');
        app(SettingsService::class)->set('payments.razorpay_key_secret', 'secret123');

        // The connection probe hits api.razorpay.com — fake it. Note: calling
        // Http::fake() twice keeps the FIRST stub, so one closure serves
        // both attempts (200 = keys accepted, then 401 = keys rejected).
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return $attempts === 1
                ? Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200)
                : Http::response(['error' => ['code' => 'BAD_REQUEST_ERROR']], 401);
        });

        // Test mode is the seeder default.
        $this->postJson('/reddy/settings/test-razorpay')
            ->assertOk()
            ->assertJson(['ok' => true]);

        // Rejected keys are reported honestly (HTTP 422, ok=false).
        $this->postJson('/reddy/settings/test-razorpay')
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        // The probe genuinely attempted a real HTTP request to the Razorpay
        // API (not just local validation) — two attempts were made above.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.razorpay.com/v1/orders');
        });
        $this->assertSame(2, $attempts);
    }

    public function test_all_settings_tabs_render_without_errors(): void
    {
        $this->actingAs($this->makeAdmin('super_admin'), 'admin');

        foreach (['general', 'features', 'pricing', 'payments', 'mail', 'security', 'legal', 'updates'] as $tab) {
            $this->get('/reddy/settings?tab='.$tab)->assertOk();
        }
    }
}
