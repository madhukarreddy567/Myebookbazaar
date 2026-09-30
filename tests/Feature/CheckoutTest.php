<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Cart;
use App\Models\DownloadLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\RazorpayService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Phase 3: Razorpay checkout, orders, secure downloads.
 *
 * Razorpay keys used here are SELF-GENERATED test values stored in the
 * test database only — no real credentials. The real HMAC signature
 * code paths are exercised; only the network call (order create) is
 * stubbed via a partial mock.
 */
class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
        Mail::fake();
        Storage::fake('local');

        // Self-generated test-only Razorpay credentials.
        $settings = app(SettingsService::class);
        $settings->set('payments.razorpay_enabled', '1');
        $settings->set('payments.razorpay_mode', 'test');
        $settings->set('payments.razorpay_key_id', 'rzp_test_mebphase3');
        $settings->set('payments.razorpay_key_secret', 'test_secret_phase3_xyz');
        $settings->set('payments.razorpay_webhook_secret', 'test_webhook_secret_xyz');
        $settings->forgetCache();
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
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => 'password123',
            'country_code' => 'IN',
            'country_name' => 'India',
            'pricing_region' => 'IN',
        ], $overrides));

        // email_verified_at is not mass-assignable — set it directly.
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    protected function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);
    }

    protected function makeBook(array $overrides = []): Book
    {
        Storage::disk('local')->put('ebooks/test-'.uniqid().'.pdf', '%PDF-1.4 fake');
        $path = collect(Storage::disk('local')->files('ebooks'))->first();

        return Book::create(array_merge([
            'title' => 'Test Book '.uniqid(),
            'slug' => 'test-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'file_path' => $path,
            'is_active' => true,
        ], $overrides));
    }

    protected function addToCart(User $user, Book $book): void
    {
        Cart::addBook($book, $user, 'test-session');
    }

    /**
     * Bind a RazorpayService whose network call (order create) is stubbed
     * but whose HMAC signature verification runs the REAL code.
     * With no argument, each call returns a UNIQUE fake order id.
     */
    protected function stubRazorpayOrderCreate(?string $fakeOrderId = null): void
    {
        $partial = \Mockery::mock(
            RazorpayService::class,
            [app(SettingsService::class)]
        )->makePartial();

        if ($fakeOrderId) {
            $partial->shouldReceive('createOrder')->andReturn(['id' => $fakeOrderId]);
        } else {
            $partial->shouldReceive('createOrder')
                ->andReturnUsing(fn () => ['id' => 'order_test_'.uniqid()]);
        }

        $this->app->instance(RazorpayService::class, $partial);
    }

    protected function checkout(User $user, Book $book, string $fakeOrderId = 'order_test_stubbed'): array
    {
        $this->stubRazorpayOrderCreate($fakeOrderId);
        $this->addToCart($user, $book);

        $response = $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
        ]);

        $response->assertOk();

        return $response->json();
    }

    protected function webhookPayload(string $event, array $entity): array
    {
        return [
            'event' => $event,
            'payload' => ['payment' => ['entity' => $entity]],
        ];
    }

    protected function signedWebhook(array $payload): array
    {
        $body = json_encode($payload);
        $sig = hash_hmac('sha256', $body, 'test_webhook_secret_xyz');

        return [$body, $sig];
    }

    // ------------------------------------------------------------------
    // Checkout access + validation
    // ------------------------------------------------------------------

    public function test_guest_cannot_open_checkout(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_checkout_requires_no_refund_acceptance(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        $this->addToCart($user, $book);

        $this->actingAs($user)->postJson('/checkout', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('no_refund_accepted');

        $this->assertSame(0, Order::count());
    }

    public function test_browser_supplied_money_values_are_ignored(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        $this->addToCart($user, $book);

        // An attacker tries to set their own total — the server ignores it.
        $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'grand_total' => 1,
            'amount' => 100,
        ])->assertOk();

        $order = Order::first();
        $this->assertEquals(149, (float) $order->grand_total);
        $this->assertSame('INR', $order->currency);
    }

    public function test_checkout_shows_message_when_razorpay_not_configured(): void
    {
        app(SettingsService::class)->set('payments.razorpay_key_secret', null);
        app(SettingsService::class)->forgetCache();

        $user = $this->makeUser();
        $this->addToCart($user, $this->makeBook());

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee('not set up yet');
    }

    // ------------------------------------------------------------------
    // Payment verification
    // ------------------------------------------------------------------

    public function test_valid_signature_marks_order_paid_and_clears_cart(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $data = $this->checkout($user, $book, 'order_test_valid');

        $paymentId = 'pay_test_valid';
        $signature = hash_hmac('sha256', 'order_test_valid|'.$paymentId, 'test_secret_phase3_xyz');

        $this->actingAs($user)->postJson('/checkout/verify', [
            'razorpay_order_id' => 'order_test_valid',
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ])->assertOk()->assertJsonPath('redirect', route('checkout.thankyou', Order::first()));

        $order = Order::first()->fresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame('paid', $order->status);
        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
        $this->assertSame($paymentId, $order->payments()->first()->razorpay_payment_id);
        $this->assertTrue($order->no_refund_accepted);
    }

    public function test_tampered_signature_marks_order_failed(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_tampered');

        $this->actingAs($user)->postJson('/checkout/verify', [
            'razorpay_order_id' => 'order_test_tampered',
            'razorpay_payment_id' => 'pay_test_tampered',
            'razorpay_signature' => 'forged-signature',
        ])->assertStatus(422);

        $order = Order::first();
        $this->assertSame(Order::STATUS_FAILED, $order->status);
        $this->assertSame(Payment::STATUS_FAILED, $order->payments()->first()->status);
    }

    public function test_verify_is_idempotent_on_double_submit(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_double');

        $payload = [
            'razorpay_order_id' => 'order_test_double',
            'razorpay_payment_id' => 'pay_test_double',
            'razorpay_signature' => hash_hmac('sha256', 'order_test_double|pay_test_double', 'test_secret_phase3_xyz'),
        ];

        $this->actingAs($user)->postJson('/checkout/verify', $payload)->assertOk();
        $this->actingAs($user)->postJson('/checkout/verify', $payload)->assertOk();

        $this->assertSame(1, Payment::count());
        $this->assertTrue(Order::first()->isPaid());
    }

    // ------------------------------------------------------------------
    // Webhooks
    // ------------------------------------------------------------------

    public function test_webhook_marks_order_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_hook');

        [$body, $sig] = $this->signedWebhook($this->webhookPayload('payment.captured', [
            'id' => 'pay_hook_1',
            'order_id' => 'order_test_hook',
            'amount' => 14900,
            'currency' => 'INR',
        ]));

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertTrue(Order::first()->fresh()->isPaid());

        // The raw webhook is stored as a decoded array (not double-encoded).
        $stored = Payment::first()->fresh()->raw_webhook;
        $this->assertIsArray($stored);
        $this->assertSame('payment.captured', $stored['event']);
    }

    public function test_replayed_webhook_is_idempotent(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_replay');

        $payload = $this->webhookPayload('payment.captured', [
            'id' => 'pay_replay_1',
            'order_id' => 'order_test_replay',
            'amount' => 14900,
            'currency' => 'INR',
        ]);
        [$body, $sig] = $this->signedWebhook($payload);

        // Post the raw body twice (a replayed delivery).
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/webhooks/razorpay', [], [], [], [
                'HTTP_X-Razorpay-Signature' => $sig,
                'CONTENT_TYPE' => 'application/json',
            ], $body)->assertOk();
        }

        $order = Order::first()->fresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_badsig');

        [$body] = $this->signedWebhook($this->webhookPayload('payment.captured', [
            'id' => 'pay_bad_1',
            'order_id' => 'order_test_badsig',
            'amount' => 14900,
            'currency' => 'INR',
        ]));

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'wrong',
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(400);

        $this->assertSame(Order::STATUS_PENDING, Order::first()->fresh()->status);
    }

    public function test_webhook_with_tampered_amount_does_not_mark_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_amt');

        // Valid signature, but the amount was tampered with in transit.
        [$body, $sig] = $this->signedWebhook($this->webhookPayload('payment.captured', [
            'id' => 'pay_amt_1',
            'order_id' => 'order_test_amt',
            'amount' => 100, // ₹1 instead of ₹149
            'currency' => 'INR',
        ]));

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertSame(Order::STATUS_PENDING, Order::first()->fresh()->status);
    }

    public function test_webhook_payment_failed_marks_order_failed(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_fail');

        [$body, $sig] = $this->signedWebhook($this->webhookPayload('payment.failed', [
            'id' => 'pay_fail_1',
            'order_id' => 'order_test_fail',
            'amount' => 14900,
            'currency' => 'INR',
            'error_description' => 'Insufficient funds',
        ]));

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertSame(Order::STATUS_FAILED, Order::first()->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Downloads
    // ------------------------------------------------------------------

    protected function makePaidOrder(User $user, Book $book): Order
    {
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

        return $order;
    }

    protected function downloadUrl(Order $order, Book $book): string
    {
        return URL::temporarySignedRoute('download', now()->addMinutes(10), [
            'order' => $order->id,
            'book' => $book->id,
        ]);
    }

    public function test_owner_can_download(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user)->get($this->downloadUrl($order, $book))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame(1, DownloadLog::usedCount($order->id, $book->id));
    }

    public function test_non_owner_cannot_download(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser(['email' => 'intruder-'.uniqid().'@example.com']);
        $book = $this->makeBook();
        $order = $this->makePaidOrder($owner, $book);

        $this->actingAs($intruder)->get($this->downloadUrl($order, $book))
            ->assertNotFound();

        $this->assertSame(0, DownloadLog::count());
    }

    public function test_sixth_download_is_blocked(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        for ($i = 0; $i < 5; $i++) {
            DownloadLog::create([
                'user_id' => $user->id, 'book_id' => $book->id,
                'order_id' => $order->id, 'ip_address' => '127.0.0.1',
                'downloaded_at' => now(),
            ]);
        }

        $this->actingAs($user)->get($this->downloadUrl($order, $book))
            ->assertRedirect();

        $this->assertSame(5, DownloadLog::usedCount($order->id, $book->id));
    }

    public function test_unpaid_order_cannot_download(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);
        $order->update(['status' => Order::STATUS_PENDING]);

        $this->actingAs($user)->get($this->downloadUrl($order, $book))
            ->assertForbidden();
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $url = URL::temporarySignedRoute('download', now()->subMinute(), [
            'order' => $order->id,
            'book' => $book->id,
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();
    }

    public function test_private_pdf_has_no_public_url(): void
    {
        $book = $this->makeBook();

        $this->get('/storage/'.$book->file_path)->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Currency separation
    // ------------------------------------------------------------------

    public function test_inr_and_usd_orders_never_mix(): void
    {
        $inUser = $this->makeUser();
        $usUser = $this->makeUser([
            'email' => 'us-'.uniqid().'@example.com',
            'country_code' => 'US',
            'country_name' => 'United States',
            'pricing_region' => 'INTL',
        ]);
        $book = $this->makeBook();

        $this->stubRazorpayOrderCreate(); // unique fake ids per checkout
        $this->addToCart($inUser, $book);
        $this->actingAs($inUser)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $this->addToCart($usUser, $book);
        $this->actingAs($usUser)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $orders = Order::orderBy('id')->get();
        $this->assertSame('INR', $orders[0]->currency);
        $this->assertEquals(149, (float) $orders[0]->grand_total);
        $this->assertSame('USD', $orders[1]->currency);
        $this->assertEquals(4.99, (float) $orders[1]->grand_total);
    }

    // ------------------------------------------------------------------
    // Page rendering (all customer + admin pages compile and render)
    // ------------------------------------------------------------------

    public function test_all_phase3_pages_render(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();
        $admin = $this->makeAdmin();
        $book = $this->makeBook();

        // Checkout page with items in cart.
        $this->addToCart($user, $book);
        $this->actingAs($user)->get('/checkout')->assertOk();

        // Empty cart checkout page.
        $user2 = $this->makeUser();
        $this->actingAs($user2)->get('/checkout')->assertOk();

        // Paid order → orders, order detail, thank-you, library pages.
        $order = $this->makePaidOrder($user, $book, 'INR');
        $this->actingAs($user)->get('/orders')->assertOk();
        $this->actingAs($user)->get('/orders/'.$order->id)->assertOk();
        $this->actingAs($user)->get('/checkout/thank-you/'.$order->id)->assertOk();
        $this->actingAs($user)->get('/library')->assertOk();

        // Another customer cannot see this order (404 hides its existence).
        $this->actingAs($user2)->get('/orders/'.$order->id)->assertNotFound();

        // Admin pages.
        $this->actingAs($admin, 'admin')->get('/reddy/orders')->assertOk();
        $this->actingAs($admin, 'admin')->get('/reddy/orders/'.$order->id)->assertOk();
    }

    public function test_admin_can_mark_order_paid_with_note(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);
        $order->update(['status' => Order::STATUS_PENDING]);

        $this->actingAs($admin, 'admin')
            ->post(route('reddy.orders.mark-paid', $order), ['note' => 'UPI payment received, UTR 1234567890'])
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue(AuditLog::where('action', 'order.mark_paid_manual')
            ->where('target_id', $order->id)->exists());
    }

    public function test_admin_mark_paid_requires_note(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);
        $order->update(['status' => Order::STATUS_PENDING]);

        $this->actingAs($admin, 'admin')
            ->post(route('reddy.orders.mark-paid', $order), ['note' => 'short'])
            ->assertSessionHasErrors('note');

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_admin_orders_list_shows_separate_revenue(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->makePaidOrder($user, $book);

        $this->actingAs($admin, 'admin')->get(route('reddy.orders.index'))
            ->assertOk()
            ->assertSee('Revenue (INR)')
            ->assertSee('Revenue (USD)');
    }

    public function test_customer_cannot_open_admin_orders(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('reddy.orders.index'))
            ->assertRedirect();
    }

    // ------------------------------------------------------------------
    // Payment-failure landing page
    // ------------------------------------------------------------------

    protected function makeFailedOrder(User $user, Book $book): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateNumber(),
            'currency' => 'INR',
            'subtotal' => 149,
            'grand_total' => 149,
            'status' => Order::STATUS_FAILED,
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

        return $order->fresh();
    }

    public function test_report_failure_marks_order_failed_and_returns_redirect(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book);

        $order = Order::where('user_id', $user->id)->first();
        $payment = Payment::where('order_id', $order->id)->first();

        $response = $this->actingAs($user)->postJson('/checkout/report-failure', [
            'razorpay_order_id' => $payment->razorpay_order_id,
            'description' => 'UPI gateway timeout',
        ]);

        $response->assertOk()
            ->assertJsonPath('redirect', route('checkout.payment-failed', $order));

        $this->assertTrue($order->fresh()->isFailed());
    }

    public function test_payment_failed_page_renders_for_failed_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makeFailedOrder($user, $book);

        $this->actingAs($user)->get(route('checkout.payment-failed', $order))
            ->assertOk()
            ->assertSee("Payment Didn't Go Through", false)
            ->assertSee($order->order_number)
            ->assertSee('Try Payment Again')
            ->assertSee('Back to Cart');
    }

    public function test_payment_failed_page_404_for_paid_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user)->get(route('checkout.payment-failed', $order))
            ->assertNotFound();
    }

    public function test_payment_failed_page_404_for_pending_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book);

        $order = Order::where('user_id', $user->id)->first();

        $this->actingAs($user)->get(route('checkout.payment-failed', $order))
            ->assertNotFound();
    }

    public function test_payment_failed_page_404_for_other_users_order(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makeFailedOrder($owner, $book);

        $this->actingAs($intruder)->get(route('checkout.payment-failed', $order))
            ->assertNotFound();
    }

    public function test_guest_cannot_open_payment_failed_page(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makeFailedOrder($user, $book);

        $this->get(route('checkout.payment-failed', $order))
            ->assertRedirect('/login');
    }
}
