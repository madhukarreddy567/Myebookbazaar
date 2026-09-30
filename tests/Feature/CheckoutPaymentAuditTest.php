<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\RazorpayService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Deep payment-audit regression tests: price tampering, signature
 * binding, idempotency, coupon safety, and the webhook state machine.
 *
 * Razorpay keys here are SELF-GENERATED test values stored in the test
 * database only — no real credentials. The real HMAC code paths run;
 * only the network call (order create) is stubbed via a partial mock.
 */
class CheckoutPaymentAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
        Mail::fake();
        Storage::fake('local');

        $settings = app(SettingsService::class);
        $settings->set('payments.razorpay_enabled', '1');
        $settings->set('payments.razorpay_mode', 'test');
        $settings->set('payments.razorpay_key_id', 'rzp_test_mebaudit');
        $settings->set('payments.razorpay_key_secret', 'test_secret_audit_xyz');
        $settings->set('payments.razorpay_webhook_secret', 'test_webhook_secret_audit');
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
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
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

    protected function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'AUDIT'.strtoupper(uniqid()),
            'type' => 'percent',
            'value' => 10,
            'currency' => 'BOTH',
            'max_uses_per_user' => 5,
            'is_active' => true,
            'applies_to' => 'all',
        ], $overrides));
    }

    protected function stubRazorpayOrderCreate(?string $fakeOrderId = null, ?callable $tap = null): void
    {
        $partial = \Mockery::mock(RazorpayService::class, [app(SettingsService::class)])->makePartial();

        if ($fakeOrderId) {
            $partial->shouldReceive('createOrder')->andReturn(['id' => $fakeOrderId]);
        } elseif ($tap) {
            $partial->shouldReceive('createOrder')->andReturnUsing(
                function ($receipt, $amount, $currency) use ($tap) {
                    $tap(compact('receipt', 'amount', 'currency'));

                    return ['id' => 'order_test_'.uniqid()];
                }
            );
        } else {
            $partial->shouldReceive('createOrder')->andReturnUsing(fn () => ['id' => 'order_test_'.uniqid()]);
        }

        $this->app->instance(RazorpayService::class, $partial);
    }

    protected function checkout(User $user, Book $book, string $fakeOrderId = 'order_test_audit'): array
    {
        $this->stubRazorpayOrderCreate($fakeOrderId);
        Cart::addBook($book, $user, 'test-session');

        $response = $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes']);
        $response->assertOk();

        return $response->json();
    }

    protected function signedWebhook(array $payload): array
    {
        $body = json_encode($payload);

        return [$body, hash_hmac('sha256', $body, 'test_webhook_secret_audit')];
    }

    protected function pendingOrderFor(User $user, Book $book, ?Coupon $coupon = null): Order
    {
        Cart::addBook($book, $user, 'test-session');

        return app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'razorpay');
    }

    // ------------------------------------------------------------------
    // 1. Double-submit idempotency
    // ------------------------------------------------------------------

    public function test_double_submit_reuses_single_order_instead_of_charging_twice(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        $first = $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes']);
        $first->assertOk();

        // Second submit of the same purchase (double click / retry).
        $second = $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes']);
        $second->assertOk();

        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertSame(1, Payment::count());

        // The second submit re-delivers the FIRST submit's params — the
        // customer pays once, through the original Razorpay order.
        $this->assertSame($first->json('razorpay_order_id'), $second->json('razorpay_order_id'));
        $this->assertSame($first->json('order_number'), $second->json('order_number'));
    }

    public function test_razorpay_order_carries_server_side_amount_currency_and_receipt(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook(['price_inr' => 299]);
        $captured = null;
        $this->stubRazorpayOrderCreate(null, function ($args) use (&$captured) {
            $captured = $args;
        });
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($order->amountInSubunits(), $captured['amount']);
        $this->assertSame($order->currency, $captured['currency']);
        $this->assertSame($order->order_number, $captured['receipt']);
        $this->assertSame(29900, $captured['amount']);
        $this->assertSame('INR', $captured['currency']);
    }

    // ------------------------------------------------------------------
    // 2. Signature binding
    // ------------------------------------------------------------------

    public function test_verify_rejects_signature_bound_to_a_different_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_bind_a');

        // Valid signature, but for a DIFFERENT order id — the classic
        // cheap-order-pays-for-expensive-order swap.
        $payload = [
            'razorpay_order_id' => 'order_test_bind_a',
            'razorpay_payment_id' => 'pay_test_bind',
            'razorpay_signature' => hash_hmac('sha256', 'order_test_bind_b|pay_test_bind', 'test_secret_audit_xyz'),
        ];

        $this->actingAs($user)->postJson('/checkout/verify', $payload)->assertStatus(422);

        $order = Order::first()->fresh();
        $this->assertFalse($order->isPaid());
        $this->assertSame(Order::STATUS_FAILED, $order->status);
    }

    public function test_verify_succeeds_with_correct_signature(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_bind_ok');

        $payload = [
            'razorpay_order_id' => 'order_test_bind_ok',
            'razorpay_payment_id' => 'pay_test_bind_ok',
            'razorpay_signature' => hash_hmac('sha256', 'order_test_bind_ok|pay_test_bind_ok', 'test_secret_audit_xyz'),
        ];

        $this->actingAs($user)->postJson('/checkout/verify', $payload)
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertTrue(Order::first()->fresh()->isPaid());
        $this->assertSame('pay_test_bind_ok', Payment::first()->fresh()->razorpay_payment_id);
    }

    // ------------------------------------------------------------------
    // 3. Gateway failure must not burn the coupon
    // ------------------------------------------------------------------

    public function test_gateway_failure_releases_coupon_and_fails_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['max_uses_per_user' => 1, 'value' => 50]);

        // Razorpay order creation fails (network / gateway down).
        $partial = \Mockery::mock(RazorpayService::class, [app(SettingsService::class)])->makePartial();
        $partial->shouldReceive('createOrder')->andReturn(null);
        $this->app->instance(RazorpayService::class, $partial);

        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)
            ->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(502);

        // The coupon usage was handed back — the customer can try again.
        $this->assertSame(0, $coupon->fresh()->uses_count);
        $this->assertSame(0, CouponUsage::count());

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(Order::STATUS_FAILED, $order->status);
    }

    // ------------------------------------------------------------------
    // 4. Coupon safety
    // ------------------------------------------------------------------

    public function test_flat_both_currency_coupon_is_rejected_at_checkout(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        // Misconfigured legacy row: a flat ₹100 that would otherwise be
        // subtracted from a $4.99 order and make it free.
        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 100, 'currency' => 'BOTH']);

        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)
            ->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'This coupon is misconfigured and cannot be used. Please contact support.']);

        $this->assertSame(0, Order::count());
    }

    public function test_flat_both_currency_coupon_is_rejected_when_applied(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 100, 'currency' => 'BOTH']);

        $this->actingAs($user)->post('/checkout/coupon', ['code' => $coupon->code])
            ->assertRedirect()
            ->assertSessionHasErrors('coupon');
    }

    public function test_deactivated_coupon_cannot_be_consumed_under_lock(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon();
        $order = $this->pendingOrderFor($user, $book);

        // Deactivated after the customer applied it.
        $coupon->update(['is_active' => false]);

        $this->expectException(CouponException::class);
        app(CouponService::class)->consume($order, $coupon, $user, collect([$book]), 149.0, 'INR');
    }

    public function test_expired_coupon_cannot_be_consumed_under_lock(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['ends_at' => now()->subDay()]);
        $order = $this->pendingOrderFor($user, $book);

        $this->expectException(CouponException::class);
        app(CouponService::class)->consume($order, $coupon, $user, collect([$book]), 149.0, 'INR');
    }

    public function test_expired_coupon_is_rejected_when_applied(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $coupon = $this->makeCoupon(['ends_at' => now()->subDay()]);

        $response = $this->actingAs($user)->post('/checkout/coupon', ['code' => $coupon->code]);
        $response->assertRedirect()->assertSessionHasErrors('coupon');
    }

    public function test_first_order_only_coupon_cannot_be_used_twice(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['first_order_only' => true, 'max_uses_per_user' => 5]);

        // First order: coupon applies and the order is paid.
        Cart::addBook($book, $user, 'test-session');
        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'razorpay');
        $order->forceFill(['status' => Order::STATUS_PAID])->save();

        // Second order: the coupon must now be rejected, even under lock.
        $order2 = $this->pendingOrderFor($user, $this->makeBook());

        $this->expectException(CouponException::class);
        app(CouponService::class)->consume($order2, $coupon, $user, collect([$book]), 149.0, 'INR');
    }

    public function test_free_coupon_double_submit_pays_once_and_keeps_single_usage(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100, 'max_uses_per_user' => 1]);

        Cart::addBook($book, $user, 'test-session');

        $payload = ['no_refund_accepted' => 'yes'];
        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', $payload)->assertOk();

        // Second submit: the coupon is spent, so this must fail cleanly.
        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', $payload)->assertStatus(422);

        $this->assertSame(1, Order::where('user_id', $user->id)->where('status', Order::STATUS_PAID)->count());
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, $coupon->fresh()->uses_count);
    }

    // ------------------------------------------------------------------
    // 5. Webhook correctness
    // ------------------------------------------------------------------

    public function test_order_paid_webhook_marks_paid_without_corrupting_payment_id(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_orderpaid');

        // order.paid carries the ORDER entity (id = order id), not a
        // payment entity — its id must never land in razorpay_payment_id.
        $payload = [
            'event' => 'order.paid',
            'payload' => ['order' => ['entity' => [
                'id' => 'order_test_orderpaid',
                'amount' => 14900,
                'currency' => 'INR',
            ]]],
        ];
        [$body, $sig] = $this->signedWebhook($payload);

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $order = Order::first()->fresh();
        $this->assertTrue($order->isPaid());

        $payment = Payment::first()->fresh();
        $this->assertTrue($payment->isCaptured());
        $this->assertNull($payment->razorpay_payment_id);
    }

    public function test_webhook_amount_mismatch_does_not_mark_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_amt');

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_test_amt',
                'order_id' => 'order_test_amt',
                'amount' => 100, // tampered: real order is 14900
                'currency' => 'INR',
            ]]],
        ];
        [$body, $sig] = $this->signedWebhook($payload);

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertFalse(Order::first()->fresh()->isPaid());
    }

    public function test_webhook_currency_mismatch_does_not_mark_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_cur');

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_test_cur',
                'order_id' => 'order_test_cur',
                'amount' => 14900,
                'currency' => 'USD', // tampered currency
            ]]],
        ];
        [$body, $sig] = $this->signedWebhook($payload);

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertOk();

        $this->assertFalse(Order::first()->fresh()->isPaid());
    }

    // ------------------------------------------------------------------
    // 6. State machine
    // ------------------------------------------------------------------

    public function test_report_failure_cannot_unpay_a_paid_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_nounpay');

        $this->actingAs($user)->postJson('/checkout/verify', [
            'razorpay_order_id' => 'order_test_nounpay',
            'razorpay_payment_id' => 'pay_test_nounpay',
            'razorpay_signature' => hash_hmac('sha256', 'order_test_nounpay|pay_test_nounpay', 'test_secret_audit_xyz'),
        ])->assertOk();

        $this->assertTrue(Order::first()->fresh()->isPaid());

        // A later failure report must not move paid -> failed.
        $this->actingAs($user)->postJson('/checkout/report-failure', [
            'razorpay_order_id' => 'order_test_nounpay',
            'description' => 'attacker tries to unpay',
        ])->assertOk();

        $this->assertTrue(Order::first()->fresh()->isPaid());
    }

    public function test_failed_order_can_still_be_paid_on_retry(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->checkout($user, $book, 'order_test_retry');

        // First attempt fails at the gateway.
        $this->actingAs($user)->postJson('/checkout/report-failure', [
            'razorpay_order_id' => 'order_test_retry',
            'description' => 'payment failed',
        ])->assertOk();
        $this->assertSame(Order::STATUS_FAILED, Order::first()->fresh()->status);

        // Customer retries on the same Razorpay order and succeeds.
        $this->actingAs($user)->postJson('/checkout/verify', [
            'razorpay_order_id' => 'order_test_retry',
            'razorpay_payment_id' => 'pay_test_retry',
            'razorpay_signature' => hash_hmac('sha256', 'order_test_retry|pay_test_retry', 'test_secret_audit_xyz'),
        ])->assertOk();

        $this->assertTrue(Order::first()->fresh()->isPaid());
    }

    // ------------------------------------------------------------------
    // 7. Edge cases
    // ------------------------------------------------------------------

    public function test_empty_cart_checkout_returns_422_not_500(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Your cart is empty.']);
    }

    public function test_deactivated_book_mid_checkout_returns_422_not_500(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        // The book is pulled between page view and submit.
        $book->update(['is_active' => false]);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Your cart is empty.']);
    }

    public function test_order_creation_race_returns_422_instead_of_500(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Simulate the cart being emptied between the page view and the
        // order creation (another tab / race): OrderService throws
        // RuntimeException, which must become a 422, not a 500.
        $mock = \Mockery::mock(OrderService::class)->makePartial();
        $mock->shouldReceive('createFromCart')->andThrow(new \RuntimeException('Your cart is empty.'));
        $this->app->instance(OrderService::class, $mock);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Your cart is empty.']);
    }

    // ------------------------------------------------------------------
    // 8. Order-number retry helper
    // ------------------------------------------------------------------

    public function test_order_number_collision_is_retried_not_fatal(): void
    {
        $service = app(OrderService::class);
        $method = new \ReflectionMethod(OrderService::class, 'createInTransaction');
        $method->setAccessible(true);

        $attempts = 0;
        $result = $method->invoke($service, function () use (&$attempts) {
            $attempts++;

            if ($attempts < 3) {
                throw new QueryException('mysql', 'insert into `orders`', [], new \Exception(
                    "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'MEB-X' for key 'orders.order_number'"
                ));
            }

            return 'created';
        });

        $this->assertSame('created', $result);
        $this->assertSame(3, $attempts);
    }

    public function test_non_duplicate_query_errors_are_not_retried(): void
    {
        $service = app(OrderService::class);
        $method = new \ReflectionMethod(OrderService::class, 'createInTransaction');
        $method->setAccessible(true);

        $attempts = 0;

        try {
            $method->invoke($service, function () use (&$attempts) {
                $attempts++;
                throw new QueryException('mysql', 'select 1', [], new \Exception('connection lost'));
            });
            $this->fail('Expected QueryException was not thrown.');
        } catch (QueryException) {
            $this->assertSame(1, $attempts);
        }
    }
}
