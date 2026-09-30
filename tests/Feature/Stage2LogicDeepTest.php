<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Mail\DownloadReadyMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\AuditLog;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * STAGE 2 — LOGIC DEEP audit: money-path state machine, checkout races,
 * webhook idempotency, and ADVERSARIAL price-tampering tests.
 *
 * Every "cannot happen" claim in the audit is locked in here:
 *  - paid → failed is impossible (transition map, row-locked)
 *  - refunded → paid is impossible (refunded is terminal)
 *  - double submit / replayed webhook can never double-charge
 *  - no client-submitted value (price, coupon, book_id, currency) can
 *    change what the customer pays — everything is re-resolved from
 *    the database server-side at checkout time.
 *
 * Razorpay keys here are SELF-GENERATED test values stored in the test
 * database only — no real credentials. The real HMAC code paths run;
 * only the network call (order create) is stubbed via a partial mock.
 */
class Stage2LogicDeepTest extends TestCase
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
        $settings->set('payments.razorpay_key_id', 'rzp_test_mebstage2');
        $settings->set('payments.razorpay_key_secret', 'test_secret_stage2_xyz');
        $settings->set('payments.razorpay_webhook_secret', 'test_webhook_secret_stage2');
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

    protected function makeCoupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'STAGE2'.strtoupper(uniqid()),
            'type' => 'percent',
            'value' => 10,
            'currency' => 'BOTH',
            'max_uses_per_user' => 5,
            'is_active' => true,
            'applies_to' => 'all',
        ], $overrides));
    }

    protected function stubRazorpayOrderCreate(?string $fakeOrderId = null): void
    {
        $partial = \Mockery::mock(RazorpayService::class, [app(SettingsService::class)])->makePartial();

        if ($fakeOrderId) {
            $partial->shouldReceive('createOrder')->andReturn(['id' => $fakeOrderId]);
        } else {
            $partial->shouldReceive('createOrder')
                ->andReturnUsing(fn () => ['id' => 'order_test_'.uniqid()]);
        }

        $this->app->instance(RazorpayService::class, $partial);
    }

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

    protected function pendingOrderFor(User $user, Book $book, ?Coupon $coupon = null): Order
    {
        Cart::addBook($book, $user, 'test-session');

        return app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'razorpay');
    }

    protected function signedWebhook(array $payload): array
    {
        $body = json_encode($payload);
        $sig = hash_hmac('sha256', $body, 'test_webhook_secret_stage2');

        return [$body, $sig];
    }

    protected function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        [$body, $sig] = $this->signedWebhook($payload);

        return $this->call(
            'POST',
            '/webhooks/razorpay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => $sig],
            $body
        );
    }

    protected function capturedPayload(string $razorpayOrderId, string $paymentId, int $amountSubunits, string $currency): array
    {
        return [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => $paymentId,
                'order_id' => $razorpayOrderId,
                'amount' => $amountSubunits,
                'currency' => $currency,
            ]]],
        ];
    }

    // ------------------------------------------------------------------
    // 1. Order state machine — illegal transitions are impossible
    // ------------------------------------------------------------------

    public function test_paid_order_can_never_transition_to_failed(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);
        $payment = Payment::create([
            'order_id' => $order->id, 'amount' => 149, 'currency' => 'INR',
            'status' => Payment::STATUS_CAPTURED,
        ]);

        app(OrderService::class)->markFailed($order, $payment, 'gateway says failed');

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_CAPTURED, $payment->fresh()->status);
    }

    public function test_failed_order_can_be_paid_again_only_via_reverified_payment(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->pendingOrderFor($user, $book);
        $payment = Payment::create([
            'order_id' => $order->id, 'razorpay_order_id' => 'order_test_f2p',
            'amount' => 149, 'currency' => 'INR', 'status' => Payment::STATUS_CREATED,
        ]);

        // Failure recorded honestly...
        app(OrderService::class)->markFailed($order, $payment, 'card declined');
        $this->assertSame(Order::STATUS_FAILED, $order->fresh()->status);

        // ...then the verified webhook for the SAME payment recovers it.
        $this->postWebhook($this->capturedPayload('order_test_f2p', 'pay_test_f2p', 14900, 'INR'))
            ->assertOk();

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_CAPTURED, $payment->fresh()->status);
    }

    public function test_refund_is_only_possible_from_paid(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $orders = app(OrderService::class);

        $pending = $this->pendingOrderFor($user, $book);
        $orders->markRefunded($pending, $admin->id, 'refund requested, ticket #1');
        $this->assertSame(Order::STATUS_PENDING, $pending->fresh()->status);

        $paid = $this->makePaidOrder($user, $book);
        $orders->markRefunded($paid, $admin->id, 'refund requested, ticket #2');
        $this->assertSame(Order::STATUS_REFUNDED, $paid->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'order.refunded')
            ->where('target_id', $paid->id)->exists());
    }

    public function test_refunded_order_can_never_become_paid_again(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $orders = app(OrderService::class);

        $order = $this->makePaidOrder($user, $book);
        $payment = Payment::create([
            'order_id' => $order->id, 'razorpay_order_id' => 'order_test_refund',
            'amount' => 149, 'currency' => 'INR', 'status' => Payment::STATUS_CAPTURED,
        ]);
        $orders->markRefunded($order, $admin->id, 'money returned via Razorpay dashboard');

        // 1. Service-level markPaid refuses.
        $orders->markPaid($order->fresh(), null, 'webhook');
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);

        // 2. Admin manual mark-as-paid refuses too.
        $orders->markPaidManually($order->fresh(), $admin->id, 'customer claims they paid again, note long enough');
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'order.mark_paid_manual')
            ->where('target_id', $order->id)->count());

        // 3. A replayed payment.captured webhook is a no-op.
        $this->postWebhook($this->capturedPayload('order_test_refund', 'pay_test_refund', 14900, 'INR'))
            ->assertOk();
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);

        // 4. markFailed is a no-op as well (refunded is terminal).
        $orders->markFailed($order->fresh(), $payment->fresh(), 'late failure notice');
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
    }

    public function test_cancelled_order_can_never_become_paid(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $orders = app(OrderService::class);

        $order = $this->pendingOrderFor($user, $book);
        $order->forceFill(['status' => Order::STATUS_CANCELLED])->save();

        $orders->markPaid($order->fresh(), null, 'checkout');
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);

        $orders->markPaidManually($order->fresh(), $admin->id, 'manual attempt on cancelled order, long note');
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_two_admins_marking_paid_is_safe_second_is_noop(): void
    {
        $admin1 = $this->makeAdmin();
        $admin2 = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $orders = app(OrderService::class);

        $order = $this->pendingOrderFor($user, $book);

        $orders->markPaidManually($order, $admin1->id, 'UPI received, UTR 1111111111');
        $orders->markPaidManually($order->fresh(), $admin2->id, 'second admin also tried, UTR 2222222222');

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(1, AuditLog::where('action', 'order.mark_paid_manual')
            ->where('target_id', $order->id)->count());
    }

    public function test_admin_cannot_mark_refunded_order_paid_via_http(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        app(OrderService::class)->markRefunded($order, $admin->id, 'refunded, ticket #9');

        $this->actingAs($admin, 'admin')
            ->post(route('reddy.orders.mark-paid', $order), ['note' => 'trying to re-mark a refunded order paid'])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
    }

    // ------------------------------------------------------------------
    // 2. Webhook idempotency + tamper guard
    // ------------------------------------------------------------------

    public function test_replayed_captured_webhook_marks_paid_exactly_once(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate('order_test_replay');
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();
        $order = Order::where('user_id', $user->id)->firstOrFail();

        $payload = $this->capturedPayload('order_test_replay', 'pay_test_replay', 14900, 'INR');
        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk(); // replayed delivery

        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertSame(1, Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_CAPTURED)->count());
        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(DownloadReadyMail::class, 1);
    }

    public function test_webhook_with_wrong_amount_does_not_mark_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate('order_test_tamper');
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();
        $order = Order::where('user_id', $user->id)->firstOrFail();

        // Attacker replays a captured event for ₹1.00 instead of ₹149.00.
        $this->postWebhook($this->capturedPayload('order_test_tamper', 'pay_test_tamper', 100, 'INR'))
            ->assertOk();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_CREATED, $order->payments()->first()->fresh()->status);
    }

    public function test_webhook_with_wrong_currency_does_not_mark_paid(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate('order_test_cur');
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();
        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->postWebhook($this->capturedPayload('order_test_cur', 'pay_test_cur', 14900, 'USD'))
            ->assertOk();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_webhook_with_bad_signature_is_rejected(): void
    {
        $payload = $this->capturedPayload('order_test_x', 'pay_x', 14900, 'INR');

        $this->call(
            'POST',
            '/webhooks/razorpay',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => 'bogus'],
            json_encode($payload)
        )->assertStatus(400);
    }

    // ------------------------------------------------------------------
    // 3. Checkout races: double submit, crash window, free orders
    // ------------------------------------------------------------------

    public function test_crash_between_order_and_razorpay_is_recovered_without_duplicate_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate('order_test_recovery');
        Cart::addBook($book, $user, 'test-session');

        // Simulate the crash: order row committed, but the process died
        // before the Razorpay order / payment row was attached.
        $dangling = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, null, 'razorpay');
        $this->assertSame(0, Payment::where('order_id', $dangling->id)->count());

        $response = $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes']);
        $response->assertOk();

        // One order, not two — the retry attaches a fresh Razorpay order
        // to the EXISTING order instead of duplicating it.
        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertSame(1, Payment::count());
        $this->assertSame($dangling->id, Payment::first()->order_id);
        $this->assertSame($dangling->order_number, $response->json('order_number'));
        $this->assertSame('order_test_recovery', $response->json('razorpay_order_id'));
    }

    public function test_crash_recovery_does_not_consume_coupon_twice(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['value' => 50, 'max_uses_per_user' => 1]);
        $this->stubRazorpayOrderCreate('order_test_recovery2');
        Cart::addBook($book, $user, 'test-session');

        // Crash with a single-use coupon already consumed by the order.
        $dangling = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'razorpay');
        $this->assertSame(1, $coupon->fresh()->uses_count);

        $response = $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes']);
        $response->assertOk();

        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, $coupon->fresh()->uses_count);
        $this->assertSame($dangling->order_number, $response->json('order_number'));
    }

    public function test_free_order_double_submit_creates_exactly_one_paid_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100, 'max_uses_per_user' => 1]);
        Cart::addBook($book, $user, 'test-session');

        $payload = ['no_refund_accepted' => 'yes'];
        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', $payload)->assertOk();

        // Sequential double submit: the coupon is spent, so the second
        // submit fails cleanly — and even under true concurrency the
        // per-user checkout lock serialises the two requests.
        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', $payload)->assertStatus(422);

        $this->assertSame(1, Order::where('user_id', $user->id)->where('status', Order::STATUS_PAID)->count());
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        $this->assertSame('coupon', Order::where('user_id', $user->id)->first()->payment_method);
    }

    public function test_free_order_never_touches_razorpay(): void
    {
        $partial = \Mockery::mock(RazorpayService::class, [app(SettingsService::class)])->makePartial();
        $partial->shouldReceive('createOrder')->never();
        $this->app->instance(RazorpayService::class, $partial);

        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100]);
        Cart::addBook($book, $user, 'test-session');

        $response = $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes']);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($order->isPaid());
        $this->assertEquals(0, (float) $order->grand_total);
        $payment = $order->payments()->firstOrFail();
        $this->assertSame(Payment::STATUS_CAPTURED, $payment->status);
        $this->assertEquals(0, (float) $payment->amount);
    }

    public function test_empty_cart_at_submit_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_deactivated_book_between_cart_and_submit_is_excluded(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        $book->update(['is_active' => false]);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    // ------------------------------------------------------------------
    // 4. ADVERSARIAL price tampering — the client controls nothing
    // ------------------------------------------------------------------

    public function test_tampered_price_fields_in_payload_are_ignored(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook(['price_inr' => 149]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Attacker tries every plausible money field at once.
        $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'grand_total' => 1,
            'amount' => 100,
            'total' => 1,
            'subtotal' => 1,
            'price' => 1,
            'discount' => 148,
        ])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(149, (float) $order->grand_total);
        $this->assertEquals(149, (float) $order->subtotal);
        $this->assertSame('INR', $order->currency);
        $this->assertSame(14900, $order->amountInSubunits());
    }

    public function test_price_change_in_db_between_cart_and_submit_is_recomputed(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook(['price_inr' => 149]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Admin raises the price after the book sat in the cart.
        $book->update(['price_inr' => 199]);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(199, (float) $order->grand_total);
        $this->assertEquals(199, (float) $order->items()->first()->unit_price);
    }

    public function test_swapped_book_id_in_payload_is_ignored(): void
    {
        $user = $this->makeUser();
        $expensive = $this->makeBook(['price_inr' => 499]);
        $cheap = $this->makeBook(['price_inr' => 49]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($expensive, $user, 'test-session');

        // Attacker claims they are buying the cheap book (and other junk).
        $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'book_id' => $cheap->id,
            'book_ids' => [$cheap->id],
            'items' => [['book_id' => $cheap->id, 'price' => 49]],
        ])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame([$expensive->id], $order->items()->pluck('book_id')->all());
        $this->assertEquals(499, (float) $order->grand_total);
    }

    public function test_forged_coupon_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->withSession(['coupon_code' => 'FORGED99'])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_expired_coupon_is_rejected_at_submit(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['value' => 50, 'ends_at' => now()->subMinute()]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Applied while live, expired before submit.
        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, CouponUsage::count());
    }

    public function test_deactivated_coupon_between_apply_and_submit_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['value' => 50]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Admin kills the coupon after the customer applied it.
        $coupon->update(['is_active' => false]);

        $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes'])
            ->assertStatus(422);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, CouponUsage::count());
    }

    public function test_currency_field_in_payload_is_ignored_region_decides(): void
    {
        $user = $this->makeUser(['pricing_region' => 'IN']);
        $book = $this->makeBook(['price_inr' => 149, 'price_usd' => 4.99]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Attacker asks for USD totals on an INR-priced region.
        $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
            'currency' => 'USD',
        ])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('INR', $order->currency);
        $this->assertEquals(149, (float) $order->grand_total);
    }

    public function test_region_switch_mid_checkout_recomputes_at_real_prices(): void
    {
        $user = $this->makeUser(['pricing_region' => 'IN']);
        $book = $this->makeBook(['price_inr' => 149, 'price_usd' => 4.99]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Customer's region changes between page view and submit: the
        // order is priced fresh in USD at the real USD price — not a
        // converted or client-supplied total.
        $user->update(['pricing_region' => 'INTL']);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('USD', $order->currency);
        $this->assertEquals(4.99, (float) $order->grand_total);
        $this->assertSame(499, $order->amountInSubunits());
    }

    public function test_sale_ending_between_view_and_submit_charges_regular_price(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook([
            'price_inr' => 149,
            'sale_price_inr' => 99,
            'sale_ends_at' => now()->addMinute(),
        ]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        // Sale expires after the checkout page rendered.
        $book->update(['sale_ends_at' => now()->subMinute()]);

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(149, (float) $order->grand_total);
    }

    public function test_live_sale_price_is_honoured_not_regular(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook([
            'price_inr' => 149,
            'sale_price_inr' => 99,
            'sale_starts_at' => now()->subHour(),
            'sale_ends_at' => now()->addHour(),
        ]);
        $this->stubRazorpayOrderCreate();
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->postJson('/checkout', ['no_refund_accepted' => 'yes'])->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(99, (float) $order->grand_total);
    }

    public function test_free_order_crash_is_resumed_not_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100, 'max_uses_per_user' => 1]);
        Cart::addBook($book, $user, 'test-session');

        // Crash: the free order row (and coupon usage) committed, but the
        // synchronous completion never ran.
        $dangling = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'coupon');
        $this->assertSame(Order::STATUS_PENDING, $dangling->fresh()->status);

        $response = $this->actingAs($user)->withSession(['coupon_code' => $coupon->code])
            ->postJson('/checkout', ['no_refund_accepted' => 'yes']);

        $response->assertOk()->assertJsonStructure(['redirect']);

        // Resumed, not duplicated and not rejected: one order, paid, one
        // coupon usage.
        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertTrue($dangling->fresh()->isPaid());
        $this->assertSame(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, $coupon->fresh()->uses_count);
    }
}
