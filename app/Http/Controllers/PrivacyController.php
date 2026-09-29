<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Support\Security;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Phase 6: customer privacy controls.
 *
 * - GET  /account/export  → JSON download: profile, orders (with items
 *   and payments), and download history.
 * - GET  /account/delete  → confirmation screen.
 * - DELETE /account       → anonymize the account (scrub PII) while
 *   RETAINING order/payment records for tax & accounting. The customer
 *   is logged out afterwards.
 */
class PrivacyController extends Controller
{
    /**
     * JSON export of everything we hold about the customer.
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = $user->orders()->with(['items.book:id,title', 'payments'])->get();

        $downloads = DownloadLog::with('book:id,title')
            ->where('user_id', $user->id)
            ->orderByDesc('downloaded_at')
            ->get();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'country_code' => $user->country_code,
                'country_name' => $user->country_name,
                'pricing_region' => $user->pricing_region,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'orders' => $orders->map(fn ($order) => [
                'number' => $order->order_number,
                'status' => $order->status,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'grand_total' => $order->grand_total,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'title' => $item->title,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                    'currency' => $item->currency,
                ])->values(),
                'payments' => $order->payments->map(fn ($payment) => [
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'razorpay_payment_id' => $payment->razorpay_payment_id,
                    'created_at' => $payment->created_at->toIso8601String(),
                ])->values(),
            ])->values(),
            'downloads' => $downloads->map(fn ($log) => [
                'book' => $log->book?->title,
                'downloaded_at' => $log->downloaded_at?->toIso8601String(),
            ])->values(),
        ];

        Security::log('privacy.export', 'Customer exported their data.', ['user_id' => $user->id]);

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="myebookbazaar-data-export.json"',
        ]);
    }

    /**
     * "Are you sure?" screen before deletion.
     */
    public function confirmDelete(): View
    {
        return view('account.delete');
    }

    /**
     * Anonymize the account. Orders, payments and download logs are
     * RETAINED (tax/accounting records) — only personal identifiers are
     * scrubbed. The customer is signed out immediately after.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        DB::transaction(function () use ($user) {
            // Scrub PII. Orders/payments/download logs stay untouched —
            // they are the store's tax records.
            $user->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted-'.$user->id.'@deleted.myebookbazaar.local',
                'password' => Hash::make(Str::random(40)),
                'remember_token' => null,
                'email_verified_at' => null,
                'is_blocked' => true,
            ])->save();

            // The cart belongs to the person, not the records: clear it.
            DB::table('carts')->where('user_id', $user->id)->delete();
        });

        Security::log('privacy.account_deleted', 'Customer deleted (anonymized) their account.', [
            'user_id' => $user->id,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('status', 'Your account has been deleted. Your order records are retained for tax purposes as required by law.');
    }
}
