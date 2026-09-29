<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Phase 3: My Library — every ebook the customer has bought,
 * with fresh signed download buttons.
 */
class LibraryController extends Controller
{
    public function index(): View
    {
        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', auth()->id())
                ->where('status', Order::STATUS_PAID))
            ->with(['book', 'order'])
            ->latest()
            ->get()
            // One row per book: the most recent purchase wins.
            ->unique('book_id')
            ->values();

        $limit = max(1, (int) settings('downloads.limit', 5));

        foreach ($items as $item) {
            $used = DownloadLog::usedCount($item->order_id, $item->book_id);
            $item->downloads_used = $used;
            $item->downloads_left = max(0, $limit - $used);
            $item->download_url = $item->book && $item->book->hasFile() && $used < $limit
                ? URL::temporarySignedRoute(
                    'download',
                    now()->addMinutes(10),
                    ['order' => $item->order_id, 'book' => $item->book_id],
                )
                : null;
        }

        return view('library.index', [
            'items' => $items,
            'limit' => $limit,
            'downloadsEnabled' => settings_enabled('features.downloads', true),
        ]);
    }
}
