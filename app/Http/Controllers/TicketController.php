<?php

namespace App\Http\Controllers;

use App\Mail\TicketCreatedMail;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Customer support tickets (account area).
 *
 * - A customer only ever sees their OWN tickets — enforced by
 *   TicketPolicy on show/reply (403 otherwise).
 * - Ticket creation is throttled to 5/min; replies to 10/min.
 * - The admin inbox is notified by email ONLY when mail is actually
 *   configured; a mail failure (or no mail config at all) never blocks
 *   ticket creation.
 * - Message bodies are escaped by Blade on render ({{ }}).
 */
class TicketController extends Controller
{
    use AuthorizesRequests;

    /**
     * Mail transports that cannot deliver to a real inbox — sending to
     * them means "mail is not configured".
     */
    protected const NON_DELIVERY_MAILERS = ['log', 'array', 'fail', 'null'];

    /**
     * The customer's own tickets, newest first.
     */
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->withCount('replies')
            ->latest()
            ->paginate(10);

        return view('support.index', ['tickets' => $tickets]);
    }

    /**
     * New-ticket form. The optional order reference lists the customer's
     * own orders only — enforced again server-side on store.
     */
    public function create(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get(['id', 'order_number', 'currency', 'grand_total', 'status']);

        return view('support.create', ['orders' => $orders]);
    }

    /**
     * Open a ticket. The first message is stored as the ticket's first
     * reply so the whole thread lives in one place.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // The subject lands in a mail header (TicketCreatedMail) —
            // reject CR/LF like the contact form does (header injection).
            'subject' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'category' => ['required', 'string', Rule::in(array_keys(Ticket::CATEGORIES))],
            'order_id' => [
                'nullable', 'integer',
                // The order must belong to the customer — never trusted
                // from the client.
                Rule::exists('orders', 'id')->where(fn ($q) => $q->where('user_id', $request->user()->id)),
            ],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'order_id' => $validated['order_id'] ?? null,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin' => false,
            'body' => $validated['message'],
        ]);

        $this->notifySupport($ticket->fresh(['user', 'order', 'replies']));

        return redirect()
            ->route('support.show', $ticket)
            ->with('status', 'Your ticket has been opened. We reply within 2 working days.');
    }

    /**
     * The ticket thread (customer view).
     */
    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['replies.user', 'order']);

        return view('support.show', ['ticket' => $ticket]);
    }

    /**
     * Customer reply. Replying to a closed ticket reopens it — the
     * customer obviously has something new to say.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin' => false,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => Ticket::STATUS_AWAITING_SUPPORT]);

        return redirect()
            ->route('support.show', $ticket)
            ->with('status', 'Your reply has been sent.');
    }

    /**
     * Notify the support inbox about a new ticket. Mail problems must
     * NEVER break ticket creation — this is best-effort by design.
     */
    protected function notifySupport(Ticket $ticket): void
    {
        if (! $this->mailIsConfigured()) {
            return;
        }

        try {
            $to = (string) settings('site.support_email', config('mail.from.address'));

            Mail::to($to)->send(new TicketCreatedMail($ticket));
        } catch (\Throwable $e) {
            // Logged, not thrown: the ticket already exists and the
            // customer already got their confirmation.
            report($e);
        }
    }

    /**
     * True only when the app can actually deliver mail to a real inbox.
     */
    protected function mailIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, self::NON_DELIVERY_MAILERS, true)) {
            return false;
        }

        // SMTP without a host is not usable.
        if ($mailer === 'smtp' && trim((string) settings('mail.host')) === '') {
            return false;
        }

        return true;
    }
}
