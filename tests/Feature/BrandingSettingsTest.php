<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\RemoveBgService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Branding (favicon/logo), footer content settings, and the optional
 * remove.bg background-removal integration.
 *
 * - site.logo / site.favicon keys already existed; these tests pin the
 *   upload wiring, the layout rendering, and the new keys
 *   (site.remove_bg_api_key, site.copyright_text).
 * - The remove.bg API key is never exposed in views, logs or audit rows.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test` (SQLite is forbidden by the project rules).
 */
class BrandingSettingsTest extends TestCase
{
    use DatabaseTransactions;

    /** Uploaded branding files created during the test (cleaned up). */
    protected array $uploadedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->uploadedFiles as $relative) {
            $full = public_path($relative);
            if (is_string($relative) && is_file($full)) {
                @unlink($full);
            }
        }
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Brand Admin',
            'email' => 'brand-admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
    }

    protected function asAdmin(): self
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        return $this;
    }

    /**
     * GET a public storefront URL as a guest visitor.
     *
     * actingAs($admin, 'admin') switches Laravel's default guard, which would
     * leak the Admin model into auth()->user() on storefront controllers.
     * Real visitors never hit that path; reset the guard to web first.
     */
    protected function getStorefront(string $uri)
    {
        $this->app['auth']->shouldUse('web');

        return $this->get($uri);
    }

    protected function settings(): SettingsService
    {
        return app(SettingsService::class);
    }

    /**
     * PUT to the general tab. The tab requires site.name and
     * site.support_email, so every general-tab submission carries them
     * (like the real form does).
     */
    protected function putGeneral(array $extra = [])
    {
        return $this->put('/reddy/settings', array_merge([
            'tab' => 'general',
            's' => ['site' => [
                'name' => 'MyEbookBazaar',
                'support_email' => 'support@myebookbazaar.com',
            ]],
        ], $extra));
    }

    protected function trackUploaded(string $key): void
    {
        $path = $this->settings()->get($key);
        if (is_string($path) && $path !== '') {
            $this->uploadedFiles[] = $path;
        }
    }

    // ------------------------------------------------------------------
    // Favicon + logo upload & layout wiring
    // ------------------------------------------------------------------

    public function test_admin_can_upload_logo_png_and_it_renders_in_layouts(): void
    {
        $this->asAdmin();

        $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect();

        $path = $this->settings()->get('site.logo');
        $this->trackUploaded('site.logo');
        $this->assertNotEmpty($path);
        $this->assertFileExists(public_path($path));

        // Public layout: header/footer use the uploaded logo.
        $html = $this->getStorefront('/')->assertOk()->getContent();
        $this->assertStringContainsString(asset($path), $html);

        // Admin layout: sidebar brand uses the uploaded logo.
        $adminHtml = $this->get('/reddy/settings')->assertOk()->getContent();
        $this->assertStringContainsString(asset($path), $adminHtml);
    }

    public function test_admin_can_upload_ico_favicon_and_it_renders_in_head(): void
    {
        $this->asAdmin();

        $this->putGeneral([
            'img_site_favicon' => UploadedFile::fake()->create('favicon.ico', 10, 'image/x-icon'),
        ])->assertRedirect();

        $path = $this->settings()->get('site.favicon');
        $this->trackUploaded('site.favicon');
        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.ico', $path);
        $this->assertFileExists(public_path($path));

        $html = $this->getStorefront('/')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="icon" href="'.asset($path).'"', $html);
        $this->assertStringContainsString('type="image/x-icon"', $html);

        $adminHtml = $this->get('/reddy/settings')->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="icon" href="'.asset($path).'"', $adminHtml);
    }

    public function test_logo_upload_is_never_converted_to_jpeg(): void
    {
        $this->asAdmin();

        $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertRedirect();

        $path = $this->settings()->get('site.logo');
        $this->trackUploaded('site.logo');

        $this->assertStringEndsWith('.png', $path, 'Transparency must be preserved — no JPEG flattening.');
        $this->assertStringNotContainsString('.jpg', (string) $path);
        $this->assertStringNotContainsString('.jpeg', (string) $path);
    }

    public function test_oversized_branding_upload_is_rejected(): void
    {
        $this->asAdmin();

        $response = $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('huge.png')->size(3000), // > 2 MB cap
        ]);

        $response->assertSessionHasErrors('img_site_logo');
        $this->assertNull($this->settings()->get('site.logo'));
    }

    public function test_branding_uploads_are_audited(): void
    {
        $this->asAdmin();

        $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertRedirect();

        $this->trackUploaded('site.logo');
        $this->assertTrue(
            AuditLog::where('action', 'settings.updated')->exists(),
            'Branding uploads must be audited like other settings changes.'
        );
    }

    // ------------------------------------------------------------------
    // Footer / contact / copyright settings
    // ------------------------------------------------------------------

    public function test_footer_renders_contact_social_and_copyright_from_settings(): void
    {
        $s = $this->settings();
        $s->set('site.support_email', 'hello@example.com');
        $s->set('site.phone', '+91 98765 43210');
        $s->set('site.address', '1 MG Road, Bengaluru');
        $s->set('site.facebook', 'https://facebook.com/myebookbazaar');
        $s->set('site.x', 'https://x.com/myebookbazaar');
        $s->set('site.copyright_text', '© 2026 Test Store. All rights reserved.');

        $html = $this->getStorefront('/')->assertOk()->getContent();

        $this->assertStringContainsString('mailto:hello@example.com', $html);
        $this->assertStringContainsString('+91 98765 43210', $html);
        $this->assertStringContainsString('1 MG Road, Bengaluru', $html);
        $this->assertStringContainsString('https://facebook.com/myebookbazaar', $html);
        $this->assertStringContainsString('https://x.com/myebookbazaar', $html);
        $this->assertStringContainsString('© 2026 Test Store. All rights reserved.', $html);
    }

    public function test_footer_falls_back_sensibly_when_settings_empty(): void
    {
        $s = $this->settings();
        foreach (['site.phone', 'site.address', 'site.facebook', 'site.instagram', 'site.x', 'site.youtube', 'site.copyright_text'] as $key) {
            $s->set($key, null);
        }

        $html = $this->getStorefront('/')->assertOk()->getContent();

        // No phone/address/social markup when unset; default copyright remains.
        $this->assertStringNotContainsString('tel:', $html);
        $this->assertStringContainsString('© '.date('Y').' MyEbookBazaar. All rights reserved.', $html);
        $this->assertStringContainsString('support@myebookbazaar.com', $html);
    }

    public function test_copyright_text_is_editable_from_settings_ui(): void
    {
        $this->asAdmin();

        $this->putGeneral([
            's' => ['site' => [
                'name' => 'MyEbookBazaar',
                'support_email' => 'support@myebookbazaar.com',
                'copyright_text' => 'Custom © line here',
            ]],
        ])->assertRedirect();

        $this->assertSame('Custom © line here', $this->settings()->get('site.copyright_text'));
        $this->assertStringContainsString('Custom © line here', $this->getStorefront('/')->getContent());
    }

    // ------------------------------------------------------------------
    // remove.bg integration
    // ------------------------------------------------------------------

    protected function pngBytes(): string
    {
        // Minimal valid PNG header + payload; the service only checks the magic bytes.
        return "\x89PNG\r\n\x1a\nfakepngpayload";
    }

    public function test_logo_background_removed_when_key_configured(): void
    {
        Http::fake(['api.remove.bg/*' => Http::response($this->pngBytes(), 200, ['Content-Type' => 'image/png'])]);
        $this->asAdmin();
        $this->settings()->set('site.remove_bg_api_key', 'test-key-123');

        $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.jpg', 100, 100),
        ])->assertRedirect();

        Http::assertSent(function ($request) {
            return $request->url() === RemoveBgService::ENDPOINT
                && $request->header('X-Api-Key') === ['test-key-123'];
        });

        $path = $this->settings()->get('site.logo');
        $this->trackUploaded('site.logo');

        $this->assertStringEndsWith('.png', $path, 'remove.bg output is a transparent PNG.');
        $stored = file_get_contents(public_path($path));
        $this->assertTrue(str_starts_with($stored, "\x89PNG"));

        $logged = AuditLog::where('action', 'settings.logo_background')->latest('id')->first();
        $this->assertNotNull($logged, 'Background-removal outcome must be audited.');
        $this->assertSame('applied', $logged->new_values['result'] ?? null);
        $this->assertStringNotContainsString('test-key-123', json_encode($logged->new_values), 'Audit must never contain the API key.');
    }

    public function test_logo_upload_kept_on_removebg_failure_with_warning(): void
    {
        Http::fake(['api.remove.bg/*' => Http::response('bad key', 401)]);
        $this->asAdmin();
        $this->settings()->set('site.remove_bg_api_key', 'bad-key');

        $response = $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.jpg', 100, 100),
        ]);

        $response->assertRedirect();
        // The original upload is kept (never breaks), warning is flashed.
        $response->assertSessionHas('warning');

        $path = $this->settings()->get('site.logo');
        $this->trackUploaded('site.logo');
        $this->assertStringEndsWith('.jpg', $path, 'On API failure the original upload must be kept.');
        $this->assertFileExists(public_path($path));
    }

    public function test_no_removebg_call_without_api_key(): void
    {
        Http::fake();
        $this->asAdmin();
        $this->settings()->set('site.remove_bg_api_key', null);

        $this->putGeneral([
            'img_site_logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ])->assertRedirect();

        Http::assertNothingSent();
        $this->trackUploaded('site.logo');
        $this->assertStringEndsWith('.png', (string) $this->settings()->get('site.logo'));
    }

    public function test_removebg_api_key_is_never_exposed(): void
    {
        $plain = 'rbg-secret-key-'.uniqid();
        $this->settings()->set('site.remove_bg_api_key', $plain);
        $this->asAdmin();

        $html = $this->get('/reddy/settings?tab=general')->assertOk()->getContent();

        $this->assertStringNotContainsString($plain, $html, 'The API key must never be rendered, only masked.');
        $this->assertStringContainsString('••••••••'.substr($plain, -4), $html);

        $stored = Setting::where('key', 'site.remove_bg_api_key')->firstOrFail()->value;
        $this->assertNotSame($plain, $stored, 'The API key must be encrypted at rest.');
    }

    public function test_removebg_help_text_is_honest_about_key_requirement(): void
    {
        $this->asAdmin();

        $html = $this->get('/reddy/settings?tab=general')->assertOk()->getContent();

        $this->assertStringContainsString('Without a key, logo uploads are used exactly as uploaded', $html);
    }
}
