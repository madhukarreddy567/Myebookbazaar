<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Services\TotpService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Stage 3: adversarial security + data-integrity audit.
 *
 * Each test below was written exploit-first: it asserts the SECURE
 * behaviour, so it FAILS against the vulnerable code and PASSES after
 * the fix. Findings covered:
 *
 *  1. 2FA login challenge had no brute-force guard (6-digit TOTP code,
 *     unlimited guesses after a correct password).
 *  2. Deleting a book that appears in historical order items threw an
 *     unhandled 500 (FK restrict) AND deleted the PDF/cover first.
 *  3. GET /library (signed-download generation) had no throttle.
 *  4. Account deletion (anonymize) left the Google OAuth identifier
 *     (google_id) on the row.
 */
class Stage3SecurityAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    // ------------------------------------------------------------------
    // 5. Stored XSS via admin-editable link settings (social links,
    //    announcement link) rendered into href attributes
    // ------------------------------------------------------------------

    public function test_settings_reject_javascript_social_link(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->put('/reddy/settings', [
            'tab' => 'general',
            's' => ['site' => [
                'name' => 'MyEbookBazaar',
                'support_email' => 'support@example.com',
                'instagram' => 'javascript:alert(1)',
            ]],
        ])->assertSessionHasErrors('s.site.instagram');

        // The poisoned value was never saved.
        $this->assertNotSame(
            'javascript:alert(1)',
            settings('site.instagram')
        );
    }

    public function test_settings_accept_valid_https_social_link(): void
    {
        $this->actingAs($this->makeAdmin(), 'admin');

        $this->put('/reddy/settings', [
            'tab' => 'general',
            's' => ['site' => [
                'name' => 'MyEbookBazaar',
                'support_email' => 'support@example.com',
                'instagram' => 'https://instagram.com/myebookbazaar',
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://instagram.com/myebookbazaar', settings('site.instagram'));
    }

    public function test_malicious_stored_link_never_renders_as_href(): void
    {
        // A poisoned value already in the database (e.g. saved before
        // validation existed, or written directly) must not reach an
        // href on any storefront page.
        app(\App\Services\SettingsService::class)->set('site.instagram', 'javascript:alert(1)');
        app(\App\Services\SettingsService::class)->set('site.announcement_link', 'JaVaScRiPt:alert(2)');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function makeAdmin(string $role = 'super_admin'): Admin
    {
        return Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => $role,
        ]);
    }

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

    protected function enable2fa(Admin $admin): array
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $recovery = $totp->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => $recovery['hashed'],
        ])->save();

        return ['secret' => $secret, 'recovery_plain' => $recovery['plain']];
    }

    protected function makeBook(array $overrides = []): Book
    {
        $path = 'ebooks/audit-'.uniqid().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 fake');

        return Book::create(array_merge([
            'title' => 'Audit Book '.uniqid(),
            'slug' => 'audit-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'file_path' => $path,
            'original_filename' => 'Audit Book.pdf',
            'is_active' => true,
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

    /** Correct password → lands on the 2FA challenge as a guest. */
    protected function loginToChallenge(Admin $admin, string $ip): void
    {
        $this->call('POST', '/reddy/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ], [], [], ['REMOTE_ADDR' => $ip])->assertRedirect('/reddy/2fa/challenge');
        $this->assertGuest('admin');
    }

    // ------------------------------------------------------------------
    // 1. 2FA challenge brute-force guard
    // ------------------------------------------------------------------

    public function test_2fa_challenge_brute_force_is_rate_limited(): void
    {
        $admin = $this->makeAdmin();
        $this->enable2fa($admin);
        $ip = '203.0.113.50';
        $this->loginToChallenge($admin, $ip);

        // 5 wrong guesses: each rejected with a validation error.
        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
                ->assertSessionHasErrors('code');
        }
        $this->assertGuest('admin');

        // 6th guess inside the minute: locked out — not another free guess.
        $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
            ->assertStatus(429);
        $this->assertGuest('admin');
        $this->assertDatabaseHas('security_logs', ['event' => '2fa.challenge_lockout']);
    }

    public function test_2fa_challenge_attempts_do_not_leak_across_admins(): void
    {
        $adminA = $this->makeAdmin();
        $this->enable2fa($adminA);
        $adminB = $this->makeAdmin();
        $this->enable2fa($adminB);
        $ip = '203.0.113.51';

        // Exhaust admin A's guesses.
        $this->loginToChallenge($adminA, $ip);
        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
                ->assertSessionHasErrors('code');
        }
        $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
            ->assertStatus(429);

        // Admin B (different account) is NOT locked out by A's failures.
        $this->loginToChallenge($adminB, $ip);
        $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_2fa_challenge_success_clears_the_attempt_counter(): void
    {
        $admin = $this->makeAdmin();
        ['secret' => $secret] = $this->enable2fa($admin);
        $ip = '203.0.113.52';
        $this->loginToChallenge($admin, $ip);

        // 4 failed guesses, then the right code: sign-in still succeeds.
        for ($i = 0; $i < 4; $i++) {
            $this->call('POST', '/reddy/2fa/challenge', ['code' => '000000'], [], [], ['REMOTE_ADDR' => $ip])
                ->assertSessionHasErrors('code');
        }
        $code = app(TotpService::class)->currentCode($secret);
        $this->call('POST', '/reddy/2fa/challenge', ['code' => $code], [], [], ['REMOTE_ADDR' => $ip])
            ->assertRedirect('/reddy/dashboard');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // ------------------------------------------------------------------
    // 2. Book deletion vs historical order items
    // ------------------------------------------------------------------

    public function test_book_with_historical_orders_is_not_deleted_and_does_not_500(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $this->makePaidOrder($user, $book);
        $filePath = $book->file_path;

        $response = $this->actingAs($admin, 'admin')
            ->delete('/reddy/books/'.$book->id);

        // No 500: a friendly redirect explaining the constraint.
        $response->assertRedirect(route('reddy.books.index'));
        $response->assertSessionHasErrors('book');

        // The book row AND its files survive (delete-first would have
        // orphaned the row pointing at missing files).
        $this->assertDatabaseHas('books', ['id' => $book->id]);
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        // Order history is untouched.
        $this->assertDatabaseHas('order_items', ['book_id' => $book->id]);
        $this->assertSame(1, Order::count());
    }

    public function test_book_without_order_history_deletes_normally(): void
    {
        $admin = $this->makeAdmin();
        $book = $this->makeBook();
        $filePath = $book->file_path;

        $this->actingAs($admin, 'admin')
            ->delete('/reddy/books/'.$book->id)
            ->assertRedirect(route('reddy.books.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }

    // ------------------------------------------------------------------
    // 3. Library (signed-download generation) throttle
    // ------------------------------------------------------------------

    public function test_library_route_carries_throttle_middleware(): void
    {
        $middleware = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('library')->gatherMiddleware();

        $this->assertContains('throttle:60,1', $middleware);
    }

    public function test_library_page_is_throttled_per_ip(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $ip = '203.0.113.53';

        for ($i = 0; $i < 60; $i++) {
            $this->call('GET', '/library', [], [], [], ['REMOTE_ADDR' => $ip])->assertOk();
        }

        // 61st request inside the minute is rejected.
        $this->call('GET', '/library', [], [], [], ['REMOTE_ADDR' => $ip])->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // 4. Account deletion scrubs the Google identifier
    // ------------------------------------------------------------------

    public function test_account_deletion_scrubs_google_id(): void
    {
        $user = $this->makeUser(['google_id' => 'google-oauth-sub-123456789']);
        $this->actingAs($user, 'web');

        $this->delete('/account', [
            'password' => 'password123',
            'confirmation' => 'DELETE',
        ])->assertRedirect(route('home'));

        $fresh = $user->fresh();
        $this->assertNull($fresh->google_id);
        $this->assertTrue($fresh->is_blocked);
        $this->assertStringStartsWith('deleted-', $fresh->email);
    }
}
