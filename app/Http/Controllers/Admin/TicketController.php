<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin support inbox — existing admin style only, no redesign.
 *
 * - Filter by open / closed; search by subject or customer email.
 * - On order-linked tickets the download-tracking panel (Download
 *   Status / IP Address / Total Attempts) is shown, reusing the same
 *   Order methods as the order detail view, so support can judge
 *   refund requests under the no-refund policy at a glance.
 * - Every action (reply, close, reopen) is audit-logged.
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);

        $tickets = $query->paginate(15)->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'q']),
            'openCount' => Ticket::where('status', '!=', Ticket::STATUS_CLOSED)->count(),
            'closedCount' => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
        ]);
    }

    /**
     * Shared filter logic for the inbox list.
     */
    protected function filteredQuery(Request $request): Builder
    {
        $query = Ticket::with(['user', 'order'])->latest();

        $status = (string) $request->string('status', 'open');
        if ($status === 'closed') {
            $query->where('status', Ticket::STATUS_CLOSED);
        } elseif ($status === 'all') {
            // No constraint.
        } else {
            $query->where('status', '!=', Ticket::STATUS_CLOSED);
        }

        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], '', (string) $request->string('q')).'%';
            $query->where(function ($w) use ($q) {
                $w->where('subject', 'like', $q)
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('email', 'like', $q)
                        ->orWhere('name', 'like', $q));
            });
        }

        return $query;
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['user', 'replies.user', 'order.downloadLogs']);

        $order = $ticket->order;

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            // Download tracking for refund-validity checks (same methods
            // as the admin order detail view).
            'firstDownload' => $order?->firstSuccessfulDownload(),
            'downloadAttempts' => $order ? $order->downloadAttemptsCount() : 0,
        ]);
    }

    /**
     * Admin reply. The ball moves to the customer. A reply to a closed
     * ticket reopens it.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $oldStatus = $ticket->status;

        $ticket->replies()->create([
            'user_id' => null,
            'is_admin' => true,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => Ticket::STATUS_AWAITING_CUSTOMER]);

        Audit::record('tickets.replied', 'ticket', $ticket->id,
            ['status' => $oldStatus],
            ['status' => $ticket->status, 'by' => 'admin']);

        return back()->with('status', 'Reply sent. The ticket is now awaiting the customer.');
    }

    /**
     * Close the ticket.
     */
    public function close(Ticket $ticket): RedirectResponse
    {
        if ($ticket->isClosed()) {
            return back()->with('status', 'This ticket is already closed.');
        }

        $oldStatus = $ticket->status;
        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        Audit::record('tickets.closed', 'ticket', $ticket->id,
            ['status' => $oldStatus],
            ['status' => Ticket::STATUS_CLOSED]);

        return back()->with('status', 'Ticket closed.');
    }

    /**
     * Reopen a closed ticket.
     */
    public function reopen(Ticket $ticket): RedirectResponse
    {
        if (! $ticket->isClosed()) {
            return back()->with('status', 'This ticket is already open.');
        }

        $ticket->update(['status' => Ticket::STATUS_OPEN]);

        Audit::record('tickets.reopened', 'ticket', $ticket->id,
            ['status' => Ticket::STATUS_CLOSED],
            ['status' => Ticket::STATUS_OPEN]);

        return back()->with('status', 'Ticket reopened.');
    }
}
