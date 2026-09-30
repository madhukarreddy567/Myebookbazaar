<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Download tracking + admin visibility (refund-validity checks).
 *
 * - Every entitled download attempt is logged with IP, user agent and a
 *   downloaded_at stamp that is set ONLY when the file is actually served.
 * - The admin order view shows Download Status / IP Address / Total Attempts.
 */
class DownloadTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
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
        Storage::disk('local')->put('ebooks/track-'.uniqid().'.pdf', '%PDF-1.4 fake');
        $path = collect(Storage::disk('local')->files('ebooks'))->first();

        return Book::create(array_merge([
            'title' => 'Tracked Book '.uniqid(),
            'slug' => 'tracked-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'file_path' => $path,
            'original_filename' => 'Tracked Book Original.pdf',
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

    protected function downloadUrl(Order $order, Book $book): string
    {
        return URL::temporarySignedRoute('download', now()->addMinutes(10), [
            'order' => $order->id,
            'book' => $book->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Logging
    // ------------------------------------------------------------------

    public function test_download_logs_row_with_ip_timestamp_and_user_agent(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user, 'web')->call(
            'GET', $this->downloadUrl($order, $book), [], [], [],
            ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'DownloadTrackingTest/1.0']
        )->assertOk();

        $log = DownloadLog::where('order_id', $order->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('203.0.113.9', $log->ip_address);
        $this->assertSame('DownloadTrackingTest/1.0', $log->user_agent);
        $this->assertNotNull($log->downloaded_at);
    }

    public function test_blocked_attempts_are_logged_but_do_not_consume_the_limit(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);
        $url = $this->downloadUrl($order, $book);

        // 5 successful downloads (the default limit).
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user, 'web')->get($url)->assertOk();
        }
        $this->assertSame(5, DownloadLog::usedCount($order->id, $book->id));
        $this->assertSame(5, DownloadLog::attemptsCount($order->id, $book->id));

        // 6th and 7th attempts are blocked — but still logged as attempts.
        $this->actingAs($user, 'web')->get($url)->assertRedirect();
        $this->actingAs($user, 'web')->get($url)->assertRedirect();

        $this->assertSame(5, DownloadLog::usedCount($order->id, $book->id));
        $this->assertSame(7, DownloadLog::attemptsCount($order->id, $book->id));
        $this->assertSame(7, $order->downloadAttemptsCount());
    }

    public function test_download_serves_the_original_filename(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')->post('/reddy/books', [
            'title' => 'Filename Book '.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'pdf' => UploadedFile::fake()->createWithContent(
                'Fit at Home - Start (Edition 1).pdf',
                "%PDF-1.4\n%filename check\n"
            ),
        ])->assertRedirect();

        $book = Book::where('title', 'like', 'Filename Book%')->first();
        $this->assertSame('Fit at Home - Start (Edition 1).pdf', $book->original_filename);

        $user = $this->makeUser();
        $order = $this->makePaidOrder($user, $book);

        $response = $this->actingAs($user, 'web')->get($this->downloadUrl($order, $book));
        $response->assertOk();
        $this->assertStringContainsString(
            'Fit at Home - Start (Edition 1).pdf',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_download_filename_falls_back_to_title_slug(): void
    {
        $book = $this->makeBook(['original_filename' => null, 'title' => 'My Great Ebook']);

        $this->assertSame('my-great-ebook.pdf', $book->downloadFilename());
        $this->assertSame('Tracked Book Original.pdf', $this->makeBook()->downloadFilename());
    }

    // ------------------------------------------------------------------
    // Admin order view
    // ------------------------------------------------------------------

    public function test_admin_order_show_displays_download_status_fields(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $this->actingAs($user, 'web')->call(
            'GET', $this->downloadUrl($order, $book), [], [], [],
            ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_USER_AGENT' => 'RefundCheck/2.0']
        )->assertOk();

        // One more attempt after the file is "used up" is impossible here
        // (limit is 5), so add a blocked attempt via a second book-less hit:
        // instead, assert the first-download fields directly.
        $first = $order->fresh()->firstSuccessfulDownload();
        $this->assertNotNull($first);
        $this->assertSame('198.51.100.7', $first->ip_address);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('reddy.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Download status');
        $response->assertSee(
            'Downloaded at '.$first->downloaded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s')
        );
        $response->assertSee('198.51.100.7');
        $response->assertSee('Total attempts');
        $response->assertSee('RefundCheck/2.0');
    }

    public function test_admin_order_show_shows_not_downloaded_yet(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('reddy.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Not Downloaded Yet');
        $response->assertDontSee('Downloaded at 20');
    }

    // ------------------------------------------------------------------
    // Authorization
    // ------------------------------------------------------------------

    public function test_unauthorized_download_attempts_create_no_logs(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser(['email' => 'intruder-'.uniqid().'@example.com']);
        $book = $this->makeBook();
        $order = $this->makePaidOrder($owner, $book);

        // Intruder guessing the signed URL: 404, nothing logged.
        $this->actingAs($intruder, 'web')->get($this->downloadUrl($order, $book))
            ->assertNotFound();
        $this->assertSame(0, DownloadLog::count());

        // Guest (not logged in): redirected to login, nothing logged.
        auth('web')->logout();
        $this->get($this->downloadUrl($order, $book))->assertRedirect(route('login'));
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_customers_cannot_view_admin_order_details(): void
    {
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        // A logged-in customer is not an admin: bounced to the admin login.
        $this->actingAs($user, 'web')
            ->get(route('reddy.orders.show', $order))
            ->assertRedirect(route('reddy.login'));

        // Guest likewise.
        $this->get(route('reddy.orders.show', $order))
            ->assertRedirect(route('reddy.login'));
    }
}
