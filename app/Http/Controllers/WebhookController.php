<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 3: Razorpay webhook.
 *
 * - CSRF-exempt (see bootstrap/app.php); authenticity comes from the
 *   HMAC signature, never from the session.
 * - Idempotent: replayed deliveries hit the unique payment constraint
 *   and the "already captured" guard — they can never double-count.
 * - Every delivery is logged.
 */
class WebhookController extends Controller
{
    public function __construct(
        protected RazorpayService $razorpay,
        protected OrderService $orders,
    ) {}

    public function razorpay(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        if (! $this->razorpay->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Phase 3: Razorpay webhook rejected — bad signature.');

            // Phase 6: suspicious webhook signatures go to the security log.
            \App\Support\Security::log('webhook.bad_signature', 'Razorpay webhook rejected: bad signature.');

            return response('Bad signature', 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event) || ! isset($event['event'])) {
            return response('Bad payload', 400);
        }

        $eventName = $event['event'];
        $entity = $event['payload']['payment']['entity'] ?? $event['payload']['order']['entity'] ?? [];

        Log::info('Phase 3: Razorpay webhook received.', [
            'event' => $eventName,
            'razorpay_payment_id' => $entity['id'] ?? null,
            'razorpay_order_id' => $entity['order_id'] ?? null,
        ]);

        match ($eventName) {
            'payment.captured', 'order.paid' => $this->handleCaptured($entity, $event),
            'payment.failed' => $this->handleFailed($entity, $event),
            default => null, // Other events are logged and ignored.
        };

        return response('OK', 200);
    }

    protected function handleCaptured(array $entity, array $event): void
    {
        $razorpayOrderId = $entity['order_id'] ?? null;
        $razorpayPaymentId = $entity['id'] ?? null;

        if (! $razorpayOrderId) {
            Log::warning('Phase 3: captured webhook without order_id.');

            return;
        }

        DB::transaction(function () use ($razorpayOrderId, $razorpayPaymentId, $entity, $event) {
            $payment = Payment::where('razorpay_order_id', $razorpayOrderId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Phase 3: webhook for unknown Razorpay order.', ['razorpay_order_id' => $razorpayOrderId]);

                return;
            }

            // Idempotency: already captured → replay, do nothing.
            if ($payment->isCaptured()) {
                return;
            }

            $order = $payment->order;

            // Tamper guard: the captured amount/currency must match our order.
            $paidSubunits = (int) ($entity['amount'] ?? 0);
            $paidCurrency = (string) ($entity['currency'] ?? '');

            if ($paidSubunits !== $order->amountInSubunits() || $paidCurrency !== $order->currency) {
                Log::error('Phase 3: webhook amount/currency mismatch — NOT marking paid.', [
                    'order' => $order->order_number,
                    'expected' => $order->amountInSubunits().' '.$order->currency,
                    'received' => $paidSubunits.' '.$paidCurrency,
                ]);

                return;
            }

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'raw_webhook' => $event,
            ]);

            // markPaid is itself idempotent (order lock + status check).
            $this->orders->markPaid($order, $payment->fresh(), 'webhook');
        });
    }

    protected function handleFailed(array $entity, array $event): void
    {
        $razorpayOrderId = $entity['order_id'] ?? null;

        if (! $razorpayOrderId) {
            return;
        }

        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->first();

        if (! $payment) {
            Log::warning('Phase 3: failed-payment webhook for unknown order.', ['razorpay_order_id' => $razorpayOrderId]);

            return;
        }

        $payment->update(['raw_webhook' => $event]);

        $reason = $entity['error_description'] ?? 'Payment failed at the gateway.';

        $this->orders->markFailed($payment->order, $payment, (string) $reason);
    }
}
