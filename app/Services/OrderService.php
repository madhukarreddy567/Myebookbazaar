<?php

namespace App\Services;

use App\Mail\DownloadReadyMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\PaymentFailedMail;
use App\Models\Cart;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Phase 3: order lifecycle in one place.
 *
 * - createFromCart(): snapshots server-side prices into an order.
 * - markPaid() / markFailed(): idempotent state transitions — safe to
 *   call from the checkout handler, the webhook, and the admin panel.
 *   The second call for an already-final order is a no-op.
 */
class OrderService
{
    public function __construct(
        protected PriceService $prices,
        protected SettingsService $settings,
        protected CouponService $coupons,
    ) {}

    /**
     * Build a pending order from the customer's cart. All money is
     * recalculated here from the database via PriceService.
     *
     * When a coupon is passed, its discount is recomputed here from the
     * database (never trusted from the caller) and its usage is
     * consumed inside the same transaction under a row lock.
     *
     * @throws \RuntimeException when the cart is empty.
     * @throws \App\Exceptions\CouponException when the coupon can no
     *         longer be used (the whole order is rolled back).
     */
    public function createFromCart(
        User $user,
        string $ipAddress,
        ?string $userAgent,
        ?Coupon $coupon = null,
        string $paymentMethod = 'razorpay',
    ): Order {
        $rows = Cart::forVisitor($user, session()->getId())->get()
            ->filter(fn (Cart $row) => $row->book && $row->book->is_active);

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $summary = $this->prices->total($rows->map(fn (Cart $row) => $row->book), $user);

        $books = $rows->map(fn (Cart $row) => $row->book);
        $eligible = $coupon ? $this->coupons->eligibleSubtotal($coupon, $user, $books, $summary['total'], null) : 0.0;
        $discount = $coupon ? $this->coupons->discountFor($coupon, $eligible) : 0.0;
        $grand = round($summary['total'] - $discount, 2);

        return DB::transaction(function () use ($user, $rows, $summary, $discount, $grand, $coupon, $paymentMethod, $ipAddress, $userAgent) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateNumber(),
                'currency' => $summary['currency'],
                'subtotal' => $summary['total'],
                'discount_total' => $discount,
                'tax_total' => 0,
                'grand_total' => $grand,
                'status' => Order::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'coupon_code' => $coupon?->code,
                'no_refund_accepted' => true,
                'no_refund_accepted_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
            ]);

            foreach ($summary['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $line['book_id'],
                    'title' => $line['title'],
                    'unit_price' => $line['amount'],
                    'quantity' => 1,
                    'line_total' => $line['amount'],
                    'currency' => $line['currency'],
                ]);
            }

            if ($coupon) {
                $this->coupons->consume($order, $coupon, $user);
            }

            return $order;
        });
    }

    /**
     * Phase 4: build a pending order from a combo purchase. The combo
     * price is split across one order item per book (rounded so the
     * lines sum exactly to the combo price) so every book stays
     * individually downloadable. Coupon handling mirrors
     * createFromCart.
     *
     * @throws \RuntimeException when the combo has no active books.
     * @throws \App\Exceptions\CouponException when the coupon can no
     *         longer be used.
     */
    public function createFromCombo(
        User $user,
        Combo $combo,
        string $ipAddress,
        ?string $userAgent,
        ?Coupon $coupon = null,
        string $paymentMethod = 'razorpay',
    ): Order {
        $books = $combo->books();

        if ($books->isEmpty()) {
            throw new \RuntimeException('This combo is not available right now.');
        }

        if ($combo->userOwnsAllBooks($user)) {
            throw new \RuntimeException('You already own every book in this combo.');
        }

        $quote = $this->prices->getComboPrice($combo, $user);
        $subtotal = round($quote['amount'], 2);
        $eligible = $coupon ? $this->coupons->eligibleSubtotal($coupon, $user, $books, $subtotal, $combo) : 0.0;
        $discount = $coupon ? $this->coupons->discountFor($coupon, $eligible) : 0.0;
        $grand = round($subtotal - $discount, 2);

        return DB::transaction(function () use ($user, $combo, $books, $quote, $subtotal, $discount, $grand, $coupon, $paymentMethod, $ipAddress, $userAgent) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateNumber(),
                'currency' => $quote['currency'],
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => 0,
                'grand_total' => $grand,
                'status' => Order::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'coupon_code' => $coupon?->code,
                'no_refund_accepted' => true,
                'no_refund_accepted_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
            ]);

            // Split the combo price across the books so the lines add
            // up to exactly the combo price.
            $count = $books->count();
            $remaining = $subtotal;

            foreach ($books->values() as $index => $book) {
                $line = $index === $count - 1
                    ? round($remaining, 2)
                    : round($subtotal / $count, 2);
                $remaining = round($remaining - $line, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'unit_price' => $line,
                    'quantity' => 1,
                    'line_total' => $line,
                    'currency' => $quote['currency'],
                ]);
            }

            if ($coupon) {
                $this->coupons->consume($order, $coupon, $user);
            }

            \App\Support\Audit::record(
                'order.combo_created',
                'orders',
                $order->id,
                [],
                ['order_number' => $order->order_number, 'combo' => $combo->title, 'grand_total' => (string) $grand, 'currency' => $quote['currency']],
            );

            return $order;
        });
    }

    /**
     * Mark an order paid. Idempotent: already-paid orders are returned
     * unchanged (webhook replays and double clicks are harmless).
     */
    public function markPaid(Order $order, ?Payment $payment = null, string $source = 'checkout'): Order
    {
        return DB::transaction(function () use ($order, $payment, $source) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            $locked->update(['status' => Order::STATUS_PAID]);

            if ($payment) {
                $payment->update(['status' => Payment::STATUS_CAPTURED]);
            }

            // The ebooks are bought — the cart is done.
            Cart::forVisitor($locked->user, session()->getId())->delete();

            $this->sendMail($locked->user->email, new OrderConfirmationMail($locked), "order confirmation to {$locked->user->email}");
            $this->sendMail($locked->user->email, new DownloadReadyMail($locked), "download-ready email to {$locked->user->email}");

            \App\Support\Audit::record(
                'order.paid',
                'orders',
                $locked->id,
                [],
                ['order_number' => $locked->order_number, 'source' => $source, 'grand_total' => (string) $locked->grand_total, 'currency' => $locked->currency],
            );

            return $locked;
        });
    }

    /**
     * Mark an order failed. Idempotent: already-final orders are untouched.
     */
    public function markFailed(Order $order, ?Payment $payment, string $reason): Order
    {
        return DB::transaction(function () use ($order, $payment, $reason) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [Order::STATUS_PAID, Order::STATUS_FAILED, Order::STATUS_CANCELLED], true)) {
                return $locked;
            }

            $locked->update(['status' => Order::STATUS_FAILED]);

            if ($payment) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'failure_reason' => mb_substr($reason, 0, 500),
                ]);
            }

            $this->sendMail($locked->user->email, new PaymentFailedMail($locked, $reason), "payment-failed email to {$locked->user->email}");

            \App\Support\Audit::record(
                'order.failed',
                'orders',
                $locked->id,
                [],
                ['order_number' => $locked->order_number, 'reason' => mb_substr($reason, 0, 200)],
            );

            return $locked;
        });
    }

    /**
     * Admin "Mark as paid" with a required note. Audited.
     */
    public function markPaidManually(Order $order, int $adminId, string $note): Order
    {
        return DB::transaction(function () use ($order, $adminId, $note) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            $locked->update(['status' => Order::STATUS_PAID, 'payment_method' => 'manual']);

            Payment::create([
                'order_id' => $locked->id,
                'amount' => $locked->grand_total,
                'currency' => $locked->currency,
                'status' => Payment::STATUS_CAPTURED,
                'failure_reason' => 'Manual mark-as-paid: '.mb_substr($note, 0, 400),
            ]);

            Cart::forVisitor($locked->user, session()->getId())->delete();

            \App\Support\Audit::record(
                'order.mark_paid_manual',
                'orders',
                $locked->id,
                ['status' => $order->status],
                ['status' => Order::STATUS_PAID, 'note' => mb_substr($note, 0, 500)],
                $adminId,
            );

            $this->sendMail($locked->user->email, new OrderConfirmationMail($locked), "order confirmation to {$locked->user->email}");
            $this->sendMail($locked->user->email, new DownloadReadyMail($locked), "download-ready email to {$locked->user->email}");

            return $locked;
        });
    }

    /**
     * Email failures must never break checkout — log and continue.
     */
    protected function sendMail(string $to, \Illuminate\Mail\Mailable $mailable, string $context): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            report($e);
            logger()->warning("Phase 3: failed sending {$context}: ".$e->getMessage());
        }
    }
}
