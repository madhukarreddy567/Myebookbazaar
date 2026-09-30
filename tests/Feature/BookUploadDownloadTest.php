<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
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
 * Bug-fix coverage for the ebook upload + download chain:
 *
 * 1. Admin book upload: a fake PDF (and cover image) uploaded through the
 *    real admin create/update flow must land on the correct disks with the
 *    path the download side reads back (no "storage error").
 * 2. Upload validation: PDFs are capped at 200 MB (BookRequest 'max:204800'),
 *    non-PDF files are rejected, covers stay image-only.
 * 3. Full chain: admin upload -> paid order -> signed library-style download
 *    link -> 200 response whose bytes are exactly the uploaded PDF.
 * 4. Missing file on disk (e.g. wiped ephemeral container storage) is
 *    handled gracefully with the "not available yet" error, not a 500.
 */
class BookUploadDownloadTest extends TestCase
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

        // email_verified_at is not mass-assignable — set it directly.
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    protected function bookPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Upload Test Book '.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
        ], $overrides);
    }

    protected function fakePdf(string $content = "%PDF-1.4\n%fake ebook bytes\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('ebook.pdf', $content);
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

    // ------------------------------------------------------------------
    // Admin upload flow
    // ------------------------------------------------------------------

    public function test_admin_can_upload_pdf_and_cover_on_book_create(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => $this->fakePdf(),
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ]));

        $response->assertRedirect(route('reddy.books.index'));

        $book = Book::where('title', 'like', 'Upload Test Book%')->first();
        $this->assertNotNull($book);
        $this->assertTrue($book->hasFile());
        $this->assertStringStartsWith('ebooks/', $book->file_path);

        // The stored files are exactly where the download side looks.
        Storage::disk('local')->assertExists($book->file_path);
        $this->assertNotNull($book->cover_image);
        Storage::disk('public')->assertExists($book->cover_image);

        // The change is audited like every other admin book change.
        $this->assertTrue(
            AuditLog::where('action', 'book.created')->where('target_id', $book->id)->exists()
        );
    }

    public function test_admin_book_update_replaces_pdf_and_removes_old_file(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => $this->fakePdf("%PDF-1.4\n%version one\n"),
        ]))->assertRedirect();

        $book = Book::where('title', 'like', 'Upload Test Book%')->first();
        $oldPath = $book->file_path;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($admin, 'admin')->put('/reddy/books/'.$book->id, $this->bookPayload([
            'title' => $book->title,
            'pdf' => $this->fakePdf("%PDF-1.4\n%version two\n"),
        ]))->assertRedirect();

        $book->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($book->file_path);
        $this->assertSame("%PDF-1.4\n%version two\n", Storage::disk('local')->get($book->file_path));
    }

    public function test_pdf_upload_rejects_non_pdf_and_files_over_200mb(): void
    {
        $admin = $this->makeAdmin();

        // Not a PDF.
        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => UploadedFile::fake()->createWithContent('ebook.txt', 'not a pdf'),
        ]))->assertSessionHasErrors('pdf');

        $this->assertFalse(Book::where('title', 'like', 'Upload Test Book%')->exists());

        // Just over the 200 MB cap (204800 KB). The fake file reports its
        // size without writing 200 MB to disk.
        $oversized = UploadedFile::fake()->create('big.pdf', 204801, 'application/pdf');
        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => $oversized,
        ]))
            ->assertSessionHasErrors('pdf');

        // A file exactly at the cap passes validation (other fields valid).
        $atCap = UploadedFile::fake()->create('ok.pdf', 204800, 'application/pdf');
        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => $atCap,
        ]))->assertSessionHasNoErrors();

        $book = Book::where('title', 'like', 'Upload Test Book%')->first();
        $this->assertNotNull($book);
        $this->assertTrue($book->hasFile());
    }

    public function test_cover_upload_rejects_non_images(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'cover' => UploadedFile::fake()->createWithContent('cover.pdf', "%PDF-1.4\n"),
        ]))->assertSessionHasErrors('cover');
    }

    // ------------------------------------------------------------------
    // Full chain: admin upload -> paid order -> signed download
    // ------------------------------------------------------------------

    public function test_full_chain_admin_upload_to_customer_download(): void
    {
        $admin = $this->makeAdmin();
        $pdfBytes = "%PDF-1.4\n%the real uploaded ebook content\n";

        $this->actingAs($admin, 'admin')->post('/reddy/books', $this->bookPayload([
            'pdf' => $this->fakePdf($pdfBytes),
        ]))->assertRedirect();

        $book = Book::where('title', 'like', 'Upload Test Book%')->first();
        $this->assertNotNull($book);
        $this->assertTrue($book->hasFile());

        $user = $this->makeUser();
        $order = $this->makePaidOrder($user, $book);

        // Signed link built exactly the way LibraryController builds it
        // (route 'download', 10-minute expiry).
        $url = URL::temporarySignedRoute(
            'download',
            now()->addMinutes(10),
            ['order' => $order->id, 'book' => $book->id],
        );

        $response = $this->actingAs($user, 'web')->get($url);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        // The bytes served are byte-for-byte the file the admin uploaded.
        ob_start();
        $response->baseResponse->sendContent();
        $servedBytes = ob_get_clean();
        $this->assertSame($pdfBytes, $servedBytes);
        $this->assertSame($pdfBytes, Storage::disk('local')->get($book->file_path));

        $this->assertSame(1, DownloadLog::usedCount($order->id, $book->id));
    }

    public function test_download_fails_gracefully_when_file_missing_on_disk(): void
    {
        // file_path points at a file that is NOT on disk — the production
        // signature of wiped ephemeral container storage.
        $user = $this->makeUser();
        $book = Book::create([
            'title' => 'Ghost Book '.uniqid(),
            'slug' => 'ghost-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
            'file_path' => 'ebooks/does-not-exist.pdf',
            'is_active' => true,
        ]);
        $order = $this->makePaidOrder($user, $book);

        $url = URL::temporarySignedRoute(
            'download',
            now()->addMinutes(10),
            ['order' => $order->id, 'book' => $book->id],
        );

        $this->actingAs($user, 'web')->get($url)
            ->assertRedirect()
            ->assertSessionHasErrors('download');

        $this->assertSame(0, DownloadLog::usedCount($order->id, $book->id));
    }
}
