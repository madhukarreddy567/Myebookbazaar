<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PriceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 3: admin order management. Phase 5: CSV export.
 *
 * - List with status / currency / date filters + search.
 * - Revenue is ALWAYS shown per currency — INR and USD are never added.
 * - "Mark as paid" needs a written note and is audited.
 */
class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected PriceService $prices,
    ) {}

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);

        $orders = $query->paginate(15)->withQueryString();

        // Revenue per currency, paid orders only — never combined.
        $revenue = Order::where('status', Order::STATUS_PAID)
            ->selectRaw('currency, SUM(grand_total) as total, COUNT(*) as count')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        return view('admin.orders.index', [
            'orders' => $orders,
            'revenue' => $revenue,
            'filters' => $request->only(['status', 'currency', 'from', 'to', 'q']),
        ]);
    }

    /**
     * CSV export of orders (respects the same filters as the list).
     * Streams the response so large exports stay memory-safe.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request);

        $filename = 'orders-'.now('Asia/Kolkata')->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order number', 'Customer email', 'Currency', 'Subtotal', 'Discount', 'Grand total', 'Status', 'Coupon', 'Created (IST)']);

            $query->with('user')->orderBy('id')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->order_number,
                        $order->user?->email,
                        $order->currency,
                        $order->subtotal,
                        $order->discount_total,
                        $order->grand_total,
                        $order->status,
                        $order->coupon_code,
                        $order->created_at->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Shared filter logic for the list and the export.
     */
    protected function filteredQuery(Request $request): Builder
    {
        $query = Order::with(['user', 'items'])->latest();

        if ($request->filled('status') && in_array((string) $request->string('status'), ['pending', 'paid', 'failed', 'cancelled', 'refunded'], true)) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('currency') && in_array((string) $request->string('currency'), ['INR', 'USD'], true)) {
            $query->where('currency', $request->string('currency'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], '', $request->string('q')).'%';
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', $q)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $q));
            });
        }

        return $query;
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.book', 'payments', 'downloadLogs.book']);

        // Download tracking for refund-validity checks (no-refund policy):
        // first successful download + total attempts, at a glance.
        return view('admin.orders.show', [
            'order' => $order,
            'firstDownload' => $order->firstSuccessfulDownload(),
            'downloadAttempts' => $order->downloadAttemptsCount(),
        ]);
    }

    /**
     * Manual "Mark as paid" — for bank transfers / phone-verified payments.
     * Requires a note; the audit log records who did it and why.
     */
    public function markPaid(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'note.required' => 'Please write a note explaining why you are marking this order paid.',
            'note.min' => 'The note must be at least 10 characters.',
        ]);

        if (! $order->canTransitionTo(Order::STATUS_PAID)) {
            return back()->with('status', 'This order cannot be marked as paid (status: '.$order->statusLabel().').');
        }

        $this->orders->markPaidManually($order, auth('admin')->id(), $data['note']);

        return redirect()->route('reddy.orders.show', $order)
            ->with('status', 'Order marked as paid. The customer has been emailed.');
    }
}
