<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Verified-purchase-only ratings.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test`. Eligibility is derived server-side from PAID
 * orders — never trusted from the client. No ratings are ever seeded:
 * every row in these tests is created by the test itself.
 */
class RatingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        app(SettingsService::class)->forgetCache();
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

    protected function makeSuperAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Super Admin',
            'email' => 'super-'.uniqid().'@example.com',
            'password' => 'password12345',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    protected function makeBook(array $overrides = []): Book
    {
        return Book::create(array_merge([
            'title' => 'Rated Book '.uniqid(),
            'slug' => 'rated-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Give the user ownership of the book through a PAID order.
     */
    protected function makePaidOrder(User $user, Book $book, string $status = Order::STATUS_PAID): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => Order::generateNumber(),
            'currency' => 'INR',
            'subtotal' => 149,
            'grand_total' => 149,
            'status' => $status,
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

    // ------------------------------------------------------------------
    // Eligibility
    // ------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $book = $this->makeBook();

        $this->post(route('books.ratings.store', $book->slug), ['stars' => 5])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Rating::count());
    }

    public function test_non_buyer_gets_403(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('books.ratings.store', $book->slug), ['stars' => 5, 'review' => 'Great!'])
            ->assertForbidden();

        $this->assertSame(0, Rating::count());
    }

    public function test_pending_order_does_not_qualify(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book, Order::STATUS_PENDING);

        $this->actingAs($user)
            ->post(route('books.ratings.store', $book->slug), ['stars' => 5])
            ->assertForbidden();

        $this->assertSame(0, Rating::count());
    }

    public function test_refunded_order_does_not_qualify(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book, Order::STATUS_REFUNDED);

        $this->actingAs($user)
            ->post(route('books.ratings.store', $book->slug), ['stars' => 5])
            ->assertForbidden();

        $this->assertSame(0, Rating::count());
    }

    // ------------------------------------------------------------------
    // Create / duplicate / edit
    // ------------------------------------------------------------------

    public function test_buyer_can_create_rating_once(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book);

        $this->actingAs($user)
            ->post(route('books.ratings.store', $book->slug), ['stars' => 5, 'review' => 'Loved it.'])
            ->assertRedirect(route('books.show', $book->slug));

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'stars' => 5,
            'review' => 'Loved it.',
            'is_visible' => true,
        ]);
    }

    public function test_duplicate_rating_is_rejected(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book);

        $url = route('books.ratings.store', $book->slug);

        $this->actingAs($user)->post($url, ['stars' => 5])->assertRedirect();
        $this->actingAs($user)->post($url, ['stars' => 1])->assertForbidden();

        $this->assertSame(1, Rating::where('user_id', $user->id)->where('book_id', $book->id)->count());
        $this->assertSame(5, (int) Rating::where('user_id', $user->id)->where('book_id', $book->id)->value('stars'));
    }

    public function test_stars_are_validated(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book);

        $url = route('books.ratings.store', $book->slug);

        $this->actingAs($user)->post($url, ['stars' => 0])->assertSessionHasErrors('stars');
        $this->actingAs($user)->post($url, ['stars' => 6])->assertSessionHasErrors('stars');
        $this->actingAs($user)->post($url, ['stars' => 'five'])->assertSessionHasErrors('stars');
        $this->actingAs($user)->post($url, [])->assertSessionHasErrors('stars');

        $this->assertSame(0, Rating::count());
    }

    public function test_buyer_can_edit_own_rating(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book);

        $rating = Rating::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'stars' => 3,
            'review' => 'Okay.',
        ]);

        $this->actingAs($user)
            ->put(route('books.ratings.update', [$book->slug, $rating->id]), [
                'stars' => 5,
                'review' => 'Even better on re-read.',
            ])
            ->assertRedirect(route('books.show', $book->slug));

        $this->assertSame(5, (int) $rating->fresh()->stars);
        $this->assertSame('Even better on re-read.', $rating->fresh()->review);
    }

    public function test_user_cannot_edit_someone_elses_rating(): void
    {
        $book = $this->makeBook();
        $author = $this->makeUser();
        $intruder = $this->makeUser();
        $this->makePaidOrder($author, $book);
        $this->makePaidOrder($intruder, $book);

        $rating = Rating::create([
            'user_id' => $author->id,
            'book_id' => $book->id,
            'stars' => 4,
        ]);

        $this->actingAs($intruder)
            ->put(route('books.ratings.update', [$book->slug, $rating->id]), ['stars' => 1])
            ->assertForbidden();

        $this->assertSame(4, (int) $rating->fresh()->stars);
    }

    public function test_rating_cannot_be_moved_to_another_book(): void
    {
        $bookA = $this->makeBook();
        $bookB = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $bookA);

        $rating = Rating::create([
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'stars' => 4,
        ]);

        // Guessing the update URL of a different book must 404, not edit.
        $this->actingAs($user)
            ->put(route('books.ratings.update', [$bookB->slug, $rating->id]), ['stars' => 1])
            ->assertNotFound();

        $this->assertSame(4, (int) $rating->fresh()->stars);
    }

    // ------------------------------------------------------------------
    // Display math
    // ------------------------------------------------------------------

    public function test_average_and_count_math_are_correct(): void
    {
        $book = $this->makeBook();

        foreach ([5, 4, 3] as $stars) {
            $buyer = $this->makeUser();
            $this->makePaidOrder($buyer, $book);
            Rating::create(['user_id' => $buyer->id, 'book_id' => $book->id, 'stars' => $stars]);
        }

        $book = Book::withRatingSummary()->find($book->id);

        $this->assertSame(3, (int) $book->ratings_count);
        $this->assertEqualsWithDelta(4.0, (float) $book->rating_average, 0.001);

        $html = $this->get(route('books.show', $book->slug))->assertOk()->getContent();
        $this->assertStringContainsString('4.0', $html);
        $this->assertStringContainsString('3 verified reviews', $html);
    }

    public function test_hidden_reviews_are_excluded_from_display(): void
    {
        $book = $this->makeBook();

        $hidden = null;
        foreach ([5, 4, 3] as $stars) {
            $buyer = $this->makeUser();
            $this->makePaidOrder($buyer, $book);
            $rating = Rating::create([
                'user_id' => $buyer->id,
                'book_id' => $book->id,
                'stars' => $stars,
                'review' => 'Review text '.$stars.' stars',
            ]);
            if ($stars === 5) {
                $hidden = $rating;
            }
        }

        $admin = $this->makeSuperAdmin();
        $this->actingAs($admin, 'admin')
            ->post(route('reddy.reviews.toggle', $hidden))
            ->assertRedirect();

        $this->assertFalse($hidden->fresh()->is_visible);

        // actingAs($admin, 'admin') switches the default guard — restore the
        // customer guard before browsing the storefront as a guest.
        $this->app['auth']->shouldUse('web');

        $book = Book::withRatingSummary()->find($book->id);
        $this->assertSame(2, (int) $book->ratings_count);
        $this->assertEqualsWithDelta(3.5, (float) $book->rating_average, 0.001);

        $html = $this->get(route('books.show', $book->slug))->assertOk()->getContent();
        $this->assertStringNotContainsString('Review text 5 stars', $html);
        $this->assertStringContainsString('Review text 4 stars', $html);
        $this->assertStringContainsString('3.5', $html);
    }

    public function test_book_with_no_reviews_shows_no_summary(): void
    {
        $book = $this->makeBook();

        $html = $this->get(route('books.show', $book->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('verified reviews', $html);
        $this->assertStringContainsString('No reviews yet', $html);
    }

    // ------------------------------------------------------------------
    // XSS
    // ------------------------------------------------------------------

    public function test_review_text_is_escaped(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser(['name' => '<img src=x onerror=alert(2)>']);
        $this->makePaidOrder($user, $book);

        Rating::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'stars' => 5,
            'review' => '<script>alert("xss")</script>Nice book',
        ]);

        $html = $this->get(route('books.show', $book->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(2)>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
    }

    // ------------------------------------------------------------------
    // Anti-spam
    // ------------------------------------------------------------------

    public function test_review_submission_is_throttled(): void
    {
        $user = $this->makeUser();
        $books = [];
        for ($i = 0; $i < 6; $i++) {
            $book = $this->makeBook();
            $this->makePaidOrder($user, $book);
            $books[] = $book;
        }

        // Five submissions go through; the sixth is throttled.
        foreach (array_slice($books, 0, 5) as $book) {
            $this->actingAs($user)
                ->post(route('books.ratings.store', $book->slug), ['stars' => 5])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('books.ratings.store', $books[5]->slug), ['stars' => 5])
            ->assertStatus(429);

        $this->assertSame(5, Rating::where('user_id', $user->id)->count());
    }

    // ------------------------------------------------------------------
    // Admin moderation + audit
    // ------------------------------------------------------------------

    public function test_admin_can_hide_and_delete_with_audit_log(): void
    {
        $book = $this->makeBook();
        $user = $this->makeUser();
        $this->makePaidOrder($user, $book);
        $rating = Rating::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'stars' => 2,
            'review' => 'Abusive content here',
        ]);

        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('reddy.reviews.index'))
            ->assertOk()
            ->assertSee('Abusive content here');

        $this->actingAs($admin, 'admin')
            ->post(route('reddy.reviews.toggle', $rating))
            ->assertRedirect();

        $this->assertFalse($rating->fresh()->is_visible);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ratings.hidden',
            'target_type' => 'rating',
            'target_id' => $rating->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('reddy.reviews.destroy', $rating))
            ->assertRedirect();

        $this->assertSame(0, Rating::where('id', $rating->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ratings.deleted',
            'target_type' => 'rating',
            'target_id' => $rating->id,
        ]);
    }

    public function test_rating_form_shown_only_to_eligible_owner(): void
    {
        $book = $this->makeBook();
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $this->makePaidOrder($owner, $book);

        // Guest (checked first, before any actingAs) does not see the form.
        $guestHtml = $this->get(route('books.show', $book->slug))->assertOk()->getContent();
        $this->assertStringNotContainsString('Rate this book', $guestHtml);

        // Owner sees the form.
        $ownerHtml = $this->actingAs($owner)->get(route('books.show', $book->slug))->assertOk()->getContent();
        $this->assertStringContainsString('Rate this book', $ownerHtml);
        $this->assertStringContainsString(route('books.ratings.store', $book->slug), $ownerHtml);

        // Non-owner does not.
        $strangerHtml = $this->actingAs($stranger)->get(route('books.show', $book->slug))->assertOk()->getContent();
        $this->assertStringNotContainsString('Rate this book', $strangerHtml);
        $this->assertStringNotContainsString(route('books.ratings.store', $book->slug), $strangerHtml);
    }
}
