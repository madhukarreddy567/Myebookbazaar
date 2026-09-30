<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Models\Admin;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\DownloadLog;
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
 * STAGE 2 — LOGIC DEEP audit, part 2: download gates + limit race,
 * coupon consume serialization + re-validation, sale/coupon date
 * windows (server timezone), and the invoice method label.
 *
 * Razorpay keys here are SELF-GENERATED test values stored in the test
 * database only — no real credentials.
 */
class Stage2DownloadsCouponsTest extends TestCase
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
        $settings->set('payments.razorpay_key_id', 'rzp_test_mebstage2b');
        $settings->set('payments.razorpay_key_secret', 'test_secret_stage2b_xyz');
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
            'code' => 'STAGE2B'.strtoupper(uniqid()),
            'type' => 'percent',
            'value' => 10,
            'currency' => 'BOTH',
            'max_uses_per_user' => 5,
            'is_active' => true,
            'applies_to' => 'all',
        ], $overrides));
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

    protected function downloadUrl(Order $order, Book $book, $expiry = null): string
    {
        return URL::temporarySignedRoute('download', $expiry ?? now()->addMinutes(10), [
            'order' => $order->id,
            'book' => $book->id,
        ]);
    }

    // ------------------------------------------------------------------
    // 1. Download gates: ownership, paid status, book-in-order
    // ------------------------------------------------------------------

    public function test_download_requires_paid_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->pendingOrderFor($user, $book);

        $this->actingAs($user)->get($this->downloadUrl($order, $book))
            ->assertForbidden();
    }

    public function test_download_requires_ownership(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($owner, $book);

        $this->actingAs($intruder)->get($this->downloadUrl($order, $book))
            ->assertNotFound();
    }

    public function test_download_requires_book_in_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $other = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user)->get($this->downloadUrl($order, $other))
            ->assertNotFound();
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        // A signed URL from 1 minute ago (past its 10-minute expiry),
        // reused now: the signature check fails.
        $this->actingAs($user)->get($this->downloadUrl($order, $book, now()->subMinute()))
            ->assertForbidden();
    }

    public function test_tampered_signed_url_is_rejected(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $url = $this->downloadUrl($order, $book).'tampered';

        $this->actingAs($user)->get($url)->assertForbidden();
    }

    // ------------------------------------------------------------------
    // 2. Download limit: 5 served, 6th blocked, attempts logged
    // ------------------------------------------------------------------

    public function test_download_limit_blocks_sixth_served_download(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->get($this->downloadUrl($order, $book))->assertOk();
        }

        $this->assertSame(5, DownloadLog::usedCount($order->id, $book->id));

        // The 6th attempt is blocked — and, crucially, it does NOT count
        // against the limit (downloaded_at stays NULL), while still being
        // logged for the admin download-tracking view.
        $this->actingAs($user)->get($this->downloadUrl($order, $book))
            ->assertRedirect()
            ->assertSessionHasErrors('download');

        $this->assertSame(5, DownloadLog::usedCount($order->id, $book->id));
        $this->assertSame(6, DownloadLog::attemptsCount($order->id, $book->id));
        $this->assertNull(DownloadLog::latest('id')->first()->downloaded_at);
    }

    public function test_limit_is_per_book_per_order(): void
    {
        $user = $this->makeUser();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $order = $this->makePaidOrder($user, $bookA);
        $order->items()->create([
            'book_id' => $bookB->id, 'title' => $bookB->title,
            'unit_price' => 149, 'quantity' => 1, 'line_total' => 149, 'currency' => 'INR',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->get($this->downloadUrl($order, $bookA))->assertOk();
        }

        // Book A is exhausted, but book B still has all 5 downloads.
        $this->actingAs($user)->get($this->downloadUrl($order, $bookB))->assertOk();
        $this->assertSame(1, DownloadLog::usedCount($order->id, $bookB->id));
    }

    // ------------------------------------------------------------------
    // 3. Refund revokes download access
    // ------------------------------------------------------------------

    public function test_refunded_order_loses_download_access(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user)->get($this->downloadUrl($order, $book))->assertOk();

        app(OrderService::class)->markRefunded($order, $admin->id, 'refunded via Razorpay dashboard');

        $this->actingAs($user)->get($this->downloadUrl($order, $book))->assertForbidden();
    }

    public function test_refunded_order_disappears_from_library(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user)->get(route('library'))->assertSee($book->title);

        app(OrderService::class)->markRefunded($order, $admin->id, 'refunded via Razorpay dashboard');

        $this->actingAs($user)->get(route('library'))->assertDontSee($book->title);
    }

    // ------------------------------------------------------------------
    // 4. Coupon consume: serialization + full re-validation under lock
    // ------------------------------------------------------------------

    public function test_single_use_coupon_cannot_be_consumed_twice(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['max_uses' => 1, 'max_uses_per_user' => 1]);
        $service = app(CouponService::class);

        $order1 = $this->pendingOrderFor($user, $book);
        // pendingOrderFor without coupon — consume explicitly like two
        // simultaneous checkouts racing on the coupon row lock.
        $service->consume($order1, $coupon, $user, collect([$book]), 149.0, 'INR');
        $this->assertSame(1, $coupon->fresh()->uses_count);

        $otherUser = $this->makeUser();
        $order2 = $this->pendingOrderFor($otherUser, $book);

        $this->expectException(CouponException::class);
        $service->consume($order2, $coupon, $otherUser, collect([$book]), 149.0, 'INR');
    }

    public function test_per_user_limit_enforced_at_consume_time(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['max_uses_per_user' => 1, 'max_uses' => 100]);
        $service = app(CouponService::class);

        $order1 = $this->pendingOrderFor($user, $book);
        $service->consume($order1, $coupon, $user, collect([$book]), 149.0, 'INR');

        $order2 = $this->pendingOrderFor($user, $book);

        $this->expectException(CouponException::class);
        $service->consume($order2, $coupon, $user, collect([$book]), 149.0, 'INR');
    }

    public function test_coupon_deactivated_after_apply_fails_at_consume(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon();
        $service = app(CouponService::class);

        // Valid at apply time...
        $this->assertNull($service->validateCoupon($coupon, $user, collect([$book]), 149.0, 'INR'));

        // ...killed by an admin before the order transaction.
        $coupon->update(['is_active' => false]);

        $order = $this->pendingOrderFor($user, $book);

        $this->expectException(CouponException::class);
        $service->consume($order, $coupon->fresh(), $user, collect([$book]), 149.0, 'INR');
    }

    public function test_coupon_exhausted_after_apply_fails_at_consume(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['max_uses' => 5]);
        $service = app(CouponService::class);

        // Another customer burns the last use after this one applied it.
        $coupon->update(['uses_count' => 5]);

        $order = $this->pendingOrderFor($user, $book);

        $this->expectException(CouponException::class);
        $service->consume($order, $coupon->fresh(), $user, collect([$book]), 149.0, 'INR');
    }

    public function test_min_order_amount_is_enforced_for_both_and_single_currency_coupons(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $service = app(CouponService::class);

        // BOTH-currency coupon: the minimum is compared numerically
        // against the order subtotal (fail-safe — never grants a
        // discount below the minimum).
        $both = $this->makeCoupon(['currency' => 'BOTH', 'min_order_amount' => 1000]);
        $this->assertStringContainsString(
            'minimum order',
            $service->validateCoupon($both, $user, collect([$book]), 149.0, 'INR')
        );

        // Exact-currency coupon: the minimum is enforced.
        $inr = $this->makeCoupon(['currency' => 'INR', 'min_order_amount' => 1000]);
        $this->assertStringContainsString(
            'minimum order',
            $service->validateCoupon($inr, $user, collect([$book]), 149.0, 'INR')
        );

        // An order that meets the minimum passes validation.
        $this->assertNull($service->validateCoupon($both, $user, collect([$book]), 1200.0, 'INR'));
    }

    // ------------------------------------------------------------------
    // 5. Date windows use server time (APP_TIMEZONE), never user time
    // ------------------------------------------------------------------

    public function test_coupon_date_window_evaluated_in_server_time(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $service = app(CouponService::class);

        $expired = $this->makeCoupon(['ends_at' => now()->subMinute()]);
        $this->assertNotNull($service->validateCoupon($expired, $user, collect([$book]), 149.0, 'INR'));

        $future = $this->makeCoupon(['starts_at' => now()->addMinute()]);
        $this->assertNotNull($service->validateCoupon($future, $user, collect([$book]), 149.0, 'INR'));

        $live = $this->makeCoupon(['starts_at' => now()->subMinute(), 'ends_at' => now()->addMinute()]);
        $this->assertNull($service->validateCoupon($live, $user, collect([$book]), 149.0, 'INR'));
    }

    // ------------------------------------------------------------------
    // 6. Invoice labels the free-coupon method correctly
    // ------------------------------------------------------------------

    public function test_free_coupon_invoice_shows_coupon_method(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100]);

        Cart::addBook($book, $user, 'test-session');

        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'coupon');
        app(OrderService::class)->markPaid($order->fresh(), null, 'coupon');

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Coupon (free)');
    }
}
