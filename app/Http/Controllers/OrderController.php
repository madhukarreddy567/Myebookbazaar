<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PriceService;
use Illuminate\View\View;

/**
 * Phase 3: the customer's own orders + printable invoices.
 * A customer only ever sees their own orders (anything else is a 404).
 */
class OrderController extends Controller
{
    public function __construct(protected PriceService $prices) {}

    public function index(): View
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $order->load(['items.book', 'payments', 'user']);

        return view('orders.show', [
            'order' => $order,
            'siteName' => (string) settings('site.name', 'MyEbookBazaar'),
            'supportEmail' => (string) settings('site.support_email', ''),
        ]);
    }
}
