<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Phase 5: real dashboard.
 *
 * Revenue is ALWAYS per currency — INR and USD are never added together.
 * All dates stored in UTC, displayed in Asia/Kolkata.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $tz = 'Asia/Kolkata';
        $todayStart = Carbon::now($tz)->startOfDay()->utc();
        $thirtyDaysAgo = Carbon::now('UTC')->subDays(29)->startOfDay();

        // Revenue per currency, paid orders only — never combined.
        $revenue = Order::where('status', Order::STATUS_PAID)
            ->selectRaw('currency, SUM(grand_total) as total, COUNT(*) as orders')
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        $revenueInr = (float) ($revenue['INR']->total ?? 0);
        $revenueUsd = (float) ($revenue['USD']->total ?? 0);

        $ordersToday = Order::where('created_at', '>=', $todayStart)->count();
        $customers = User::count();
        $booksSold = OrderItem::whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID))
            ->sum('quantity');

        // Last 30 days revenue per day per currency (paid orders only).
        $daily = Order::where('status', Order::STATUS_PAID)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(created_at) as day, currency, SUM(grand_total) as total')
            ->groupBy('day', 'currency')
            ->get();

        $labels = [];
        $seriesInr = [];
        $seriesUsd = [];
        $cursor = $thirtyDaysAgo->copy();
        for ($i = 0; $i < 30; $i++) {
            $day = $cursor->format('Y-m-d');
            $labels[] = $cursor->setTimezone($tz)->format('j M');
            $seriesInr[] = (float) ($daily->firstWhere(fn ($r) => $r->day === $day && $r->currency === 'INR')->total ?? 0);
            $seriesUsd[] = (float) ($daily->firstWhere(fn ($r) => $r->day === $day && $r->currency === 'USD')->total ?? 0);
            $cursor->addDay();
        }

        $recentOrders = Order::with('user')->latest()->limit(8)->get();

        $topBooks = OrderItem::whereHas('order', fn ($q) => $q->where('status', Order::STATUS_PAID))
            ->selectRaw("book_id, SUM(quantity) as sold, SUM(CASE WHEN currency = 'INR' THEN line_total ELSE 0 END) as revenue_inr, SUM(CASE WHEN currency = 'USD' THEN line_total ELSE 0 END) as revenue_usd")
            ->groupBy('book_id')
            ->orderByDesc('sold')
            ->limit(5)
            ->with('book')
            ->get();

        return view('admin.dashboard', [
            'revenueInr' => $revenueInr,
            'revenueUsd' => $revenueUsd,
            'ordersToday' => $ordersToday,
            'customers' => $customers,
            'booksSold' => (int) $booksSold,
            'chartLabels' => $labels,
            'chartInr' => $seriesInr,
            'chartUsd' => $seriesUsd,
            'recentOrders' => $recentOrders,
            'topBooks' => $topBooks,
        ]);
    }

    /**
     * Dismiss the "turn on 2FA" warning banner for this session.
     * Phase 6 replaces this with real 2FA state.
     */
    public function dismiss2faWarning(): \Illuminate\Http\RedirectResponse
    {
        session(['dismissed_2fa_warning' => true]);

        return redirect()->route('reddy.dashboard');
    }
}
