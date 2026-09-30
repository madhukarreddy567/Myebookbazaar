<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RazorpayService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GST on Indian digital ebook orders (owner-decided 2026-09-30).
 *
 * PRICING IS GST-INCLUSIVE: the advertised price is ALWAYS the final
 * total. INR orders extract GST from the inclusive total:
 *
 *   taxable = round(total / (1 + rate/100), 2)
 *   gst     = round(total - taxable, 2)   // taxable + gst == total exactly
 *
 * USD (international) orders: zero GST. All math is server-side;
 * client-supplied GST values are never trusted.
 */
class GstTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
        Mail::fake();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function makeUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name' => 'GST Test User',
            'email' => 'gst-'.uniqid().'@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'GST Admin',
            'email' => 'gst-admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
    }

    protected function makeBook(array $overrides = []): Book
    {
        Storage::disk('local')->put('ebooks/gst-'.uniqid().'.pdf', '%PDF-1.4 fake');

        return Book::create(array_merge([
            'title' => 'GST Test Book '.uniqid(),
            'slug' => 'gst-test-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'file_path' => collect(Storage::disk('local')->files('ebooks'))->first(),
            'is_active' => true,
        ], $overrides));
    }

    protected function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'GST'.strtoupper(uniqid()),
            'type' => 'percent',
            'value' => 10,
            'currency' => 'BOTH',
            'max_uses_per_user' => 5,
            'is_active' => true,
            'applies_to' => 'all',
        ], $overrides));
    }

    protected function orderFromCart(User $user, ?Coupon $coupon = null): Order
    {
        return app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'razorpay');
    }

    // ------------------------------------------------------------------
    // The core math
    // ------------------------------------------------------------------

    public function test_inr_order_extracts_gst_inclusively_at_default_18(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $order = $this->orderFromCart($user);

        // Advertised price stays the final total.
        $this->assertSame('149.00', $order->grand_total);
        $this->assertSame('INR', $order->currency);
        $this->assertSame('18.00', $order->gst_rate);

        // Worked example: taxable = round(149 / 1.18, 2) = 126.27;
        // gst = round(149 - 126.27, 2) = 22.73.
        $this->assertSame('22.73', $order->gst_amount);
        $this->assertSame('22.73', $order->tax_total);

        // taxable + gst == total exactly (no rounding drift).
        $taxable = round((float) $order->grand_total - (float) $order->gst_amount, 2);
        $this->assertSame('126.27', number_format($taxable, 2, '.', ''));
        $this->assertSame(
            $order->grand_total,
            number_format($taxable + (float) $order->gst_amount, 2, '.', '')
        );
    }

    public function test_custom_admin_rate_applies_to_new_orders(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('tax.inr_gst_rate', '5');
        $settings->forgetCache();

        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $order = $this->orderFromCart($user);

        $this->assertSame('149.00', $order->grand_total);
        $this->assertSame('5.00', $order->gst_rate);
        // taxable = round(149 / 1.05, 2) = 141.90; gst = 149 - 141.90 = 7.10.
        $this->assertSame('7.10', $order->gst_amount);
    }

    public function test_usd_order_has_zero_gst(): void
    {
        $user = $this->makeUser(['pricing_region' => 'INTL', 'country_code' => 'US', 'country_name' => 'United States']);
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $order = $this->orderFromCart($user);

        $this->assertSame('USD', $order->currency);
        $this->assertSame('4.99', $order->grand_total);
        $this->assertSame('0.00', $order->gst_rate);
        $this->assertSame('0.00', $order->gst_amount);
    }

    public function test_discount_then_gst_uses_discounted_total_as_inclusive_base(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['value' => 10]); // 10% off 149 = 134.10
        Cart::addBook($book, $user, 'test-session');

        $order = $this->orderFromCart($user, $coupon);

        $this->assertSame('134.10', $order->grand_total);
        // taxable = round(134.10 / 1.18, 2) = 113.64; gst = 20.46.
        $this->assertSame('20.46', $order->gst_amount);
        $this->assertSame('18.00', $order->gst_rate);
    }

    public function test_free_order_from_100_percent_coupon_has_zero_gst(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['value' => 100]);
        Cart::addBook($book, $user, 'test-session');

        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'coupon');

        $this->assertSame('0.00', $order->grand_total);
        $this->assertSame('18.00', $order->gst_rate);
        $this->assertSame('0.00', $order->gst_amount);
    }

    public function test_historical_order_keeps_old_rate_after_setting_change(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $oldOrder = $this->orderFromCart($user);

        $this->assertSame('18.00', $oldOrder->gst_rate);
        $this->assertSame('22.73', $oldOrder->gst_amount);

        // Admin changes the rate; only NEW orders are affected.
        $settings = app(SettingsService::class);
        $settings->set('tax.inr_gst_rate', '28');
        $settings->forgetCache();

        Cart::addBook($book, $user, 'test-session');
        $newOrder = $this->orderFromCart($user);

        $this->assertSame('28.00', $newOrder->gst_rate);
        // taxable = round(149 / 1.28, 2) = 116.41; gst = 32.59.
        $this->assertSame('32.59', $newOrder->gst_amount);

        $oldOrder->refresh();
        $this->assertSame('18.00', $oldOrder->gst_rate);
        $this->assertSame('22.73', $oldOrder->gst_amount);
    }

    public function test_combo_order_extracts_gst_inclusively(): void
    {
        $user = $this->makeUser();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $combo = Combo::create([
            'title' => 'GST Combo '.uniqid(),
            'slug' => 'gst-combo-'.uniqid(),
            'book_ids' => [$bookA->id, $bookB->id],
            'combo_price_inr' => 199,
            'combo_price_usd' => 7.99,
            'is_active' => true,
        ]);

        $order = app(OrderService::class)->createFromCombo($user, $combo, '127.0.0.1', null, null, 'razorpay');

        $this->assertSame('199.00', $order->grand_total);
        $this->assertSame('18.00', $order->gst_rate);
        // taxable = round(199 / 1.18, 2) = 168.64; gst = 30.36.
        $this->assertSame('30.36', $order->gst_amount);
    }

    // ------------------------------------------------------------------
    // Rendering
    // ------------------------------------------------------------------

    public function test_gst_breakdown_rendered_on_checkout_page(): void
    {
        $user = $this->makeUser();
        Cart::addBook($this->makeBook(), $user, 'test-session');

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk();
        $response->assertSee('Includes GST (18%)', false);
        $response->assertSee('₹22.73', false);
        // Final payable is unchanged.
        $response->assertSee('₹149', false);
    }

    public function test_gst_breakdown_not_shown_for_usd_checkout(): void
    {
        $user = $this->makeUser(['pricing_region' => 'INTL', 'country_code' => 'US', 'country_name' => 'United States']);
        Cart::addBook($this->makeBook(), $user, 'test-session');

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk();
        $response->assertDontSee('Includes GST', false);
    }

    public function test_gst_breakdown_rendered_on_invoice_page(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $order = $this->orderFromCart($user);

        $response = $this->actingAs($user)->get('/orders/'.$order->id);

        $response->assertOk();
        $response->assertSee('Includes GST (18%)', false);
        $response->assertSee('₹22.73', false);
    }

    public function test_gst_breakdown_rendered_on_thankyou_page(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $order = $this->orderFromCart($user);
        $order->forceFill(['status' => Order::STATUS_PAID])->save();

        $response = $this->actingAs($user)->get(route('checkout.thankyou', $order));

        $response->assertOk();
        $response->assertSee('Includes GST (18%)', false);
        $response->assertSee('₹22.73', false);
    }

    public function test_confirmation_email_contains_gst_breakdown(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $order = $this->orderFromCart($user);
        $order->load('items', 'user');

        $html = (new OrderConfirmationMail($order))->render();
        $this->assertStringContainsString('Includes GST (18%)', $html);
        $this->assertStringContainsString('₹22.73', $html);

        $text = view('emails.orders.confirmation-text', ['order' => $order])->render();
        $this->assertStringContainsString('Includes GST (18%)', $text);
        $this->assertStringContainsString('₹22.73', $text);
    }

    public function test_gst_breakdown_rendered_on_admin_order_detail(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $order = $this->orderFromCart($user);

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->get('/reddy/orders/'.$order->id);

        $response->assertOk();
        $response->assertSee('GST (18%, included in total)', false);
        $response->assertSee('22.73', false);
    }

    // ------------------------------------------------------------------
    // Server-side trust: client values are never accepted
    // ------------------------------------------------------------------

    public function test_client_supplied_gst_fields_are_ignored_at_checkout(): void
    {
        $user = $this->makeUser();
        Cart::addBook($this->makeBook(), $user, 'test-session');

        // Self-generated test-only Razorpay credentials (mirrors
        // CheckoutTest; the gateway network call itself is stubbed).
        $settings = app(SettingsService::class);
        $settings->set('payments.razorpay_enabled', '1');
        $settings->set('payments.razorpay_mode', 'test');
        $settings->set('payments.razorpay_key_id', 'rzp_test_gst');
        $settings->set('payments.razorpay_key_secret', 'test_secret_gst');
        $settings->forgetCache();

        $partial = \Mockery::mock(RazorpayService::class, [app(SettingsService::class)])->makePartial();
        $partial->shouldReceive('createOrder')->andReturn(['id' => 'order_test_gst']);
        $this->app->instance(RazorpayService::class, $partial);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            // Hostile client values — must be ignored entirely.
            'gst_rate' => 0.01,
            'gst_amount' => 999.99,
        ]);

        $response->assertOk();

        $order = Order::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame('149.00', $order->grand_total);
        $this->assertSame('18.00', $order->gst_rate);
        $this->assertSame('22.73', $order->gst_amount);
    }

    // ------------------------------------------------------------------
    // Admin settings: validation of the rate
    // ------------------------------------------------------------------

    public function test_admin_gst_rate_setting_is_validated(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        // Over 100 rejected.
        $this->put('/reddy/settings', [
            'tab' => 'payments',
            's' => ['tax' => ['inr_gst_rate' => '150']],
        ])->assertSessionHasErrors('s.tax.inr_gst_rate');

        // Negative rejected.
        $this->put('/reddy/settings', [
            'tab' => 'payments',
            's' => ['tax' => ['inr_gst_rate' => '-5']],
        ])->assertSessionHasErrors('s.tax.inr_gst_rate');

        // Non-numeric rejected.
        $this->put('/reddy/settings', [
            'tab' => 'payments',
            's' => ['tax' => ['inr_gst_rate' => 'abc']],
        ])->assertSessionHasErrors('s.tax.inr_gst_rate');

        // Rate unchanged after rejected attempts.
        $this->assertSame(18.0, (float) app(SettingsService::class)->get('tax.inr_gst_rate'));
    }

    public function test_admin_can_change_gst_rate_via_settings(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->put('/reddy/settings', [
            'tab' => 'payments',
            's' => ['tax' => ['inr_gst_rate' => '12']],
        ])->assertRedirect();

        $this->assertSame(12.0, (float) app(SettingsService::class)->get('tax.inr_gst_rate'));

        // The settings page renders the new field in the payments tab.
        $response = $this->get('/reddy/settings?tab=payments');
        $response->assertOk();
        $response->assertSee('Indian digital-product GST rate', false);
    }

    public function test_gst_math_unit_cases(): void
    {
        $service = app(OrderService::class);

        // Exact division: 118 at 18% -> taxable 100.00, gst 18.00.
        $this->assertSame(['rate' => 18.0, 'taxable' => 100.0, 'gst' => 18.0], $service->gstBreakdown(118.0, 'INR'));

        // Zero total: no GST extracted.
        $this->assertSame(['rate' => 18.0, 'taxable' => 0.0, 'gst' => 0.0], $service->gstBreakdown(0.0, 'INR'));

        // USD: always zero.
        $this->assertSame(['rate' => 0.0, 'taxable' => 4.99, 'gst' => 0.0], $service->gstBreakdown(4.99, 'USD'));

        // Rate label trims trailing zeros.
        $this->assertSame('18', OrderService::gstRateLabel(18.0));
        $this->assertSame('18.5', OrderService::gstRateLabel(18.50));
    }
}
