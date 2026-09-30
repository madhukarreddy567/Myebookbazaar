<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Stitch customer-UI rebuild (phase D1 screens + hand-styled pages).
 *
 * Guards the approved design language and the "real data only" rule:
 *  - home / register / login / library render in the Stitch theme
 *  - no invented marketing claims, counts or statistics
 *  - prices render in the visitor's real currency
 *  - admin pages never inherit the customer body class
 */
class CustomerUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(StoreSeeder::class);
        app(\App\Services\SettingsService::class)->forgetCache();
    }

    protected function tearDown(): void
    {
        app(\App\Services\SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    protected function seedBook(): Book
    {
        return Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
    }

    protected function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
            'password' => Hash::make('Password1!'),
        ], $attributes));
    }

    /** @return string[] strings that must never appear on customer pages */
    protected function fakeStrings(): array
    {
        return [
            'Guaranteed actionable guides',
            'reader utility score',
            'Free sample chapters',
            'Unlimited re-downloads',
            'under 2 seconds',
            'under 3 seconds',
            'PhonePe',
            'Paytm',
            'No sponsored trackers',
            'Free starter chapters',
            'Instant DRM',
            'cloud sync keys',
            'perpetually retained',
            'Lifetime Re-downloads',
            'catalog licenses',
            'payment tokens are strictly isolated',
            '256-bit SSL',
        ];
    }

    protected function assertNoFakeStrings(string $html, string $page): void
    {
        foreach ($this->fakeStrings() as $banned) {
            $this->assertStringNotContainsString($banned, $html, "Fake marketing claim on {$page}: {$banned}");
        }
    }

    public function test_home_renders_stitch_theme_with_real_price(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('st-page', $html);
        $this->assertStringContainsString('st-hero', $html);
        $this->assertStringContainsString('Fit at Home: Start (Edition 1)', $html);
        $this->assertStringContainsString('₹149', $html);
        $this->assertNoFakeStrings($html, 'home');
    }

    public function test_home_shows_usd_for_international_visitor(): void
    {
        $user = $this->makeUser(['country_code' => 'US', 'country_name' => 'United States', 'pricing_region' => 'INTL']);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('$4.99', $html);
        $this->assertNoFakeStrings($html, 'home (INTL)');
    }

    public function test_register_renders_with_security_wiring(): void
    {
        $this->enableGoogleOAuth();

        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('st-page', $html);
        $this->assertStringContainsString('Create your MyEbookBazaar Account', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
        $this->assertStringContainsString('countrySelect(', $html);
        // Honeypot wiring must survive the redesign.
        $this->assertStringContainsString('form_loaded_at', $html);
        $this->assertStringContainsString('action="'.route('register').'"', $html);
        $this->assertStringContainsString(route('auth.google.redirect'), $html);
        $this->assertNoFakeStrings($html, 'register');
    }

    public function test_login_renders_with_security_wiring(): void
    {
        $this->enableGoogleOAuth();

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('st-page', $html);
        $this->assertStringContainsString('Welcome Back, Reader', $html);
        $this->assertStringContainsString('name="remember"', $html);
        $this->assertStringContainsString('Forgot Password?', $html);
        $this->assertStringContainsString('action="'.route('login').'"', $html);
        $this->assertStringContainsString(route('auth.google.redirect'), $html);
        // Reset panel posts to the real password-email endpoint with CSRF.
        $this->assertStringContainsString('action="'.route('password.email').'"', $html);
        $this->assertNoFakeStrings($html, 'login');
    }

    protected function enableGoogleOAuth(): void
    {
        $settings = app(\App\Services\SettingsService::class);
        $settings->set('auth.google.enabled', '1');
        $settings->set('auth.google.client_id', 'test-client-id');
        $settings->set('auth.google.client_secret', 'test-client-secret');
        $settings->forgetCache();
    }

    public function test_library_renders_real_quota_and_download(): void
    {
        $book = $this->seedBook();
        $user = $this->makeUser();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateNumber(),
            'currency' => 'INR',
            'subtotal' => 149,
            'grand_total' => 149,
            'status' => Order::STATUS_PAID,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
            'no_refund_accepted_at' => now(),
        ]);
        $order->items()->create([
            'book_id' => $book->id,
            'title' => $book->title,
            'unit_price' => 149,
            'quantity' => 1,
            'line_total' => 149,
            'currency' => 'INR',
        ]);

        $html = $this->actingAs($user)->get('/library')->assertOk()->getContent();

        $this->assertStringContainsString('st-page', $html);
        $this->assertStringContainsString('My Digital Library', $html);
        $this->assertStringContainsString('st-quota-fill', $html);
        $this->assertStringContainsString('Paid ₹149', $html);
        $this->assertStringContainsString('st-timer-chip', $html);
        $this->assertNoFakeStrings($html, 'library');
    }

    public function test_library_requires_login(): void
    {
        $this->get('/library')->assertRedirect('/login');
    }

    public function test_hand_styled_pages_render(): void
    {
        foreach (['/books', '/combos', '/offers', '/cart', '/about', '/faq', '/contact', '/privacy-policy', '/terms', '/no-refund-policy'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringContainsString('st-page', $html, "Missing st-page on {$path}");
        }
    }

    public function test_auth_recovery_pages_render(): void
    {
        foreach (['/forgot-password', '/login'] as $path) {
            $this->get($path)->assertOk();
        }
        $user = User::factory()->unverified()->create([
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ]);
        $this->actingAs($user)->get('/verify-email')->assertOk();
    }

    public function test_admin_pages_never_use_customer_body_class(): void
    {
        $html = $this->get('/reddy/login')->assertOk()->getContent();
        $this->assertStringNotContainsString('st-page', $html);
        $this->assertStringNotContainsString('stitch-v2', $html);
    }
}
