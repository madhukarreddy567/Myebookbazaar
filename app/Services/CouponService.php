<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Phase 4: the ONE place where coupon logic lives.
 *
 * - Coupons are validated and priced on the server only. The browser
 *   only ever sends the code string; every number is recomputed here.
 * - A coupon is validated twice: once when the customer applies it
 *   (friendly errors), and again inside the order transaction under a
 *   row lock (consume()), so two simultaneous checkouts cannot
 *   double-use a limited coupon.
 */
class CouponService
{
    public function __construct(
        protected SettingsService $settings,
        protected PriceService $prices,
    ) {}

    public function enabled(): bool
    {
        return $this->settings->enabled('features.coupons', true);
    }

    public function findByCode(string $code): ?Coupon
    {
        $code = mb_strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        return Coupon::query()->where('code', $code)->first();
    }

    /**
     * Full server-side quote for a set of books plus an optional coupon
     * code. Never trusts the browser for any amount.
     *
     * @param  Collection<int, \App\Models\Book>  $books
     * @return array{currency: string, symbol: string, subtotal: float, discount: float, grand_total: float, coupon: ?Coupon, coupon_error: ?string, free: bool}
     */
    public function quote(User $user, Collection $books, float $subtotal, string $currency, ?string $code, ?Combo $combo = null): array
    {
        $base = [
            'currency' => $currency,
            'symbol' => $currency === 'INR' ? '₹' : '$',
            'subtotal' => round($subtotal, 2),
            'discount' => 0.0,
            'grand_total' => round($subtotal, 2),
            'coupon' => null,
            'coupon_error' => null,
            'free' => false,
        ];

        $code = $code ? mb_strtoupper(trim($code)) : null;

        if (! $code) {
            return $base;
        }

        $coupon = $this->findByCode($code);

        if (! $coupon) {
            $base['coupon_error'] = 'That coupon code is not valid. Check the spelling and try again.';

            return $base;
        }

        $error = $this->validateCoupon($coupon, $user, $books, $subtotal, $currency, $combo);

        if ($error !== null) {
            $base['coupon_error'] = $error;

            return $base;
        }

        $eligible = $this->eligibleSubtotal($coupon, $user, $books, $subtotal, $combo);
        $discount = $this->discountFor($coupon, $eligible);
        $grand = round($subtotal - $discount, 2);

        $base['discount'] = $discount;
        $base['grand_total'] = $grand;
        $base['coupon'] = $coupon;
        $base['free'] = $grand <= 0;

        return $base;
    }

    /**
     * Validate a coupon against this purchase. Returns null when the
     * coupon can be used, otherwise a human-readable reason.
     *
     * @param  Collection<int, \App\Models\Book>  $books
     */
    public function validateCoupon(Coupon $coupon, User $user, Collection $books, float $subtotal, string $currency, ?Combo $combo = null): ?string
    {
        if (! $this->enabled()) {
            return 'Coupons are currently disabled.';
        }

        if (! $coupon->is_active) {
            return 'This coupon is no longer active.';
        }

        $now = now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return 'This coupon is not active yet. It starts on '.$coupon->starts_at->format('d M Y').'.';
        }

        if ($coupon->ends_at && $now->gt($coupon->ends_at)) {
            return 'That coupon expired on '.$coupon->ends_at->format('d M Y').'. Try another code.';
        }

        if ($coupon->currency !== Coupon::CURRENCY_BOTH && $coupon->currency !== $currency) {
            return 'This coupon is only valid for '.($coupon->currency === 'INR' ? 'rupee (₹)' : 'US dollar ($)').' orders.';
        }

        if ($coupon->max_uses !== null && $coupon->uses_count >= $coupon->max_uses) {
            return 'This coupon has reached its usage limit.';
        }

        $userUses = CouponUsage::query()
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userUses >= $coupon->max_uses_per_user) {
            return 'You have already used this coupon.';
        }

        if ($coupon->first_order_only && Order::query()
                ->where('user_id', $user->id)
                ->where('status', Order::STATUS_PAID)
                ->exists()) {
            return 'This coupon is only for your first order.';
        }

        if (in_array($coupon->applies_to, [Coupon::APPLIES_BOOKS, Coupon::APPLIES_CATEGORIES], true)) {
            if ($combo) {
                // A combo has one price for the whole bundle, so a
                // book- or category-specific coupon can only apply when
                // it covers EVERY book in the combo.
                $allCovered = $books->every(fn ($book) => $coupon->appliesToBook($book));

                if (! $allCovered) {
                    return 'This coupon does not cover every book in this combo, so it cannot be used here.';
                }
            } else {
                $ok = $books->contains(fn ($book) => $coupon->appliesToBook($book));

                if (! $ok) {
                    return 'This coupon does not apply to the ebooks in your cart.';
                }
            }
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return 'This coupon needs a minimum order of '.
                $this->prices->format((float) $coupon->min_order_amount, $currency).'.';
        }

        return null;
    }

    /**
     * The part of the subtotal a coupon is allowed to discount.
     * - applies_to = all: the whole subtotal.
     * - specific books / categories in a cart: only those books'
     *   server-side prices.
     * - specific books / categories on a combo: the combo price
     *   (validation already requires the coupon to cover every book
     *   in the combo).
     *
     * @param  Collection<int, \App\Models\Book>  $books
     */
    public function eligibleSubtotal(Coupon $coupon, User $user, Collection $books, float $subtotal, ?Combo $combo = null): float
    {
        $subtotal = round($subtotal, 2);

        if ($coupon->applies_to === Coupon::APPLIES_ALL || $combo) {
            return $subtotal;
        }

        $eligible = 0.0;

        foreach ($books as $book) {
            if ($coupon->appliesToBook($book)) {
                $eligible += $this->prices->getPrice($book, $user)['amount'];
            }
        }

        return round(min($eligible, $subtotal), 2);
    }

    /**
     * Discount amount for a coupon against an eligible subtotal. Never
     * exceeds the eligible subtotal itself.
     */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $subtotal = round($subtotal, 2);

        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = $coupon->type === Coupon::TYPE_PERCENT
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Record one coupon usage. MUST be called inside the order's DB
     * transaction: the coupon row is locked and every limit is
     * rechecked, so a coupon that hits its limit between validation
     * and order creation fails here instead of double-spending.
     *
     * @throws CouponException when the coupon can no longer be used.
     */
    public function consume(Order $order, Coupon $coupon, User $user): void
    {
        /** @var Coupon $locked */
        $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();

        if ($locked->max_uses !== null && $locked->uses_count >= $locked->max_uses) {
            throw new CouponException('This coupon has just reached its usage limit. Please remove it and try again.');
        }

        $userUses = CouponUsage::query()
            ->where('coupon_id', $locked->id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->count();

        if ($userUses >= $locked->max_uses_per_user) {
            throw new CouponException('You have already used this coupon.');
        }

        $locked->increment('uses_count');

        CouponUsage::create([
            'coupon_id' => $locked->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }
}
