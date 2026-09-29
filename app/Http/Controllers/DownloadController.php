<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 3: secure ebook downloads.
 *
 * - The route itself is signed and expires after 10 minutes.
 * - Only the buyer of a PAID order containing the book may download.
 * - The per-book limit (Settings: downloads.limit, default 5) is enforced
 *   by counting download_logs rows — the 6th attempt is blocked.
 * - Files live on the private disk; there is no public URL, ever.
 */
class DownloadController extends Controller
{
    public function download(Order $order, Book $book)
    {
        abort_unless($order->user_id === auth()->id(), 404);
        abort_unless($order->isPaid(), 403);
        abort_unless($order->items()->where('book_id', $book->id)->exists(), 404);

        if (! $book->hasFile() || ! Storage::disk('local')->exists($book->file_path)) {
            return back()->withErrors([
                'download' => 'This ebook file is not available yet. Please contact support.',
            ]);
        }

        $limit = max(1, (int) settings('downloads.limit', 5));
        $used = DownloadLog::usedCount($order->id, $book->id);

        if ($used >= $limit) {
            return back()->withErrors([
                'download' => "You have used all {$limit} downloads for this ebook. Please contact support if you need it again.",
            ]);
        }

        DownloadLog::create([
            'user_id' => auth()->id(),
            'book_id' => $book->id,
            'order_id' => $order->id,
            'ip_address' => request()->ip(),
        ]);

        $filename = str($book->title)->slug().'.pdf';

        return Storage::disk('local')->download($book->file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
