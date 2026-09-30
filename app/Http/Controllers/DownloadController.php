<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 3: secure ebook downloads.
 *
 * - The route itself is signed and expires after 10 minutes.
 * - Only the buyer of a PAID order containing the book may download.
 * - The per-book limit (Settings: downloads.limit, default 5) is enforced
 *   by counting download_logs rows — the 6th attempt is blocked.
 * - The count-and-insert runs inside a transaction with a row lock on
 *   the order+book log range, so two concurrent requests cannot both
 *   slip under the limit and serve a 6th download.
 * - Files live on the private disk; there is no public URL, ever.
 */
class DownloadController extends Controller
{
    public function download(Order $order, Book $book)
    {
        abort_unless($order->user_id === auth()->id(), 404);
        abort_unless($order->isPaid(), 403);
        abort_unless($order->items()->where('book_id', $book->id)->exists(), 404);

        // Every entitled attempt is logged (IP + user agent). downloaded_at
        // is stamped only when the file is actually served, so blocked
        // attempts never count against the download limit.
        $attempt = [
            'user_id' => auth()->id(),
            'book_id' => $book->id,
            'order_id' => $order->id,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1024),
        ];

        if (! $book->hasFile() || ! Storage::disk('local')->exists($book->file_path)) {
            // The DB row points at a file that is not on disk. In production
            // this happens when uploads live on the ephemeral container disk
            // (redeploy wiped them) — see PRIVATE_STORAGE_PATH in .env.example.
            Log::warning('Ebook download failed: file missing on disk', [
                'book_id' => $book->id,
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'file_path' => $book->file_path,
            ]);

            DownloadLog::create($attempt);

            return back()->withErrors([
                'download' => 'This ebook file is not available yet. Please contact support.',
            ]);
        }

        $limit = max(1, (int) settings('downloads.limit', 5));

        // Count-and-insert under one transaction: the FOR UPDATE lock on
        // this order+book's log range serialises concurrent downloads, so
        // the limit can never be overshot by a race.
        $served = DB::transaction(function () use ($order, $book, $attempt, $limit) {
            $used = DownloadLog::query()
                ->where('order_id', $order->id)
                ->where('book_id', $book->id)
                ->whereNotNull('downloaded_at')
                ->lockForUpdate()
                ->count();

            if ($used >= $limit) {
                DownloadLog::create($attempt);

                return false;
            }

            DownloadLog::create($attempt + ['downloaded_at' => now()]);

            return true;
        });

        if (! $served) {
            return back()->withErrors([
                'download' => "You have used all {$limit} downloads for this ebook. Please contact support if you need it again.",
            ]);
        }

        return Storage::disk('local')->download($book->file_path, $book->downloadFilename(), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
