<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Http\Controllers\Concerns\VerifiesTurnstile;
use App\Models\Cart;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\PriceService;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Phase 3: checkout. Phase 4 adds coupons and combo purchases.
 *
 * - GET /checkout shows the summary (order is created only when the
 *   customer clicks Pay — page views never create orders).
 * - POST /checkout creates the order + Razorpay order server-side and
 *   returns the checkout.js parameters as JSON.
 * - POST /checkout/verify verifies the payment signature server-side.
 * - POST /checkout/coupon applies a coupon code (server-side only).
 * - DELETE /checkout/coupon removes it.
 * - A 100% coupon completes the order with NO Razorpay involvement:
 *   the order is marked paid with payment method "coupon".
 * - A combo bought via /combos/{slug}/buy is checked out from the
 *   session (checkout_combo_id) instead of the cart.
 * - No money value is ever trusted from the browser.
 */
class CheckoutController extends Controller
{
    use VerifiesTurnstile;

    public function __construct(
        protected PriceService $prices,
        protected RazorpayService $razorpay,
        protected OrderService $orders,
        protected CouponService $coupons,
    ) {}

    /**
     * Checkout page: server-side summary, coupon field, no-refund
     * checkbox, Pay button. When Razorpay is disabled or keys are
     * missing, a clear message is shown instead of a broken payment
     * form (unless a coupon makes the order free).
     */
    public function show(): View
    {
        $user = auth()->user();
        $purchase = $this->purchaseBase($user);

        if (! $purchase) {
            return view('checkout.empty');
        }

        [$books, $base, $combo] = $purchase;

        $quote = $this->settledQuote($user, $books, $base, $combo);

        $items = $combo ? $this->comboRows($combo, $user) : $this->cartRows($user);

        $summary = [
            'currency' => $base['currency'],
            'symbol' => $base['currency'] === 'INR' ? '₹' : '$',
            'display_subtotal' => $this->prices->format($base['subtotal'], $base['currency']),
            'display_discount' => $this->prices->format($quote['discount'], $base['currency']),
            'display_total' => $this->prices->format($quote['grand_total'], $base['currency']),
        ];

        $gatewayError = $quote['free'] ? null : $this->gatewayError($base['currency']);

        return view('checkout.index', [
            'items' => $items,
            'summary' => $summary,
            'quote' => $quote,
            'combo' => $combo,
            'couponsEnabled' => $this->coupons->enabled(),
            'gatewayError' => $gatewayError,
        ]);
    }

    /**
     * Apply a coupon code to this checkout. Validation is server-side;
     * the code is kept in the session and re-validated at order time.
     * Rate-limited: 5 attempts per minute per customer+IP.
     */
    public function applyCoupon(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $rateKey = 'coupon-apply:'.$user->id.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            abort(429, 'Too many coupon attempts. Please wait a minute and try again.');
        }

        RateLimiter::hit($rateKey, 60);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $purchase = $this->purchaseBase($user);

        if (! $purchase) {
            return redirect()->route('cart.index');
        }

        [$books, $base, $combo] = $purchase;

        $quote = $this->coupons->quote($user, $books, $base['subtotal'], $base['currency'], $data['code'], $combo);

        if ($quote['coupon_error']) {
            return back()->withErrors(['coupon' => $quote['coupon_error']])->withInput();
        }

        session(['coupon_code' => $quote['coupon']->code]);

        return back()->with('status', 'Coupon '.$quote['coupon']->code.' applied.');
    }

    /**
     * Remove the applied coupon from this checkout.
     */
    public function removeCoupon(): RedirectResponse
    {
        session()->forget('coupon_code');

        return back()->with('status', 'Coupon removed.');
    }

    /**
     * Create the order and the Razorpay order. Returns checkout.js params,
     * or — for a 100% coupon — completes the order immediately and
     * returns the thank-you redirect.
     */
    public function store(Request $request): JsonResponse
    {
        // Phase 6: bot check before any order is created.
        $this->verifyTurnstile($request, 'checkout');

        $request->validate([
            'no_refund_accepted' => ['required', 'accepted'],
        ], [
            'no_refund_accepted.accepted' => 'Please accept the no-refund policy to continue.',
        ]);

        $user = auth()->user();
        $purchase = $this->purchaseBase($user);

        if (! $purchase) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        [$books, $base, $combo] = $purchase;

        $quote = $this->coupons->quote($user, $books, $base['subtotal'], $base['currency'], session('coupon_code'), $combo);

        if (session('coupon_code') && $quote['coupon_error']) {
            session()->forget('coupon_code');

            return response()->json(['message' => $quote['coupon_error']], 422);
        }

        if (! $quote['free'] && ($error = $this->gatewayError($base['currency']))) {
            return response()->json(['message' => $error], 422);
        }

        $method = $quote['free'] ? 'coupon' : 'razorpay';

        try {
            $order = $combo
                ? $this->orders->createFromCombo($user, $combo, $request->ip(), $request->userAgent(), $quote['coupon'], $method)
                : $this->orders->createFromCart($user, $request->ip(), $request->userAgent(), $quote['coupon'], $method);
        } catch (CouponException $e) {
            session()->forget('coupon_code');

            return response()->json(['message' => $e->getMessage()], 422);
        }

        session()->forget(['coupon_code', 'checkout_combo_id']);

        // 100% coupon: no Razorpay at all — the order is simply paid.
        if ($quote['free']) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'amount' => 0,
                'currency' => $order->currency,
                'status' => Payment::STATUS_CAPTURED,
            ]);

            $order = $this->orders->markPaid($order->fresh(), $payment, 'coupon');

            return response()->json(['redirect' => route('checkout.thankyou', $order)]);
        }

        $razorpayOrder = $this->razorpay->createOrder(
            $order->order_number,
            $order->amountInSubunits(),
            $order->currency,
        );

        if (! $razorpayOrder) {
            return response()->json([
                'message' => 'The payment gateway is temporarily unavailable. Please try again in a few minutes.',
            ], 502);
        }

        Payment::create([
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrder['id'],
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'status' => Payment::STATUS_CREATED,
        ]);

        return response()->json([
            'key' => $this->razorpay->keyId(),
            'razorpay_order_id' => $razorpayOrder['id'],
            'amount' => $order->amountInSubunits(),
            'currency' => $order->currency,
            'order_number' => $order->order_number,
            'name' => (string) settings('site.name', 'MyEbookBazaar'),
            'prefill' => ['name' => $user->name, 'email' => $user->email],
            'verify_url' => route('checkout.verify'),
            'failure_url' => route('checkout.report-failure'),
        ]);
    }

    /**
     * Verify the Razorpay payment signature after checkout.js succeeds.
     * Tampered or forged signatures are rejected — the order is marked
     * failed and the customer is told.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $payment || $payment->order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order = $payment->order;

        // Already handled (double click / replay) — just send them along.
        if ($order->isPaid()) {
            return response()->json(['redirect' => route('checkout.thankyou', $order)]);
        }

        $valid = $this->razorpay->verifyPaymentSignature(
            $data['razorpay_order_id'],
            $data['razorpay_payment_id'],
            $data['razorpay_signature'],
        );

        if (! $valid) {
            $this->orders->markFailed($order, $payment, 'Payment signature verification failed.');

            return response()->json([
                'message' => 'We could not verify this payment. No money was taken for this attempt — please try again.',
            ], 422);
        }

        // Bind the payment id now; the unique constraint makes replays safe.
        try {
            $payment->update(['razorpay_payment_id' => $data['razorpay_payment_id']]);
        } catch (\Illuminate\Database\QueryException) {
            // Another request already recorded this payment id (replay).
            return response()->json(['redirect' => route('checkout.thankyou', $order->fresh())]);
        }

        $this->orders->markPaid($order->fresh(), $payment->fresh(), 'checkout');

        return response()->json(['redirect' => route('checkout.thankyou', $order)]);
    }

    /**
     * checkout.js payment.failed handler: record the failure honestly.
     */
    public function reportFailure(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $payment || $payment->order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $this->orders->markFailed(
            $payment->order,
            $payment,
            $data['description'] ?? 'Payment failed at the gateway.'
        );

        return response()->json(['message' => 'Payment failed. You can try again from your cart.']);
    }

    /**
     * Thank-you page with download buttons. Only the buyer, only paid.
     */
    public function thankyou(Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 404);

        if (! $order->isPaid()) {
            return redirect()->route('orders.show', $order)
                ->with('status', 'Your payment is still being confirmed.');
        }

        $order->load('items.book');

        return view('checkout.thankyou', [
            'order' => $order,
            'downloadsEnabled' => settings_enabled('features.downloads', true),
            'downloadLimit' => (int) settings('downloads.limit', 5),
        ]);
    }

    /**
     * What is being bought: [books, base{currency,subtotal}, combo|null].
     * A combo in the session replaces the cart; an invalid combo falls
     * back to the cart (or null when there is nothing to buy).
     */
    protected function purchaseBase($user): ?array
    {
        $combo = $this->checkoutCombo($user);

        if ($combo) {
            $books = $combo->books();

            if ($books->isEmpty()) {
                session()->forget('checkout_combo_id');

                return null;
            }

            $cq = $this->prices->getComboPrice($combo, $user);

            return [$books, ['currency' => $cq['currency'], 'subtotal' => round($cq['amount'], 2)], $combo];
        }

        $items = $this->cartItems($user);

        if ($items->isEmpty()) {
            return null;
        }

        $summary = $this->prices->total($items->map(fn (Cart $row) => $row->book), $user);

        return [
            $items->map(fn (Cart $row) => $row->book),
            ['currency' => $summary['currency'], 'subtotal' => round($summary['total'], 2)],
            null,
        ];
    }

    /**
     * The combo being checked out, or null. Invalid, inactive, empty
     * or fully-owned combos are dropped from the session. A combo
     * where the customer owns SOME books is kept, with a warning.
     */
    protected function checkoutCombo($user): ?Combo
    {
        $id = session('checkout_combo_id');

        if (! $id || ! settings_enabled('features.combos', true)) {
            session()->forget('checkout_combo_id');

            return null;
        }

        $combo = Combo::query()->where('id', $id)->where('is_active', true)->first();

        if (! $combo || $combo->books()->isEmpty() || $combo->userOwnsAllBooks($user)) {
            session()->forget('checkout_combo_id');

            return null;
        }

        $owned = $combo->booksOwnedBy($user);

        if ($owned->isNotEmpty()) {
            $titles = $owned->pluck('title')->map(fn ($t) => '“'.$t.'”')->join(', ');
            session()->flash(
                'combo_warning',
                'You already own '.$titles.' from this combo. You can still buy the full combo at the listed price.'
            );
        }

        return $combo;
    }

    /**
     * Build the coupon quote, dropping a session coupon that has become
     * invalid (expired, used up) since it was applied.
     */
    protected function settledQuote($user, $books, array $base, ?Combo $combo = null): array
    {
        $quote = $this->coupons->quote($user, $books, $base['subtotal'], $base['currency'], session('coupon_code'), $combo);

        if (session('coupon_code') && $quote['coupon_error']) {
            session()->flash('coupon_notice', $quote['coupon_error']);
            session()->forget('coupon_code');
            $quote = $this->coupons->quote($user, $books, $base['subtotal'], $base['currency'], null, $combo);
        }

        return $quote;
    }

    protected function cartItems($user)
    {
        return Cart::forVisitor($user, session()->getId())->get()
            ->filter(fn (Cart $row) => $row->book && $row->book->is_active);
    }

    protected function cartRows($user)
    {
        $items = $this->cartItems($user);

        foreach ($items as $row) {
            $quote = $this->prices->getPrice($row->book, $user);
            $row->price_display = $this->prices->format($quote['amount'], $quote['currency']);
        }

        return $items;
    }

    protected function comboRows(Combo $combo, $user)
    {
        return $combo->books()->map(function ($book) use ($user) {
            $quote = $this->prices->getPrice($book, $user);
            $row = new \stdClass();
            $row->book = $book;
            $row->price_display = $this->prices->format($quote['amount'], $quote['currency']);

            return $row;
        });
    }

    /**
     * Null when the gateway can take this currency, otherwise a
     * human-readable reason shown on the checkout page.
     */
    protected function gatewayError(string $currency): ?string
    {
        if (! $this->razorpay->configured()) {
            return 'Online payment is not set up yet. Please try again later or contact support.';
        }

        if (! $this->razorpay->currencyAllowed($currency)) {
            return 'Payments in '.($currency === 'INR' ? 'rupees' : 'US dollars').' are currently disabled. Please contact support.';
        }

        return null;
    }
}
