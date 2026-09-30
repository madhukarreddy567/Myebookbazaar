<?php

namespace Tests\Feature;

use App\Mail\TicketCreatedMail;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Customer + admin support tickets.
 *
 * Uses DatabaseTransactions against the separate MySQL test database
 * `myebookbazaar_test`. Every row in these tests is created by the
 * test itself; the tearDown assertion in the throttled tests aside,
 * no rows are left behind.
 */
class SupportTicketTest extends TestCase
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
            'name' => 'Ticket User',
            'email' => 'ticket-user-'.uniqid().'@example.com',
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
            'title' => 'Ticket Book '.uniqid(),
            'slug' => 'ticket-book-'.uniqid(),
            'price_inr' => 149,
            'price_usd' => 4.99,
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

    protected function ticketPayload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Download not working',
            'category' => 'download',
            'message' => 'I paid but the download button does nothing.',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Customer: create + reply
    // ------------------------------------------------------------------

    public function test_customer_can_create_ticket_and_reply(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('support.store'), $this->ticketPayload());
        $ticket = Ticket::where('user_id', $user->id)->first();

        $this->assertNotNull($ticket);
        $response->assertRedirect(route('support.show', $ticket));

        $this->assertSame('Download not working', $ticket->subject);
        $this->assertSame('download', $ticket->category);
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertNull($ticket->order_id);
        $this->assertSame(1, $ticket->replies()->count());

        $firstReply = $ticket->replies()->first();
        $this->assertFalse((bool) $firstReply->is_admin);
        $this->assertSame($user->id, $firstReply->user_id);

        // Thread view shows subject and the first message.
        $this->actingAs($user)->get(route('support.show', $ticket))
            ->assertOk()
            ->assertSee('Download not working')
            ->assertSee('I paid but the download button does nothing.');

        // Customer reply moves the ticket to "awaiting support".
        $this->actingAs($user)->post(route('support.reply', $ticket), ['body' => 'Any update?'])
            ->assertRedirect(route('support.show', $ticket));

        $this->assertSame(2, $ticket->fresh()->replies()->count());
        $this->assertSame(Ticket::STATUS_AWAITING_SUPPORT, $ticket->fresh()->status);
    }

    public function test_customer_can_link_ticket_to_their_own_order_only(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $order = $this->makePaidOrder($user, $this->makeBook());
        $otherOrder = $this->makePaidOrder($other, $this->makeBook());

        // Own order: accepted.
        $this->actingAs($user)
            ->post(route('support.store'), $this->ticketPayload(['order_id' => $order->id]))
            ->assertRedirect();
        $this->assertSame($order->id, Ticket::where('user_id', $user->id)->first()->order_id);

        // Someone else's order: rejected by validation.
        $this->actingAs($user)
            ->post(route('support.store'), $this->ticketPayload(['order_id' => $otherOrder->id]))
            ->assertSessionHasErrors('order_id');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('support.index'))->assertRedirect(route('login'));
        $this->post(route('support.store'), $this->ticketPayload())->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Customer isolation (403)
    // ------------------------------------------------------------------

    public function test_customer_cannot_view_or_reply_to_another_customers_ticket(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();

        $this->actingAs($owner)->post(route('support.store'), $this->ticketPayload());
        $ticket = Ticket::where('user_id', $owner->id)->first();

        // The intruder's own list does not contain it either.
        $this->actingAs($intruder)->get(route('support.index'))
            ->assertOk()
            ->assertDontSee('Download not working');

        $this->actingAs($intruder)->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($intruder)->post(route('support.reply', $ticket), ['body' => 'hi'])->assertForbidden();

        $this->assertSame(1, $ticket->fresh()->replies()->count());
    }

    // ------------------------------------------------------------------
    // Admin: inbox, reply, close, reopen (+ audit log)
    // ------------------------------------------------------------------

    public function test_admin_sees_all_replies_closes_and_reopens(): void
    {
        $admin = $this->makeSuperAdmin();
        $alice = $this->makeUser();
        $bob = $this->makeUser();

        $this->actingAs($alice)->post(route('support.store'), $this->ticketPayload(['subject' => 'Alice problem']));
        $this->actingAs($bob)->post(route('support.store'), $this->ticketPayload(['subject' => 'Bob problem']));
        $aliceTicket = Ticket::where('user_id', $alice->id)->first();

        // Inbox lists every customer's ticket.
        $this->actingAs($admin, 'admin')->get(route('reddy.tickets.index'))
            ->assertOk()
            ->assertSee('Alice problem')
            ->assertSee('Bob problem');

        // Thread view works.
        $this->actingAs($admin, 'admin')->get(route('reddy.tickets.show', $aliceTicket))
            ->assertOk()
            ->assertSee('Alice problem');

        // Admin reply: marked as admin, status moves to awaiting_customer.
        $this->actingAs($admin, 'admin')
            ->post(route('reddy.tickets.reply', $aliceTicket), ['body' => 'We are looking into it.'])
            ->assertRedirect();

        $aliceTicket->refresh();
        // replies() carries ->oldest(); reorder() clears it so latest('id')
        // truly returns the admin's reply regardless of timestamp precision.
        $reply = $aliceTicket->replies()->reorder()->latest('id')->first();
        $this->assertTrue((bool) $reply->is_admin);
        $this->assertNull($reply->user_id);
        $this->assertSame(Ticket::STATUS_AWAITING_CUSTOMER, $aliceTicket->status);
        $this->assertTrue(AuditLog::where('action', 'tickets.replied')
            ->where('target_id', $aliceTicket->id)->exists());

        // Close.
        $this->actingAs($admin, 'admin')->post(route('reddy.tickets.close', $aliceTicket))->assertRedirect();
        $this->assertSame(Ticket::STATUS_CLOSED, $aliceTicket->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'tickets.closed')
            ->where('target_id', $aliceTicket->id)->exists());

        // Closed filter shows it; the default open filter does not.
        $this->actingAs($admin, 'admin')->get(route('reddy.tickets.index', ['status' => 'closed']))
            ->assertOk()->assertSee('Alice problem');
        $this->actingAs($admin, 'admin')->get(route('reddy.tickets.index'))
            ->assertOk()->assertDontSee('Alice problem');

        // Reopen.
        $this->actingAs($admin, 'admin')->post(route('reddy.tickets.reopen', $aliceTicket))->assertRedirect();
        $this->assertSame(Ticket::STATUS_OPEN, $aliceTicket->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'tickets.reopened')
            ->where('target_id', $aliceTicket->id)->exists());
    }

    // ------------------------------------------------------------------
    // Download tracking on order-linked tickets
    // ------------------------------------------------------------------

    public function test_order_linked_ticket_shows_download_tracking_fields(): void
    {
        $admin = $this->makeSuperAdmin();
        $user = $this->makeUser();
        $book = $this->makeBook();
        $order = $this->makePaidOrder($user, $book);

        DownloadLog::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'order_id' => $order->id,
            'ip_address' => '203.0.113.45',
            'downloaded_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->post(route('support.store'), $this->ticketPayload([
            'subject' => 'Refund please',
            'category' => 'refund',
            'order_id' => $order->id,
        ]));
        $ticket = Ticket::where('user_id', $user->id)->first();

        $this->actingAs($admin, 'admin')->get(route('reddy.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Download Status')
            ->assertSee('IP Address')
            ->assertSee('Total Attempts')
            ->assertSee('Downloaded at')
            ->assertSee('203.0.113.45')
            ->assertSee($order->order_number);
    }

    // ------------------------------------------------------------------
    // Mail: only when configured, never blocks creation
    // ------------------------------------------------------------------

    public function test_ticket_creation_succeeds_when_mail_unconfigured(): void
    {
        // phpunit.xml sets MAIL_MAILER=array: not a real inbox.
        Mail::fake();

        $user = $this->makeUser();

        $this->actingAs($user)->post(route('support.store'), $this->ticketPayload())
            ->assertRedirect();

        $this->assertSame(1, Ticket::where('user_id', $user->id)->count());
        Mail::assertNothingSent();
    }

    public function test_admin_notification_sent_when_mail_configured(): void
    {
        Mail::fake();

        // Make mail look configured: a real transport + an SMTP host.
        config(['mail.default' => 'smtp']);
        Setting::where('key', 'mail.host')->update(['value' => 'smtp.example.com']);
        app(SettingsService::class)->forgetCache();

        $user = $this->makeUser();

        $this->actingAs($user)->post(route('support.store'), $this->ticketPayload())
            ->assertRedirect();

        Mail::assertSent(TicketCreatedMail::class, function (TicketCreatedMail $mail) use ($user) {
            return $mail->ticket->user_id === $user->id;
        });
    }

    public function test_ticket_creation_survives_mail_failure(): void
    {
        config(['mail.default' => 'smtp']);
        Setting::where('key', 'mail.host')->update(['value' => 'smtp.example.com']);
        app(SettingsService::class)->forgetCache();

        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $user = $this->makeUser();

        // The ticket is still created and the customer is redirected —
        // the mail exception is swallowed.
        $response = $this->actingAs($user)->post(route('support.store'), $this->ticketPayload());
        $ticket = Ticket::where('user_id', $user->id)->first();

        $this->assertNotNull($ticket);
        $response->assertRedirect(route('support.show', $ticket));
    }

    // ------------------------------------------------------------------
    // Escaping + throttling
    // ------------------------------------------------------------------

    public function test_message_bodies_are_escaped_on_render(): void
    {
        $user = $this->makeUser();
        $xss = '<script>alert("xss")</script><img src=x onerror=alert(1)>';

        $this->actingAs($user)->post(route('support.store'), $this->ticketPayload(['message' => $xss]));
        $ticket = Ticket::where('user_id', $user->id)->first();

        $this->actingAs($user)->get(route('support.show', $ticket))
            ->assertOk()
            ->assertDontSee($xss, false)
            ->assertSee(e($xss), false);
    }

    public function test_ticket_creation_is_throttled(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->post(route('support.store'), $this->ticketPayload(['subject' => 'Ticket '.$i]))
                ->assertRedirect();
        }

        // The 6th ticket inside a minute is rejected.
        $this->actingAs($user)
            ->post(route('support.store'), $this->ticketPayload(['subject' => 'Ticket 6']))
            ->assertStatus(429);

        $this->assertSame(5, Ticket::where('user_id', $user->id)->count());
    }
}
