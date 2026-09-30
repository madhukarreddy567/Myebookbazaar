<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\PriceService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Price rendering across every public book surface.
 *
 * Covers the reported bug: "Sale price and other prices not rendering."
 * Root causes fixed:
 *  - PriceService quotes never contained `discount_percent`, so the home
 *    "Save %" chip and the detail "% off" chip rendered empty / missing.
 *  - Cart and checkout rows never received `price_regular_display`, so the
 *    struck-through original price never showed on a live sale.
 *  - The library showed no price at all; it now shows the paid price.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test` (SQLite is forbidden by the project rules).
 */
class PriceDisplayTest extends TestCase
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

    protected function saleBook(): Book
    {
        $book = Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
        $book->update([
            'is_active' => true,
            'is_featured' => true,
            'sale_price_inr' => 99,
            'sale_price_usd' => 2.99,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);

        return $book->fresh();
    }

    protected function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $attributes));
    }

    // ------------------------------------------------------------------
    // PriceService quotes
    // ------------------------------------------------------------------

    public function test_quote_contains_discount_percent_on_sale(): void
    {
        $book = $this->saleBook();

        $quote = $this->prices()->getPrice($book);

        $this->assertTrue($quote['on_sale']);
        $this->assertSame('₹', $quote['symbol']);
        $this->assertEquals(99, $quote['amount']);
        $this->assertEquals(149, $quote['regular_amount']);
        $this->assertSame(34, $quote['discount_percent']);
    }

    public function test_quote_has_null_discount_percent_off_sale(): void
    {
        $book = Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
        $book->update(['sale_price_inr' => null, 'sale_price_usd' => null]);

        $quote = $this->prices()->getPrice($book->fresh());

        $this->assertFalse($quote['on_sale']);
        $this->assertNull($quote['regular_amount']);
        $this->assertNull($quote['discount_percent']);
    }

    public function test_sale_outside_window_is_not_active(): void
    {
        $book = $this->saleBook();
        $book->update(['sale_starts_at' => now()->addDay(), 'sale_ends_at' => now()->addDays(2)]);

        $quote = $this->prices()->getPrice($book->fresh());

        $this->assertFalse($quote['on_sale']);
        $this->assertNull($quote['discount_percent']);
        $this->assertEquals(149, $quote['amount']);
    }

    public function test_sale_switch_off_disables_sale_price(): void
    {
        $this->saleBook();
        app(SettingsService::class)->set('features.sales_offers', '0');

        $book = Book::where('slug', 'fit-at-home-start-edition-1')->firstOrFail();
        $quote = $this->prices()->getPrice($book);

        $this->assertFalse($quote['on_sale']);
        $this->assertEquals(149, $quote['amount']);
    }

    public function test_intl_visitor_gets_usd_quote(): void
    {
        $book = $this->saleBook();
        $user = $this->makeUser(['country_code' => 'US', 'country_name' => 'United States', 'pricing_region' => 'INTL']);

        $quote = $this->prices()->getPrice($book, $user);

        $this->assertSame('USD', $quote['currency']);
        $this->assertSame('$', $quote['symbol']);
        $this->assertTrue($quote['on_sale']);
        $this->assertEquals(2.99, $quote['amount']);
        $this->assertEquals(4.99, $quote['regular_amount']);
        $this->assertSame(40, $quote['discount_percent']);
    }

    // ------------------------------------------------------------------
    // Rendered surfaces
    // ------------------------------------------------------------------

    public function test_home_shows_simple_price_without_clutter(): void
    {
        $this->saleBook();

        $html = $this->get('/')->assertOk()->getContent();

        // Simplified homepage: clean price display, no "Save %" chip clutter.
        $this->assertStringContainsString('₹99', $html);
        $this->assertStringContainsString('₹149', $html);
        $this->assertStringNotContainsString('Save %<', $html);
    }

    public function test_catalog_book_card_shows_sale_and_struck_original(): void
    {
        $this->saleBook();

        $html = $this->get('/books')->assertOk()->getContent();

        $this->assertStringContainsString('₹99', $html);
        $this->assertStringContainsString('₹149', $html);
        // book-card renders the old price with the struck-through class.
        $this->assertStringContainsString('book-card-oldprice', $html);
    }

    public function test_book_detail_shows_discount_chip_and_struck_original(): void
    {
        $book = $this->saleBook();

        $html = $this->get('/books/'.$book->slug)->assertOk()->getContent();

        $this->assertStringContainsString('₹99', $html);
        $this->assertStringContainsString('₹149', $html);
        $this->assertStringContainsString('34% off', $html);
    }

    public function test_cart_shows_sale_price_with_struck_original(): void
    {
        $book = $this->saleBook();
        $user = $this->makeUser();
        Cart::addBook($book, $user, 'test-session');

        $html = $this->actingAs($user)->get('/cart')->assertOk()->getContent();

        $this->assertStringContainsString('₹99', $html);
        $this->assertStringContainsString('₹149', $html);
        $this->assertStringContainsString('book-card-oldprice', $html);
    }

    public function test_checkout_shows_sale_price_with_struck_original(): void
    {
        $book = $this->saleBook();
        $user = $this->makeUser();
        Cart::addBook($book, $user, 'test-session');

        $html = $this->actingAs($user)->get('/checkout')->assertOk()->getContent();

        $this->assertStringContainsString('₹99', $html);
        $this->assertStringContainsString('₹149', $html);
        $this->assertStringContainsString('book-card-oldprice', $html);
    }

    public function test_library_shows_paid_price_with_correct_currency(): void
    {
        $book = $this->saleBook();
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

        $this->assertStringContainsString('Paid ₹149', $html);
    }

    public function test_library_shows_usd_paid_price_for_intl_order(): void
    {
        $book = $this->saleBook();
        $user = $this->makeUser(['country_code' => 'US', 'pricing_region' => 'INTL']);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateNumber(),
            'currency' => 'USD',
            'subtotal' => 4.99,
            'grand_total' => 4.99,
            'status' => Order::STATUS_PAID,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
            'no_refund_accepted_at' => now(),
        ]);
        $order->items()->create([
            'book_id' => $book->id,
            'title' => $book->title,
            'unit_price' => 4.99,
            'quantity' => 1,
            'line_total' => 4.99,
            'currency' => 'USD',
        ]);

        $html = $this->actingAs($user)->get('/library')->assertOk()->getContent();

        $this->assertStringContainsString('Paid $4.99', $html);
    }
}
