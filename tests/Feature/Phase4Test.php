<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4: coupons, combos, offers, announcement bar.
 */
class Phase4Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
        Storage::fake('local');

        $settings = app(SettingsService::class);
        $settings->set('features.coupons', '1');
        $settings->set('features.combos', '1');
        $settings->set('features.sales_offers', '1');
        $settings->set('features.announcement_bar', '1');
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
        $path = collect(Storage::disk('local')->files('ebooks'))->last();

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
            'code' => 'TEST'.strtoupper(uniqid()),
            'type' => 'percent',
            'value' => 10,
            'currency' => 'BOTH',
            'max_uses_per_user' => 5,
            'is_active' => true,
            'applies_to' => 'all',
        ], $overrides));
    }

    protected function makeCombo(Book $a, Book $b, array $overrides = []): Combo
    {
        return Combo::create(array_merge([
            'title' => 'Test Combo '.uniqid(),
            'slug' => 'test-combo-'.uniqid(),
            'book_ids' => [$a->id, $b->id],
            'combo_price_inr' => 199,
            'combo_price_usd' => 7.99,
            'is_active' => true,
        ], $overrides));
    }

    protected function couponService(): CouponService
    {
        return app(CouponService::class);
    }

    protected function quoteFor(User $user, string $code, array $bookOverrides = []): array
    {
        $book = $this->makeBook($bookOverrides);

        return $this->couponService()->quote(
            $user,
            collect([$book]),
            149.0,
            'INR',
            $code
        );
    }

    // ------------------------------------------------------------------
    // Coupon validation
    // ------------------------------------------------------------------

    public function test_invalid_coupon_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $quote = $this->quoteFor($user, 'NOPE-NOT-REAL');

        $this->assertNotNull($quote['coupon_error']);
        $this->assertNull($quote['coupon']);
        $this->assertSame(0.0, $quote['discount']);
    }

    public function test_inactive_coupon_is_rejected(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['is_active' => false]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertNotNull($quote['coupon_error']);
    }

    public function test_not_yet_active_coupon_is_rejected(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['starts_at' => now()->addDay()]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('not active yet', $quote['coupon_error']);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['ends_at' => now()->subDay()]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('expired', $quote['coupon_error']);
    }

    public function test_coupon_with_reached_total_limit_is_rejected(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['max_uses' => 2, 'uses_count' => 2]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('usage limit', $quote['coupon_error']);
    }

    public function test_coupon_with_reached_per_user_limit_is_rejected(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['max_uses_per_user' => 1]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.uniqid(),
            'currency' => 'INR',
            'subtotal' => 149,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 149,
            'status' => Order::STATUS_PAID,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('already used', $quote['coupon_error']);
    }

    public function test_coupon_minimum_order_is_enforced(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['min_order_amount' => 500]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('minimum order', $quote['coupon_error']);
    }

    public function test_coupon_code_matching_is_case_insensitive(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['code' => 'SAVE20ABC']);

        $quote = $this->quoteFor($user, 'save20abc');

        $this->assertNull($quote['coupon_error']);
        $this->assertSame('SAVE20ABC', $quote['coupon']->code);
    }

    // ------------------------------------------------------------------
    // Discount math
    // ------------------------------------------------------------------

    public function test_percent_discount_is_calculated(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 10]);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertEquals(14.9, $quote['discount']);
        $this->assertEquals(134.1, $quote['grand_total']);
    }

    public function test_flat_discount_is_capped_at_subtotal(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['type' => 'flat', 'value' => 500, 'currency' => 'INR']);

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertEquals(149.0, $quote['discount']);
        $this->assertEquals(0.0, $quote['grand_total']);
        $this->assertTrue($quote['free']);
    }

    public function test_specific_book_coupon_discounts_only_eligible_books(): void
    {
        $user = $this->makeUser();
        $eligible = $this->makeBook(['price_inr' => 149]);
        $other = $this->makeBook(['price_inr' => 249]);
        $coupon = $this->makeCoupon([
            'type' => 'percent',
            'value' => 50,
            'applies_to' => 'books',
            'applies_book_ids' => [$eligible->id],
        ]);

        $quote = $this->couponService()->quote(
            $user,
            collect([$eligible, $other]),
            398.0,
            'INR',
            $coupon->code
        );

        // 50% of the eligible book only (149), not the full 398.
        $this->assertNull($quote['coupon_error']);
        $this->assertEquals(74.5, $quote['discount']);
        $this->assertEquals(323.5, $quote['grand_total']);
    }

    public function test_specific_book_coupon_rejected_when_no_eligible_books(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $coupon = $this->makeCoupon([
            'applies_to' => 'books',
            'applies_book_ids' => [999999],
        ]);

        $quote = $this->couponService()->quote(
            $user, collect([$book]), 149.0, 'INR', $coupon->code
        );

        $this->assertStringContainsString('does not apply', $quote['coupon_error']);
    }

    public function test_inr_only_coupon_rejected_for_usd_order(): void
    {
        $user = $this->makeUser(['pricing_region' => 'INTL', 'country_code' => 'US']);
        $coupon = $this->makeCoupon(['currency' => 'INR']);

        $quote = $this->couponService()->quote(
            $user, collect([$this->makeBook()]), 4.99, 'USD', $coupon->code
        );

        $this->assertStringContainsString('rupee', $quote['coupon_error']);
    }

    public function test_both_currency_coupon_works_for_usd(): void
    {
        $user = $this->makeUser(['pricing_region' => 'INTL', 'country_code' => 'US']);
        $coupon = $this->makeCoupon(['currency' => 'BOTH', 'type' => 'percent', 'value' => 10]);

        $quote = $this->couponService()->quote(
            $user, collect([$this->makeBook()]), 4.99, 'USD', $coupon->code
        );

        $this->assertNull($quote['coupon_error']);
        $this->assertEquals(0.5, $quote['discount']);
    }

    public function test_coupons_switch_off_rejects_everything(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon();

        app(SettingsService::class)->set('features.coupons', '0');
        app(SettingsService::class)->forgetCache();

        $quote = $this->quoteFor($user, $coupon->code);

        $this->assertStringContainsString('disabled', $quote['coupon_error']);
    }

    // ------------------------------------------------------------------
    // 100% coupon order flow (no Razorpay)
    // ------------------------------------------------------------------

    public function test_hundred_percent_coupon_creates_free_paid_order(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100]);

        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, $coupon, 'coupon');

        $this->assertEquals(0.0, (float) $order->grand_total);
        $this->assertEquals(149.0, (float) $order->discount_total);
        $this->assertEquals('coupon', $order->payment_method);
        $this->assertEquals($coupon->code, $order->coupon_code);
        $this->assertEquals(1, $order->items()->count());

        // Usage recorded.
        $this->assertEquals(1, CouponUsage::where('coupon_id', $coupon->id)->count());
        $this->assertEquals(1, $coupon->fresh()->uses_count);
    }

    public function test_free_order_marked_paid_with_zero_payment(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 100]);

        // Apply the coupon first (it lives in the session).
        $this->actingAs($user)->post('/checkout/coupon', ['code' => $coupon->code])->assertRedirect();

        $response = $this->actingAs($user)->postJson('/checkout', [
            'no_refund_accepted' => 'yes',
        ], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Without gateway keys configured the free path still works.
        $response->assertOk();
        $response->assertJsonStructure(['redirect']);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('coupon', $order->payment_method);

        // markPaid path via thankyou page shows paid state.
        $paid = app(OrderService::class)->markPaid(
            $order->fresh(),
            $order->payments()->create([
                'amount' => 0, 'currency' => 'INR',
                'status' => \App\Models\Payment::STATUS_CAPTURED,
            ]),
            'coupon'
        );
        $this->assertEquals(Order::STATUS_PAID, $paid->status);
    }

    public function test_limited_coupon_cannot_be_double_used(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['max_uses' => 1, 'max_uses_per_user' => 5]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.uniqid(),
            'currency' => 'INR',
            'subtotal' => 149,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 149,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
        ]);

        $this->couponService()->consume($order, $coupon, $user);

        $order2 = Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.uniqid(),
            'currency' => 'INR',
            'subtotal' => 149,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 149,
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'razorpay',
            'no_refund_accepted' => true,
        ]);

        $this->expectException(CouponException::class);
        $this->couponService()->consume($order2, $coupon, $user);
    }

    // ------------------------------------------------------------------
    // Combos
    // ------------------------------------------------------------------

    public function test_combo_purchase_creates_one_item_per_book(): void
    {
        $user = $this->makeUser();
        $combo = $this->makeCombo($this->makeBook(), $this->makeBook());

        $order = app(OrderService::class)->createFromCombo($user, $combo, '127.0.0.1', null, null, 'razorpay');

        $items = $order->items()->orderBy('id')->get();
        $this->assertEquals(2, $items->count());
        $this->assertEquals(199.0, (float) $order->grand_total);
        // Lines sum exactly to the combo price.
        $this->assertEquals(199.0, round($items->sum('line_total'), 2));
        $this->assertEquals($combo->book_ids, $items->pluck('book_id')->all());
    }

    public function test_combo_sale_price_is_used_when_live(): void
    {
        $user = $this->makeUser();
        $combo = $this->makeCombo($this->makeBook(), $this->makeBook(), [
            'sale_price_inr' => 149,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);

        $order = app(OrderService::class)->createFromCombo($user, $combo, '127.0.0.1', null, null, 'razorpay');

        $this->assertEquals(149.0, (float) $order->grand_total);
    }

    public function test_owning_some_combo_books_warns_but_allows_purchase(): void
    {
        $user = $this->makeUser();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $combo = $this->makeCombo($bookA, $bookB);

        // Buy book A on its own first.
        Cart::addBook($bookA, $user, 'test-session');
        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, null, 'razorpay');
        $order->forceFill(['status' => Order::STATUS_PAID])->save();

        $this->assertTrue($combo->userOwnsAnyBook($user));
        $this->assertFalse($combo->userOwnsAllBooks($user));

        // Owning SOME books: buy is allowed, with the "already own" warning.
        $response = $this->actingAs($user)->post('/combos/'.$combo->slug.'/buy');
        $response->assertRedirect(route('checkout'));
        $response->assertSessionHas('combo_warning');
        $this->assertStringContainsString($bookA->title, session('combo_warning'));

        // The combo detail page shows the warning and a "buy anyway" button.
        $response = $this->actingAs($user)->get('/combos/'.$combo->slug);
        $response->assertOk();
        $response->assertSee('You already own');
        $response->assertSee($bookA->title);
        $response->assertSee('Buy combo anyway');
    }

    public function test_owning_all_combo_books_blocks_repurchase(): void
    {
        $user = $this->makeUser();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $combo = $this->makeCombo($bookA, $bookB);

        // Buy both books on their own first.
        foreach ([$bookA, $bookB] as $book) {
            Cart::addBook($book, $user, 'test-session');
            $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, null, 'razorpay');
            $order->forceFill(['status' => Order::STATUS_PAID])->save();
            Cart::query()->where('user_id', $user->id)->delete();
        }

        $this->assertTrue($combo->userOwnsAllBooks($user));

        // Owning ALL books: buy is blocked and names the library.
        $response = $this->actingAs($user)->post('/combos/'.$combo->slug.'/buy');
        $response->assertRedirect(route('library'));

        // Checkout drops a fully-owned combo from the session.
        session(['checkout_combo_id' => $combo->id]);
        $response = $this->actingAs($user)->get('/checkout');
        $response->assertOk();
        $this->assertNull(session('checkout_combo_id'));
    }

    public function test_combo_pages_require_feature_switch(): void
    {
        $combo = $this->makeCombo($this->makeBook(), $this->makeBook());

        $this->get('/combos')->assertOk();
        $this->get('/combos/'.$combo->slug)->assertOk();

        app(SettingsService::class)->set('features.combos', '0');
        app(SettingsService::class)->forgetCache();

        $this->get('/combos')->assertForbidden();
        $this->get('/combos/'.$combo->slug)->assertForbidden();
    }

    public function test_combo_buy_requires_login(): void
    {
        $combo = $this->makeCombo($this->makeBook(), $this->makeBook());

        $this->post('/combos/'.$combo->slug.'/buy')->assertRedirect(route('login'));
    }

    public function test_specific_book_coupon_on_combo_needs_full_coverage(): void
    {
        $user = $this->makeUser();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $combo = $this->makeCombo($bookA, $bookB);

        $coupon = $this->makeCoupon([
            'applies_to' => 'books',
            'applies_book_ids' => [$bookA->id], // only one of two
        ]);

        $quote = $this->couponService()->quote(
            $user, $combo->books(), 199.0, 'INR', $coupon->code, $combo
        );

        $this->assertStringContainsString('every book in this combo', $quote['coupon_error']);

        // Covering both books is fine.
        $coupon->update(['applies_book_ids' => [$bookA->id, $bookB->id]]);
        $quote = $this->couponService()->quote(
            $user, $combo->books(), 199.0, 'INR', $coupon->code, $combo
        );

        $this->assertNull($quote['coupon_error']);
    }

    // ------------------------------------------------------------------
    // Offers + announcement
    // ------------------------------------------------------------------

    public function test_offers_page_lists_live_sales_only(): void
    {
        $live = $this->makeBook([
            'sale_price_inr' => 99,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);
        $ended = $this->makeBook([
            'sale_price_inr' => 99,
            'sale_starts_at' => now()->subDays(5),
            'sale_ends_at' => now()->subDay(),
        ]);

        $response = $this->get('/offers');
        $response->assertOk();
        $response->assertSee($live->title);
        $response->assertDontSee($ended->title);
    }

    public function test_offers_page_gated_by_feature_switch(): void
    {
        $this->get('/offers')->assertOk();

        app(SettingsService::class)->set('features.sales_offers', '0');
        app(SettingsService::class)->forgetCache();

        $this->get('/offers')->assertForbidden();
    }

    public function test_announcement_bar_shows_text_and_optional_link(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('site.announcement_text', 'Diwali sale — 20% off');
        $settings->set('site.announcement_link', 'https://example.com/offers');
        $settings->forgetCache();

        $response = $this->get('/');
        $response->assertSee('Diwali sale — 20% off');
        $response->assertSee('https://example.com/offers', false);
    }

    // ------------------------------------------------------------------
    // Admin CRUD + auditing
    // ------------------------------------------------------------------

    public function test_admin_can_manage_coupons_with_audit(): void
    {
        $admin = $this->makeAdmin();

        // Create.
        $response = $this->actingAs($admin, 'admin')->post('/reddy/coupons', [
            'code' => 'DIWALI20',
            'type' => 'percent',
            'value' => 20,
            'currency' => 'BOTH',
            'max_uses_per_user' => 1,
            'is_active' => '1',
            'applies_to' => 'all',
        ]);
        $response->assertRedirect();

        $coupon = Coupon::where('code', 'DIWALI20')->first();
        $this->assertNotNull($coupon);
        $this->assertTrue(
            AuditLog::where('action', 'coupon.created')->where('target_id', $coupon->id)->exists()
        );

        // Update.
        $response = $this->actingAs($admin, 'admin')->put('/reddy/coupons/'.$coupon->id, [
            'code' => 'DIWALI20',
            'type' => 'percent',
            'value' => 25,
            'currency' => 'BOTH',
            'max_uses_per_user' => 1,
            'is_active' => '1',
            'applies_to' => 'all',
        ]);
        $response->assertRedirect();
        $this->assertEquals(25.0, (float) $coupon->fresh()->value);
        $this->assertTrue(
            AuditLog::where('action', 'coupon.updated')->where('target_id', $coupon->id)->exists()
        );

        // Usage stats page.
        $this->actingAs($admin, 'admin')->get('/reddy/coupons/'.$coupon->id)->assertOk();

        // Delete.
        $response = $this->actingAs($admin, 'admin')->delete('/reddy/coupons/'.$coupon->id);
        $response->assertRedirect();
        $this->assertNull(Coupon::find($coupon->id));
        $this->assertTrue(
            AuditLog::where('action', 'coupon.deleted')->exists()
        );
    }

    public function test_admin_coupon_validation_rejects_bad_input(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->post('/reddy/coupons', [
            'code' => 'bad code!!',
            'type' => 'percent',
            'value' => 150, // over 100
            'currency' => 'BOTH',
            'max_uses_per_user' => 1,
            'applies_to' => 'all',
        ]);

        $response->assertSessionHasErrors(['code', 'value']);
    }

    public function test_admin_can_manage_combos_with_audit(): void
    {
        $admin = $this->makeAdmin();
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();

        $response = $this->actingAs($admin, 'admin')->post('/reddy/combos', [
            'title' => 'Fitness Bundle',
            'slug' => 'fitness-bundle',
            'book_ids' => [$bookA->id, $bookB->id],
            'combo_price_inr' => 199,
            'combo_price_usd' => 7.99,
            'is_active' => '1',
        ]);
        $response->assertRedirect();

        $combo = Combo::where('slug', 'fitness-bundle')->first();
        $this->assertNotNull($combo);
        $this->assertEquals([$bookA->id, $bookB->id], $combo->book_ids);
        $this->assertTrue(
            AuditLog::where('action', 'combo.created')->where('target_id', $combo->id)->exists()
        );

        // Needs at least two books.
        $response = $this->actingAs($admin, 'admin')->post('/reddy/combos', [
            'title' => 'Single',
            'slug' => 'single',
            'book_ids' => [$bookA->id],
            'combo_price_inr' => 199,
            'combo_price_usd' => 7.99,
        ]);
        $response->assertSessionHasErrors(['book_ids']);

        // Update + delete audited.
        $this->actingAs($admin, 'admin')->put('/reddy/combos/'.$combo->id, [
            'title' => 'Fitness Bundle',
            'slug' => 'fitness-bundle',
            'book_ids' => [$bookA->id, $bookB->id],
            'combo_price_inr' => 179,
            'combo_price_usd' => 7.99,
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertTrue(
            AuditLog::where('action', 'combo.updated')->where('target_id', $combo->id)->exists()
        );

        $this->actingAs($admin, 'admin')->delete('/reddy/combos/'.$combo->id)->assertRedirect();
        $this->assertTrue(
            AuditLog::where('action', 'combo.deleted')->exists()
        );
    }

    public function test_admin_offers_overview_renders(): void
    {
        $admin = $this->makeAdmin();
        $this->makeBook([
            'sale_price_inr' => 99,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin, 'admin')->get('/reddy/offers')->assertOk();
    }

    public function test_admin_sidebar_links_exist(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->get('/reddy/dashboard');
        $response->assertOk();
        $response->assertSee('/reddy/coupons', false);
        $response->assertSee('/reddy/combos', false);
        $response->assertSee('/reddy/offers', false);
    }

    // ------------------------------------------------------------------
    // Checkout coupon HTTP flow
    // ------------------------------------------------------------------

    public function test_coupon_apply_and_remove_via_http(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 10]);

        $this->actingAs($user)
            ->post('/checkout/coupon', ['code' => $coupon->code])
            ->assertRedirect();

        $response = $this->actingAs($user)->get('/checkout');
        $response->assertOk();
        $response->assertSee($coupon->code);

        $this->actingAs($user)->delete('/checkout/coupon')->assertRedirect();
        $response = $this->actingAs($user)->get('/checkout');
        $response->assertDontSee($coupon->code);
    }

    public function test_applying_invalid_coupon_shows_error(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)
            ->post('/checkout/coupon', ['code' => 'NOPE'])
            ->assertSessionHasErrors(['coupon']);
    }

    public function test_cart_page_shows_coupon_form_for_logged_in_users(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $coupon = $this->makeCoupon(['type' => 'percent', 'value' => 10]);

        // Before applying: the form is visible.
        $response = $this->actingAs($user)->get('/cart');
        $response->assertOk();
        $response->assertSee('Coupon code');

        // Apply from the cart page; it redirects back to the cart.
        $this->actingAs($user)
            ->post('/checkout/coupon', ['code' => $coupon->code])
            ->assertRedirect();

        $response = $this->actingAs($user)->get('/cart');
        $response->assertSee($coupon->code);

        // Guests never see the coupon form.
        $response = $this->get('/cart');
        $response->assertDontSee('Coupon code');
    }
}
