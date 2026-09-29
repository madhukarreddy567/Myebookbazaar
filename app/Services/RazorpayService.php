<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * Phase 3: the ONE place that talks to Razorpay.
 *
 * - Keys come only from the Settings Center (never hardcoded, never .env).
 * - Every method degrades gracefully when Razorpay is disabled or the
 *   keys are missing: callers get null/false and show a friendly message.
 * - Signature verification is NEVER skipped.
 */
class RazorpayService
{
    public function __construct(protected SettingsService $settings) {}

    /**
     * True when the master switch is on AND both keys are present.
     */
    public function configured(): bool
    {
        return $this->settings->enabled('payments.razorpay_enabled', false)
            && (bool) $this->settings->get('payments.razorpay_key_id')
            && (bool) $this->settings->get('payments.razorpay_key_secret');
    }

    /**
     * True when payments in this currency are switched on in Settings.
     */
    public function currencyAllowed(string $currency): bool
    {
        return $currency === 'INR'
            ? $this->settings->enabled('payments.inr_enabled', true)
            : $this->settings->enabled('payments.usd_enabled', true);
    }

    public function keyId(): ?string
    {
        $keyId = $this->settings->get('payments.razorpay_key_id');

        return $keyId ? (string) $keyId : null;
    }

    public function mode(): string
    {
        return (string) $this->settings->get('payments.razorpay_mode', 'test');
    }

    /**
     * SDK client, or null when not configured. Never throws for missing keys.
     */
    public function api(): ?Api
    {
        if (! $this->configured()) {
            return null;
        }

        return new Api(
            (string) $this->settings->get('payments.razorpay_key_id'),
            (string) $this->settings->get('payments.razorpay_key_secret'),
        );
    }

    /**
     * Create a Razorpay order. Amount is in paise/cents (subunits).
     *
     * @return array{id: string}|null  Null when the API call fails.
     */
    public function createOrder(string $receipt, int $amountSubunits, string $currency): ?array
    {
        $api = $this->api();

        if (! $api) {
            return null;
        }

        try {
            $order = $api->order->create([
                'receipt' => $receipt,
                'amount' => $amountSubunits,
                'currency' => $currency,
                'notes' => ['receipt' => $receipt],
            ]);

            return ['id' => (string) $order['id']];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Verify the checkout success signature. Returns false (never throws)
     * when the signature is invalid or Razorpay is not configured.
     */
    public function verifyPaymentSignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        $api = $this->api();

        if (! $api) {
            return false;
        }

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Verify a webhook payload against the webhook secret from Settings.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->settings->get('payments.razorpay_webhook_secret');

        if (! $secret) {
            return false;
        }

        try {
            // Static-safe: the SDK method only does HMAC comparison.
            (new Api('', ''))->utility->verifyWebhookSignature($payload, $signature, (string) $secret);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Real lightweight API check for the Settings "Test connection" button.
     * Fetches a deliberately non-existent order: HTTP 400 ("not found")
     * proves the keys authenticate; 401/403 proves they don't.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->settings->enabled('payments.razorpay_enabled', false)) {
            return ['ok' => false, 'message' => 'Razorpay is disabled — enable it first.'];
        }

        $keyId = $this->settings->get('payments.razorpay_key_id');
        $secret = $this->settings->get('payments.razorpay_key_secret');

        if (! $keyId || ! $secret) {
            return ['ok' => false, 'message' => 'Key ID or Key Secret is missing.'];
        }

        $mode = $this->mode();
        $expected = $mode === 'live' ? 'rzp_live_' : 'rzp_test_';
        if (! str_starts_with((string) $keyId, $expected)) {
            return ['ok' => false, 'message' => "Key ID does not look like a {$mode} key (expected to start with {$expected})."];
        }

        // Read-only probe: listing orders authenticates the keys without
        // creating anything. Uses Laravel's HTTP client (10s timeout) so
        // tests can fake it and a dead network fails fast instead of hanging.
        try {
            $response = Http::timeout(10)
                ->withBasicAuth((string) $keyId, (string) $secret)
                ->get('https://api.razorpay.com/v1/orders', ['count' => 1]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach Razorpay: '.$e->getMessage()];
        }

        if ($response->status() === 401) {
            return ['ok' => false, 'message' => 'Razorpay rejected the keys (authentication failed). Check the Key ID and Key Secret.'];
        }

        if ($response->successful()) {
            return ['ok' => true, 'message' => "Connected to Razorpay ({$mode} mode) — keys authenticate correctly."];
        }

        return ['ok' => false, 'message' => 'Razorpay returned HTTP '.$response->status().'.'];
    }
}
