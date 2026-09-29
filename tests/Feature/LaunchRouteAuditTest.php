<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 8 launch gate: every public route responds correctly, every /reddy
 * route demands admin login, and /admin stays 404.
 *
 * Uses DatabaseTransactions (NOT RefreshDatabase): the suite shares the dev
 * MySQL database, so RefreshDatabase would wipe real dev data.
 */
class LaunchRouteAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function seedBook(): Book
    {
        return Book::firstOrCreate(
            ['slug' => 'fit-at-home-start-edition-1'],
            [
                'title' => 'Fit at Home: Start (Edition 1)',
                'author' => 'H. Madhukar Reddy',
                'short_description' => 'A 54-page starter guide.',
                'description' => 'Full description.',
                'price_inr' => 149,
                'price_usd' => 4.99,
                'is_active' => true,
                'is_featured' => true,
            ]
        );
    }

    public function test_public_pages_return_200(): void
    {
        $this->seedBook();

        $pages = [
            '/', '/books', '/books/fit-at-home-start-edition-1',
            '/combos', '/offers',
            '/about', '/faq', '/contact',
            '/privacy-policy', '/terms', '/no-refund-policy',
            '/login', '/register', '/forgot-password',
            '/sitemap.xml', '/robots.txt',
        ];

        foreach ($pages as $page) {
            $response = $this->get($page);
            $this->assertTrue(
                in_array($response->status(), [200]),
                "Public page {$page} returned {$response->status()}, expected 200."
            );
        }
    }

    public function test_sitemap_is_valid_xml_with_book_url(): void
    {
        $this->seedBook();

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<urlset', $response->getContent());
        $this->assertStringContainsString('/books/fit-at-home-start-edition-1', $response->getContent());
    }

    public function test_robots_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();
        $this->assertStringContainsString('Sitemap:', $response->getContent());
        $this->assertStringContainsString('/sitemap.xml', $response->getContent());
    }

    public function test_checkout_requires_login(): void
    {
        // Cart is reachable for guests.
        $this->get('/cart')->assertOk();

        // Checkout with an empty cart redirects to login (customer middleware).
        $this->get('/checkout')->assertRedirect('/login');

        // Even with items, guests are sent to login first.
        $book = $this->seedBook();
        $this->post(route('cart.store'), ['book_id' => $book->id])->assertRedirect();
        $this->get('/checkout')->assertRedirect('/login');

        // A logged-in customer with a verified email and items in their
        // own cart sees the checkout page.
        $user = User::create([
            'name' => 'Checkout Tester',
            'email' => 'checkout@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user)
            ->post(route('cart.store'), ['book_id' => $book->id])
            ->assertRedirect();
        $this->actingAs($user)->get('/checkout')->assertOk();
    }

    public function test_customer_only_pages_redirect_guests_to_login(): void
    {
        foreach (['/account', '/library', '/orders'] as $page) {
            $this->get($page)->assertRedirect('/login');
        }
    }

    public function test_admin_path_is_404(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $this->get('/admin/dashboard')->assertNotFound();
    }

    public function test_reddy_routes_require_admin_login(): void
    {
        $protected = [
            '/reddy/dashboard',
            '/reddy/books',
            '/reddy/books/create',
            '/reddy/orders',
            '/reddy/users',
            '/reddy/settings',
            '/reddy/updates',
            '/reddy/updates/confirm',
            '/reddy/backups',
            '/reddy/audit-log',
            '/reddy/security-log',
            '/reddy/coupons',
            '/reddy/combos',
            '/reddy/categories',
            '/reddy/banners',
            '/reddy/offers',
            '/reddy/pages',
            '/reddy/admins',
            '/reddy/sessions',
        ];

        foreach ($protected as $page) {
            $response = $this->get($page);
            $this->assertEquals(
                302, $response->status(),
                "Admin page {$page} returned {$response->status()}, expected 302 to login."
            );
            $this->assertStringContainsString(
                '/reddy/login', $response->headers->get('Location'),
                "Admin page {$page} did not redirect to /reddy/login."
            );
        }
    }

    public function test_reddy_login_page_is_reachable(): void
    {
        $this->get('/reddy/login')->assertOk();
    }

    public function test_customer_cannot_reach_admin_routes(): void
    {
        $user = User::create([
            'name' => 'Test Reader',
            'email' => 'reader@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)->get('/reddy/dashboard')->assertRedirect('/reddy/login');
        $this->actingAs($user)->get('/reddy/settings')->assertRedirect('/reddy/login');
    }

    public function test_admin_can_reach_dashboard(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
        ]);

        // 2FA is not enforced until configured; the require_2fa middleware
        // lets admins without 2FA through to the dashboard with a warning.
        $this->actingAs($admin, 'admin')->get('/reddy/dashboard')->assertOk();
    }

    public function test_seo_meta_tags_present_on_homepage(): void
    {
        $content = $this->get('/')->getContent();

        $this->assertStringContainsString('<meta name="description"', $content);
        $this->assertStringContainsString('property="og:title"', $content);
        $this->assertStringContainsString('property="og:description"', $content);
        $this->assertStringContainsString('property="og:image"', $content);
        $this->assertStringContainsString('name="twitter:card"', $content);
        $this->assertStringContainsString('rel="canonical"', $content);
    }

    public function test_book_page_has_og_image_and_canonical(): void
    {
        $this->seedBook();
        $content = $this->get('/books/fit-at-home-start-edition-1')->getContent();

        $this->assertStringContainsString('property="og:image"', $content);
        $this->assertStringContainsString('rel="canonical"', $content);
        $this->assertStringContainsString('/books/fit-at-home-start-edition-1', $content);
    }

    public function test_sitemap_excludes_disabled_features(): void
    {
        $this->seedBook();

        // With combos and offers enabled (defaults), they are listed.
        $content = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('/combos', $content);
        $this->assertStringContainsString('/offers', $content);

        // Disable both — they must disappear from the sitemap.
        $settings = app(\App\Services\SettingsService::class);
        $settings->set('features.combos', false);
        $settings->set('features.sales_offers', false);
        \Illuminate\Support\Facades\Cache::forget('sitemap.xml');

        $content = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString('/combos', $content);
        $this->assertStringNotContainsString('/offers', $content);
        // Books and homepage stay.
        $this->assertStringContainsString('/books/fit-at-home-start-edition-1', $content);
    }

    public function test_security_headers_present(): void
    {
        $response = $this->get('/');

        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString(
            "'unsafe-eval'",
            $response->headers->get('Content-Security-Policy'),
            'CSP must allow unsafe-eval or Alpine.js menus break.'
        );
    }
}
