<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Book;
use App\Models\Cart;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Automated SEO: per-book meta/OG/Twitter/canonical/JSON-LD, slug URLs,
 * sitemap.xml + robots.txt, and noindex on private pages.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test`; the sitemap cache and settings cache are cleared
 * between tests because the array cache store is process-global while
 * the database is transactional.
 */
class SeoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(StoreSeeder::class);
        app(SettingsService::class)->forgetCache();
        Cache::forget('sitemap.xml');
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        Cache::forget('sitemap.xml');
        parent::tearDown();
    }

    protected function seedBook(): Book
    {
        return Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
    }

    protected function makeBook(array $attributes = []): Book
    {
        return Book::create(array_merge([
            'title' => 'SEO Test Book '.uniqid(),
            'author' => 'Test Author',
            'description' => str_repeat('A helpful sentence about this ebook. ', 30),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
        ], $attributes));
    }

    protected function makeUser(): User
    {
        return User::factory()->create(['country_code' => 'IN', 'country_name' => 'India']);
    }

    // ------------------------------------------------------------------
    // Book detail page: full SEO tags
    // ------------------------------------------------------------------

    public function test_book_page_has_full_seo_tags(): void
    {
        $book = $this->seedBook();

        $response = $this->get(route('books.show', $book->slug));
        $response->assertOk();

        $html = $response->getContent();

        // Meta title: "{Title} by {Author} | MyEbookBazaar".
        $this->assertStringContainsString(
            '<title>'.$book->title.' by '.$book->author.' | MyEbookBazaar</title>',
            $html
        );

        // Meta description present and within ~155 chars (cleanly truncated).
        $this->assertMatchesRegularExpression(
            '/<meta name="description" content="([^"]*)"/',
            $html
        );
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $this->assertLessThanOrEqual(160, mb_strlen(html_entity_decode($m[1])));

        // Open Graph + Twitter Card tags.
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:description"', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('property="og:type" content="book"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('name="twitter:title"', $html);
        $this->assertStringContainsString('name="twitter:image"', $html);

        // Canonical URL.
        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('books.show', $book->slug).'"',
            $html
        );

        // JSON-LD Book schema with real price data.
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Book"', $html);
        $this->assertStringContainsString('"priceCurrency":"INR"', $html);
        $this->assertStringContainsString('"price":"149.00"', $html);

        // BreadcrumbList JSON-LD.
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);

        // One h1 on the page.
        $this->assertEquals(1, substr_count($html, '<h1'));
    }

    public function test_book_page_meta_description_falls_back_when_empty(): void
    {
        $book = $this->makeBook([
            'short_description' => null,
            'description' => null,
            'subtitle' => null,
        ]);

        $response = $this->get(route('books.show', $book->slug));
        $response->assertOk();
        $this->assertStringContainsString(
            '<meta name="description" content="'.$book->title.' — an ebook from MyEbookBazaar."',
            $response->getContent()
        );
    }

    // ------------------------------------------------------------------
    // Slug URLs
    // ------------------------------------------------------------------

    public function test_slug_urls_resolve(): void
    {
        $book = $this->seedBook();

        $this->get('/books/'.$book->slug)->assertOk();
        $this->get('/books/no-such-book-slug')->assertNotFound();
    }

    public function test_slug_auto_generated_and_unique_from_title(): void
    {
        $a = $this->makeBook(['title' => 'Unique Slug Book']);
        $b = $this->makeBook(['title' => 'Unique Slug Book']);

        $this->assertSame('unique-slug-book', $a->slug);
        $this->assertSame('unique-slug-book-2', $b->slug);
        $this->assertNotSame($a->slug, $b->slug);

        $this->get(route('books.show', $a->slug))->assertOk();
        $this->get(route('books.show', $b->slug))->assertOk();
    }

    // ------------------------------------------------------------------
    // Unpublished books: not found, excluded from sitemap
    // ------------------------------------------------------------------

    public function test_unpublished_book_returns_404_and_is_absent_from_sitemap(): void
    {
        $book = $this->makeBook(['is_active' => false]);

        // Inactive books are never rendered (404), so they cannot be indexed.
        $this->get(route('books.show', $book->slug))->assertNotFound();

        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString($book->slug, $sitemap);
    }

    // ------------------------------------------------------------------
    // Sitemap + robots
    // ------------------------------------------------------------------

    public function test_sitemap_is_valid_xml_containing_published_books_categories_combos(): void
    {
        $book = $this->seedBook();
        $inactive = $this->makeBook(['is_active' => false]);

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $xml = $response->getContent();
        $parsed = simplexml_load_string($xml);
        $this->assertNotFalse($parsed, 'sitemap.xml is not valid XML');
        $this->assertSame('urlset', $parsed->getName());

        // Published book with lastmod = updated_at date.
        $this->assertStringContainsString(route('books.show', $book->slug), $xml);
        $this->assertStringContainsString($book->updated_at->toDateString(), $xml);

        // Categories appear as filtered catalogue URLs.
        $this->assertStringContainsString('category', $xml);

        // Static pages present.
        $this->assertStringContainsString(route('about'), $xml);

        // Inactive book absent.
        $this->assertStringNotContainsString($inactive->slug, $xml);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('User-agent:', $body);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
    }

    // ------------------------------------------------------------------
    // Fault isolation: a corrupt record must never 500 the sitemap
    // ------------------------------------------------------------------

    public function test_sitemap_survives_corrupt_records_and_stays_valid_xml(): void
    {
        $book = $this->seedBook();

        // Simulate corrupt data that bypasses model safeguards (e.g. a
        // record written by raw SQL or an older code path): an active
        // book with an empty slug would make route('books.show', '')
        // throw. The sitemap must skip it, not 500.
        DB::table('books')->insert([
            'title' => 'Corrupt Slug Book',
            'slug' => '',
            'author' => 'Test Author',
            'description' => 'Corrupt record for sitemap fault-isolation test.',
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertOk();

        $xml = $response->getContent();
        $parsed = simplexml_load_string($xml);
        $this->assertNotFalse($parsed, 'sitemap.xml is not valid XML');
        $this->assertSame('urlset', $parsed->getName());

        // The good book is still listed; the corrupt one degrades away.
        $this->assertStringContainsString(route('books.show', $book->slug), $xml);
        $this->assertStringNotContainsString('Corrupt Slug Book', $xml);
    }

    // ------------------------------------------------------------------
    // noindex on private pages
    // ------------------------------------------------------------------

    public function test_auth_pages_carry_noindex(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringContainsString(
                '<meta name="robots" content="noindex,nofollow">',
                $html,
                "missing noindex on {$path}"
            );
        }
    }

    public function test_cart_and_checkout_carry_noindex(): void
    {
        $html = $this->get('/cart')->assertOk()->getContent();
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex,nofollow">',
            $html,
            'missing noindex on /cart'
        );

        $user = $this->makeUser();
        Cart::addBook($this->seedBook(), $user, 'seo-session');
        $html = $this->actingAs($user)->get('/checkout')->assertOk()->getContent();
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex,nofollow">',
            $html,
            'missing noindex on /checkout'
        );
    }

    public function test_account_orders_library_carry_noindex(): void
    {
        $user = $this->makeUser();

        foreach (['/account', '/orders', '/library'] as $path) {
            $html = $this->actingAs($user)->get($path)->assertOk()->getContent();
            $this->assertStringContainsString(
                '<meta name="robots" content="noindex,nofollow">',
                $html,
                "missing noindex on {$path}"
            );
        }
    }

    public function test_admin_pages_carry_noindex(): void
    {
        $admin = Admin::create([
            'name' => 'SEO Admin',
            'email' => 'seo-admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);

        $html = $this->get('/reddy/login')->assertOk()->getContent();
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $html,
            'missing noindex on /reddy/login'
        );

        $html = $this->actingAs($admin, 'admin')->get('/reddy/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('noindex', $html, 'missing noindex on /reddy/dashboard');
    }

    // ------------------------------------------------------------------
    // Public pages are indexable: no noindex on home / book pages
    // ------------------------------------------------------------------

    public function test_public_pages_do_not_carry_noindex(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('noindex', $home);
        $this->assertStringContainsString('application/ld+json', $home);
        $this->assertStringContainsString('"@type":"Organization"', $home);
        $this->assertStringContainsString('"@type":"WebSite"', $home);
        $this->assertEquals(1, substr_count($home, '<h1'));

        $bookPage = $this->get(route('books.show', $this->seedBook()->slug))->assertOk()->getContent();
        $this->assertStringNotContainsString('noindex', $bookPage);
    }
}
