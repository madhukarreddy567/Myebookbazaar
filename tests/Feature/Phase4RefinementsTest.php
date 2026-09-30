<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Category;
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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4 refinements: new combo ownership rule, coupon rate limiting,
 * category targeting, first-order-only coupons, coupon duplicate,
 * usage CSV export, sale countdown timers, banners, feature-switch
 * hiding — each verified item by item.
 */
class Phase4RefinementsTest extends TestCase
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
        $settings->set('features.checkout', '1');
        $settings->forgetCache();
    }

    protected function tearDown(): void
    {
        app(SettingsService::class)->forgetCache();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers (mirroring Phase4Test)
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

    protected function makeAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ], $overrides));
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

    protected function makeCategory(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'is_active' => true,
        ]);
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

    protected function paidOrderFor(User $user, Book $book): Order
    {
        Cart::addBook($book, $user, 'test-session');
        $order = app(OrderService::class)->createFromCart($user, '127.0.0.1', null, null, 'razorpay');
        $order->forceFill(['status' => Order::STATUS_PAID])->save();
        Cart::query()->where('user_id', $user->id)->delete();

        return $order;
    }

    // ------------------------------------------------------------------
    // (a) Coupon rate limit: 5/min per user+IP
    // ------------------------------------------------------------------

    public function test_coupon_apply_rate_limited_to_five_per_minute(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');
        $coupon = $this->makeCoupon();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->post('/checkout/coupon', ['code' => 'WRONG'.$i])
                ->assertRedirect();
        }

        // The 6th attempt within a minute is rejected with 429.
        $this->actingAs($user)
            ->post('/checkout/coupon', ['code' => $coupon->code])
            ->assertStatus(429);
    }

    public function test_coupon_rate_limit_is_per_user(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $userA, 'test-session');

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($userA)->post('/checkout/coupon', ['code' => 'WRONG'.$i]);
        }

        $this->actingAs($userA)->post('/checkout/coupon', ['code' => 'X'])->assertStatus(429);

        // A different user on the same IP is not affected.
        Cart::addBook($book, $userB, 'test-session');
        $this->actingAs($userB)->post('/checkout/coupon', ['code' => 'WRONG0'])->assertRedirect();
    }

    // ------------------------------------------------------------------
    // (b) Row locking: consume() can never exceed max_uses
    // ------------------------------------------------------------------

    public function test_consume_never_exceeds_max_uses(): void
    {
        $user = $this->makeUser();
        $coupon = $this->makeCoupon(['max_uses' => 3, 'max_uses_per_user' => 10]);
        $order = $this->paidOrderFor($user, $this->makeBook());
        $service = app(CouponService::class);

        for ($i = 0; $i < 3; $i++) {
            $service->consume($order, $coupon, $user);
        }

        $this->assertSame(3, $coupon->fresh()->uses_count);

        $this->expectException(CouponException::class);
        $service->consume($order, $coupon, $user);
    }

    // ------------------------------------------------------------------
    // (c) Category targeting
    // ------------------------------------------------------------------

    public function test_category_coupon_discounts_only_books_in_those_categories(): void
    {
        $catA = $this->makeCategory('Fitness');
        $catB = $this->makeCategory('Money');
        $bookA = $this->makeBook(['category_id' => $catA->id, 'price_inr' => 200]);
        $bookB = $this->makeBook(['category_id' => $catB->id, 'price_inr' => 300]);

        $coupon = $this->makeCoupon([
            'type' => 'percent',
            'value' => 50,
            'applies_to' => 'categories',
            'applies_category_ids' => [$catA->id],
        ]);

        $service = app(CouponService::class);
        $user = $this->makeUser();
        $books = collect([$bookA, $bookB]);

        // 50% of the eligible ₹200 book only = ₹100 discount.
        $eligible = $service->eligibleSubtotal($coupon, $user, $books, 500.0);
        $this->assertSame(200.0, $eligible);
        $this->assertSame(100.0, $service->discountFor($coupon, $eligible));

        // A cart with no books in the category is rejected.
        $error = $service->validateCoupon($coupon, $user, collect([$bookB]), 300.0, 'INR');
        $this->assertNotNull($error);
    }

    public function test_category_coupon_on_combo_needs_full_coverage(): void
    {
        $catA = $this->makeCategory('Fitness');
        $catB = $this->makeCategory('Money');
        $bookA = $this->makeBook(['category_id' => $catA->id]);
        $bookB = $this->makeBook(['category_id' => $catB->id]);
        $combo = $this->makeCombo($bookA, $bookB);

        $coupon = $this->makeCoupon([
            'applies_to' => 'categories',
            'applies_category_ids' => [$catA->id, $catB->id],
        ]);

        $service = app(CouponService::class);
        $user = $this->makeUser();
        $books = $combo->books();

        // Covers every combo book: valid.
        $this->assertNull($service->validateCoupon($coupon, $user, $books, 199.0, 'INR', $combo));

        // Covers only one combo book: rejected (all-or-nothing).
        $coupon->update(['applies_category_ids' => [$catA->id]]);
        $this->assertNotNull($service->validateCoupon($coupon, $user, $books, 199.0, 'INR', $combo));
    }

    // ------------------------------------------------------------------
    // (d) First-order-only coupons
    // ------------------------------------------------------------------

    public function test_first_order_only_coupon(): void
    {
        $coupon = $this->makeCoupon(['first_order_only' => true]);
        $service = app(CouponService::class);

        $newUser = $this->makeUser();
        $book = $this->makeBook();
        $this->assertNull($service->validateCoupon($coupon, $newUser, collect([$book]), 149.0, 'INR'));

        $returningUser = $this->makeUser();
        $this->paidOrderFor($returningUser, $book);
        $error = $service->validateCoupon($coupon, $returningUser, collect([$book]), 149.0, 'INR');
        $this->assertStringContainsString('first order', $error);
    }

    // ------------------------------------------------------------------
    // (e) Coupon duplicate
    // ------------------------------------------------------------------

    public function test_admin_can_duplicate_coupon(): void
    {
        $admin = $this->makeAdmin();
        $coupon = $this->makeCoupon(['code' => 'DIWALI20', 'uses_count' => 7, 'max_uses' => 10]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/reddy/coupons/'.$coupon->id.'/duplicate');

        $response->assertRedirect();
        $copy = Coupon::query()->where('code', 'DIWALI20-COPY')->firstOrFail();

        $this->assertNotEquals($coupon->id, $copy->id);
        $this->assertSame(0, (int) $copy->uses_count);
        $this->assertSame($coupon->value, $copy->value);
        $this->assertSame($coupon->applies_to, $copy->applies_to);

        // Audited as a creation.
        $this->assertTrue(
            AuditLog::query()->where('action', 'coupon.created')->where('target_id', $copy->id)->exists()
        );

        // A second duplicate gets a unique code.
        $this->actingAs($admin, 'admin')->post('/reddy/coupons/'.$coupon->id.'/duplicate');
        $this->assertTrue(Coupon::query()->where('code', 'DIWALI20-COPY-2')->exists());
    }

    // ------------------------------------------------------------------
    // (f) CSV export of coupon usages
    // ------------------------------------------------------------------

    public function test_coupon_usages_csv_export(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser(['name' => 'CSV Buyer', 'email' => 'csv-buyer-'.uniqid().'@example.com']);
        $coupon = $this->makeCoupon(['code' => 'CSVTEST']);
        $order = $this->paidOrderFor($user, $this->makeBook());
        $order->forceFill(['discount_total' => 25.50])->save();

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/reddy/coupons/'.$coupon->id.'/usages/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $body = $response->streamedContent();
        foreach (['User', 'Email', '"Order ID"', '"Discount amount"', 'Currency', '"Used at (IST)"'] as $header) {
            $this->assertStringContainsString($header, $body);
        }
        $this->assertStringContainsString('CSV Buyer', $body);
        $this->assertStringContainsString($user->email, $body);
        $this->assertStringContainsString($order->order_number, $body);
        $this->assertStringContainsString('25.50', $body);

        // Audited.
        $this->assertTrue(
            AuditLog::query()->where('action', 'coupon.usages_exported')->where('target_id', $coupon->id)->exists()
        );
    }

    // ------------------------------------------------------------------
    // (g) Sale countdown timer markup
    // ------------------------------------------------------------------

    public function test_sale_countdown_renders_with_end_timestamp(): void
    {
        $endsAt = now()->addDays(2)->addHours(4);
        $book = $this->makeBook([
            'sale_price_inr' => 99,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => $endsAt,
        ]);

        $response = $this->get('/books/'.$book->slug);
        $response->assertOk();
        $response->assertSee('data-countdown-end="'.$book->fresh()->sale_ends_at->toIso8601String().'"', false);
        $response->assertSee('Sale ends in', false);
    }

    public function test_combo_sale_countdown_renders(): void
    {
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $combo = $this->makeCombo($bookA, $bookB, [
            'sale_price_inr' => 149,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);

        $response = $this->get('/combos/'.$combo->slug);
        $response->assertOk();
        $response->assertSee('data-countdown-end="'.$combo->fresh()->sale_ends_at->toIso8601String().'"', false);
    }

    // ------------------------------------------------------------------
    // (h) Offer banners render on the homepage
    // ------------------------------------------------------------------

    public function test_homepage_stays_simple_without_banner_clutter(): void
    {
        Banner::create([
            'title' => 'Diwali Sale',
            'image' => 'banners/diwali.jpg',
            'link_url' => '/offers',
            'is_active' => true,
        ]);

        // The simplified homepage keeps its focus: no banner carousel clutter.
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('Diwali Sale');
    }

    // ------------------------------------------------------------------
    // (i) Feature switch OFF hides its feature
    // ------------------------------------------------------------------

    protected function switchOff(string $key): void
    {
        app(SettingsService::class)->set($key, '0');
        app(SettingsService::class)->forgetCache();
    }

    public function test_combos_switch_off_hides_everything(): void
    {
        $this->switchOff('features.combos');

        $this->get('/combos')->assertStatus(403);
        $this->get('/combos/some-slug')->assertStatus(403);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('Combos');
        $response->assertDontSee('View combo');
    }

    public function test_sales_offers_switch_off_hides_everything(): void
    {
        $this->switchOff('features.sales_offers');

        $this->get('/offers')->assertStatus(403);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('On sale now');
        $response->assertDontSee('All offers');
    }

    public function test_coupons_switch_off_hides_field_and_blocks_endpoint(): void
    {
        $this->switchOff('features.coupons');

        $user = $this->makeUser();
        $book = $this->makeBook();
        Cart::addBook($book, $user, 'test-session');

        $this->actingAs($user)->post('/checkout/coupon', ['code' => 'ANY'])->assertStatus(403);

        $response = $this->actingAs($user)->get('/checkout');
        $response->assertOk();
        $response->assertDontSee('Coupon code');
    }

    public function test_checkout_switch_off_blocks_checkout(): void
    {
        $this->switchOff('features.checkout');

        $user = $this->makeUser();
        $this->actingAs($user)->get('/checkout')->assertStatus(403);
    }
}
