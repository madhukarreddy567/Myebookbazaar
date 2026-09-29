<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Category;
use App\Models\User;
use App\Services\PriceService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Phase 2B: store foundation.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test` (SQLite is
 * forbidden by the project rules); the settings cache is cleared between
 * tests because the array cache store is process-global while the
 * database is transactional.
 */
class StoreTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->seed(StoreSeeder::class);
        app(SettingsService::class)->forgetCache();
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    protected function prices(): PriceService
    {
        return app(PriceService::class);
    }

    protected function seedBook(): Book
    {
        return Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Store Admin',
            'email' => 'store-admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
    }

    protected function switch(string $key, bool $on): void
    {
        app(SettingsService::class)->set($key, $on);
    }

    protected function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['country_code' => 'IN', 'country_name' => 'India'], $attributes));
    }

    /**
     * Carry the (encrypted) session cookie from one test response into the
     * next request, so consecutive HTTP calls share one guest session —
     * Laravel's test client does not do this automatically.
     */
    protected function carrySessionCookie(\Illuminate\Testing\TestResponse $response): void
    {
        $name = config('session.cookie');
        $cookie = $response->getCookie($name, false);

        if ($cookie) {
            $this->withUnencryptedCookie($name, $cookie->getValue());
        }
    }

    // ------------------------------------------------------------------
    // Pricing
    // ------------------------------------------------------------------

    public function test_indian_visitors_pay_inr(): void
    {
        $quote = $this->prices()->getPrice($this->seedBook(), null, 'IN');

        $this->assertSame('INR', $quote['currency']);
        $this->assertEquals(149, $quote['amount']);
        $this->assertSame('₹', $quote['symbol']);
        $this->assertFalse($quote['on_sale']);
    }

    public function test_international_visitors_pay_usd(): void
    {
        $quote = $this->prices()->getPrice($this->seedBook(), null, 'US');

        $this->assertSame('USD', $quote['currency']);
        $this->assertEquals(4.99, $quote['amount']);
        $this->assertSame('$', $quote['symbol']);
    }

    public function test_logged_in_customer_uses_saved_region(): void
    {
        $user = $this->makeUser(['pricing_region' => 'IN']);

        $quote = $this->prices()->getPrice($this->seedBook(), $user);

        $this->assertSame('INR', $quote['currency']);
    }

    public function test_sale_price_applies_inside_window(): void
    {
        $book = Book::create([
            'title' => 'Sale Book', 'slug' => 'sale-book-'.uniqid(),
            'price_inr' => 200, 'price_usd' => 9.99,
            'sale_price_inr' => 99, 'sale_price_usd' => 4.99,
            'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $quote = $this->prices()->getPrice($book, null, 'IN');

        $this->assertTrue($quote['on_sale']);
        $this->assertEquals(99, $quote['amount']);
        $this->assertEquals(200, $quote['regular_amount']);
    }

    public function test_sale_price_ignored_outside_window(): void
    {
        $book = Book::create([
            'title' => 'Expired Sale', 'slug' => 'expired-sale-'.uniqid(),
            'price_inr' => 200, 'price_usd' => 9.99,
            'sale_price_inr' => 99, 'sale_price_usd' => 4.99,
            'sale_starts_at' => now()->subDays(5), 'sale_ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $quote = $this->prices()->getPrice($book, null, 'IN');

        $this->assertFalse($quote['on_sale']);
        $this->assertEquals(200, $quote['amount']);
        $this->assertNull($quote['regular_amount']);
    }

    public function test_sale_ignored_when_sales_switch_off(): void
    {
        $this->switch('features.sales_offers', false);

        $book = Book::create([
            'title' => 'Switch Sale', 'slug' => 'switch-sale-'.uniqid(),
            'price_inr' => 200, 'price_usd' => 9.99,
            'sale_price_inr' => 99, 'sale_price_usd' => 4.99,
            'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $quote = $this->prices()->getPrice($book, null, 'IN');

        $this->assertFalse($quote['on_sale']);
        $this->assertEquals(200, $quote['amount']);
    }

    public function test_disabled_currency_falls_back(): void
    {
        $this->switch('pricing.inr_enabled', false);

        $quote = $this->prices()->getPrice($this->seedBook(), null, 'IN');

        $this->assertSame('USD', $quote['currency']);
        $this->assertEquals(4.99, $quote['amount']);
    }

    // ------------------------------------------------------------------
    // Cart
    // ------------------------------------------------------------------

    public function test_guest_can_add_to_cart(): void
    {
        $book = $this->seedBook();

        $this->post(route('cart.store'), ['book_id' => $book->id])
            ->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'book_id' => $book->id,
            'user_id' => null,
        ]);
    }

    public function test_guest_cart_blocked_when_switch_off(): void
    {
        $this->switch('features.guest_cart', false);

        $this->post(route('cart.store'), ['book_id' => $this->seedBook()->id])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_guest_cart_merges_on_login(): void
    {
        $book = $this->seedBook();

        // Guest adds a book (same browser session must be kept for login).
        $this->carrySessionCookie(
            $this->post(route('cart.store'), ['book_id' => $book->id])->assertRedirect()
        );

        $user = $this->makeUser();

        // Real login through the controller (triggers the merge).
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseMissing('carts', [
            'book_id' => $book->id,
            'user_id' => null,
        ]);
    }

    public function test_cart_merge_skips_duplicates(): void
    {
        $book = $this->seedBook();
        $user = $this->makeUser();

        // User already owns the book in their cart; guest session has it too.
        Cart::addBook($book, $user, 'old-session');
        $this->carrySessionCookie(
            $this->post(route('cart.store'), ['book_id' => $book->id])->assertRedirect()
        );

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertEquals(1, Cart::where('user_id', $user->id)->where('book_id', $book->id)->count());
    }

    public function test_customer_cannot_remove_another_cart_row(): void
    {
        $book = $this->seedBook();
        $owner = $this->makeUser();
        $row = Cart::addBook($book, $owner, 'some-session');

        $intruder = $this->makeUser();

        $this->actingAs($intruder)
            ->delete(route('cart.destroy', $row))
            ->assertNotFound();

        $this->assertDatabaseHas('carts', ['id' => $row->id]);
    }

    public function test_cart_page_shows_visitor_currency_total(): void
    {
        $book = $this->seedBook();
        $this->carrySessionCookie(
            $this->post(route('cart.store'), ['book_id' => $book->id])
        );

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('₹149');
    }

    // ------------------------------------------------------------------
    // Feature switches on store routes
    // ------------------------------------------------------------------

    public function test_guest_browsing_blocked_when_switch_off(): void
    {
        $this->switch('features.guest_browsing', false);

        $this->get(route('books'))->assertForbidden();
        $this->get(route('books.show', $this->seedBook()->slug))->assertForbidden();

        // Logged-in customers are never blocked from browsing.
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('books'))->assertOk();
    }

    public function test_buy_now_blocked_when_switch_off(): void
    {
        $this->switch('features.buy_now', false);

        $this->post(route('books.buy-now', $this->seedBook()->slug))->assertForbidden();
    }

    public function test_buy_now_adds_and_goes_to_cart(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('books.buy-now', $this->seedBook()->slug))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('carts', [
            'book_id' => $this->seedBook()->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_contact_page_honours_switch(): void
    {
        $this->get(route('contact'))->assertOk();

        $this->switch('features.contact_form', false);

        $this->get(route('contact'))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // CRLF hardening (GHSA-5vg9-5847-vvmq backport: no patched Laravel 11
    // exists, so the app rejects CR/LF in the email rule and in the
    // contact form's other mail-header fields).
    // ------------------------------------------------------------------

    protected function validContactPayload(): array
    {
        return [
            'name' => 'A Reader',
            'email' => 'reader@example.com',
            'subject' => 'A question',
            'message' => 'Hello, I have a question about the book.',
        ];
    }

    public function test_contact_form_rejects_crlf_in_email(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), array_merge($this->validContactPayload(),
            ['email' => "victim@example.com\r\nBcc: attacker@example.com"]))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_contact_form_rejects_crlf_in_name_and_subject(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), array_merge($this->validContactPayload(),
            ['name' => "Evil\r\nX-Injected: yes"]))
            ->assertSessionHasErrors('name');

        $this->post(route('contact.send'), array_merge($this->validContactPayload(),
            ['subject' => "Hi\r\nBcc: attacker@example.com"]))
            ->assertSessionHasErrors('subject');

        Mail::assertNothingSent();
    }

    public function test_contact_form_accepts_legit_input(): void
    {
        Mail::fake();

        $this->post(route('contact.send'), $this->validContactPayload())
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(ContactMessageMail::class);
    }

    public function test_email_rule_rejects_crlf_everywhere(): void
    {
        $validator = Validator::make(
            ['email' => "user@example.com\nBcc: x@y.z"],
            ['email' => ['required', 'email']]
        );

        $this->assertTrue($validator->fails());

        $ok = Validator::make(
            ['email' => 'user@example.com'],
            ['email' => ['required', 'email']]
        );

        $this->assertTrue($ok->passes());
    }

    // ------------------------------------------------------------------
    // Private PDF storage
    // ------------------------------------------------------------------

    public function test_pdf_is_private_and_not_url_reachable(): void
    {
        Storage::fake('local');

        $book = Book::create([
            'title' => 'PDF Book', 'slug' => 'pdf-book-'.uniqid(),
            'price_inr' => 149, 'price_usd' => 4.99,
            'file_path' => 'ebooks/secret.pdf', 'is_active' => true,
        ]);
        Storage::disk('local')->put('ebooks/secret.pdf', 'fake-pdf-bytes');

        // The file lives on the private disk...
        $this->assertTrue(Storage::disk('local')->exists('ebooks/secret.pdf'));
        // ...but no public URL serves it. Laravel's serve route requires a
        // signed URL for private disks: unsigned requests get 403 in dev
        // and 404 in production — the file itself is never exposed.
        $status = $this->get('/storage/ebooks/secret.pdf')->status();
        $this->assertContains($status, [403, 404], 'unsigned private-file request returned '.$status);
        $this->assertFalse(Storage::disk('public')->exists('ebooks/secret.pdf'));
    }

    // ------------------------------------------------------------------
    // Admin CRUD + audit
    // ------------------------------------------------------------------

    public function test_admin_book_crud_is_audited(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->makeAdmin(), 'admin');

        // Create (with uploads).
        $this->post(route('reddy.books.store'), [
            'title' => 'Audited Book',
            'price_inr' => 149,
            'price_usd' => 4.99,
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'pdf' => UploadedFile::fake()->create('book.pdf', 500, 'application/pdf'),
        ])->assertRedirect(route('reddy.books.index'));

        $book = Book::where('title', 'Audited Book')->firstOrFail();
        Storage::disk('public')->assertExists($book->cover_image);
        Storage::disk('local')->assertExists($book->file_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'book.created',
            'target_type' => 'book',
            'target_id' => $book->id,
        ]);

        // Update.
        $this->put(route('reddy.books.update', $book), [
            'title' => 'Audited Book Renamed',
            'price_inr' => 199,
            'price_usd' => 5.99,
        ])->assertRedirect(route('reddy.books.index'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'book.updated',
            'target_type' => 'book',
            'target_id' => $book->id,
        ]);

        // Delete (files removed too).
        $coverPath = $book->cover_image;
        $pdfPath = $book->file_path;
        $this->delete(route('reddy.books.destroy', $book))
            ->assertRedirect(route('reddy.books.index'));
        Storage::disk('public')->assertMissing($coverPath);
        Storage::disk('local')->assertMissing($pdfPath);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'book.deleted',
            'target_type' => 'book',
            'target_id' => $book->id,
        ]);
    }

    public function test_admin_category_banner_page_crud_is_audited(): void
    {
        Storage::fake('public');
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->post(route('reddy.categories.store'), ['name' => 'Skills'])
            ->assertRedirect();
        $category = Category::where('slug', 'skills')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.created', 'target_id' => $category->id]);

        $this->post(route('reddy.banners.store'), [
            'title' => 'Diwali Sale',
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'banner.created']);

        $this->post(route('reddy.pages.store'), [
            'title' => 'Shipping',
            'slug' => 'shipping',
            'content' => 'Digital delivery only.',
        ])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'page.created']);

        // Category with books cannot be deleted.
        $this->delete(route('reddy.categories.destroy', Category::where('slug', 'fitness-diet')->first()))
            ->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'fitness-diet']);
    }

    public function test_guests_cannot_reach_admin_store_routes(): void
    {
        $this->get(route('reddy.books.index'))->assertRedirect(route('reddy.login'));
        $this->post(route('reddy.books.store'), [])->assertRedirect(route('reddy.login'));
    }

    public function test_admin_store_pages_render(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->get(route('reddy.books.index'))->assertOk()->assertSee('Fit at Home: Start (Edition 1)');
        $this->get(route('reddy.books.create'))->assertOk();
        $this->get(route('reddy.books.edit', $this->seedBook()))->assertOk();
        $this->get(route('reddy.categories.index'))->assertOk()->assertSee('Fitness & Diet');
        $this->get(route('reddy.categories.create'))->assertOk();
        $this->get(route('reddy.banners.index'))->assertOk();
        $this->get(route('reddy.banners.create'))->assertOk();
        $this->get(route('reddy.pages.index'))->assertOk()->assertSee('Frequently asked questions');
        $this->get(route('reddy.pages.create'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Public pages render
    // ------------------------------------------------------------------

    public function test_store_pages_render(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Fit at Home: Start (Edition 1)');
        $this->get(route('books'))->assertOk();
        $this->get(route('books.show', $this->seedBook()->slug))->assertOk()->assertSee('₹149');
        $this->get(route('about'))->assertOk();
        $this->get(route('faq'))->assertOk();
        $this->get(route('privacy'))->assertOk();
        $this->get(route('terms'))->assertOk();
        $this->get(route('no-refund'))->assertOk();
    }

    public function test_inactive_book_is_not_public(): void
    {
        $book = Book::create([
            'title' => 'Hidden Book', 'slug' => 'hidden-book-'.uniqid(),
            'price_inr' => 149, 'price_usd' => 4.99, 'is_active' => false,
        ]);

        $this->get(route('books.show', $book->slug))->assertNotFound();
        $this->get(route('books'))->assertDontSee('Hidden Book');
    }
}
