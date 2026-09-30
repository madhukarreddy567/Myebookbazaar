<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;

/**
 * Phase 2B: the ONE place where price, currency and sale logic lives.
 *
 * - Indian visitors (pricing region IN) pay INR; everyone else pays USD.
 * - The region comes from the customer's saved pricing_region, or from
 *   the Settings Center region list / default for guests.
 * - Sale prices apply only inside their date window AND only when the
 *   "Sales and offers" switch is on.
 * - If a currency is disabled in Settings, visitors fall back to the
 *   other currency — a price is always shown, never a dead end.
 * - Server-side only. Controllers and views never do money math.
 */
class PriceService
{
    public function __construct(protected SettingsService $settings) {}

    /**
     * Pricing region for a visitor: logged-in customers use their saved
     * region; guests use the configured default region.
     */
    public function regionForVisitor(?User $user): string
    {
        if ($user && in_array($user->pricing_region, ['IN', 'INTL'], true)) {
            return $user->pricing_region;
        }

        $default = (string) $this->settings->get('pricing.default_region', 'IN');

        return $default === 'INTL' ? 'INTL' : 'IN';
    }

    /**
     * Full price quote for a book and visitor.
     *
     * @return array{currency: 'INR'|'USD', symbol: string, amount: float, regular_amount: float|null, on_sale: bool, discount_percent: int|null}
     */
    public function getPrice(Book $book, ?User $user = null, ?string $countryCode = null): array
    {
        $region = $countryCode
            ? $this->settings->regionForCountry($countryCode)
            : $this->regionForVisitor($user);

        $currency = $region === 'IN' ? 'INR' : 'USD';

        // Currency switches: fall back to the other currency when disabled.
        $inrOn = $this->settings->boolean('pricing.inr_enabled', true);
        $usdOn = $this->settings->boolean('pricing.usd_enabled', true);
        if ($currency === 'INR' && ! $inrOn && $usdOn) {
            $currency = 'USD';
        } elseif ($currency === 'USD' && ! $usdOn && $inrOn) {
            $currency = 'INR';
        }

        $regular = $currency === 'INR' ? (float) $book->price_inr : (float) $book->price_usd;
        $sale = $currency === 'INR' ? $book->sale_price_inr : $book->sale_price_usd;

        $onSale = $this->saleIsActive($book) && $sale !== null && (float) $sale < $regular;

        return [
            'currency' => $currency,
            'symbol' => $currency === 'INR' ? '₹' : '$',
            'amount' => $onSale ? (float) $sale : $regular,
            'regular_amount' => $onSale ? $regular : null,
            'on_sale' => $onSale,
            'discount_percent' => $this->discountPercent($onSale ? (float) $sale : null, $regular),
        ];
    }

    /**
     * "₹149" / "$4.99" — symbol first, tabular numbers.
     */
    public function format(float $amount, string $currency): string
    {
        $symbol = $currency === 'INR' ? '₹' : '$';
        $decimals = $currency === 'INR' ? 0 : 2;

        // INR: whole rupees shown without decimals (₹149).
        if ($currency === 'INR' && fmod($amount, 1.0) !== 0.0) {
            $decimals = 2;
        }

        return $symbol.number_format($amount, $decimals, '.', ',');
    }

    /**
     * Convenience: formatted current price for a book and visitor.
     */
    public function displayPrice(Book $book, ?User $user = null, ?string $countryCode = null): string
    {
        $quote = $this->getPrice($book, $user, $countryCode);

        return $this->format($quote['amount'], $quote['currency']);
    }

    /**
     * A sale is active when the feature switch is on, a sale price exists,
     * and "now" falls inside the optional start/end window.
     */
    public function saleIsActive(Book $book): bool
    {
        if (! $this->settings->enabled('features.sales_offers', true)) {
            return false;
        }

        $now = now();

        if ($book->sale_starts_at && $now->lt($book->sale_starts_at)) {
            return false;
        }

        if ($book->sale_ends_at && $now->gt($book->sale_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Phase 4: a combo's sale window is active under the same rules as
     * book sales.
     */
    public function comboSaleIsActive(\App\Models\Combo $combo): bool
    {
        if (! $this->settings->enabled('features.sales_offers', true)) {
            return false;
        }

        $now = now();

        if ($combo->sale_starts_at && $now->lt($combo->sale_starts_at)) {
            return false;
        }

        if ($combo->sale_ends_at && $now->gt($combo->sale_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Phase 4: full price quote for a combo and visitor. Same region
     * and currency rules as single books.
     *
     * @return array{currency: 'INR'|'USD', symbol: string, amount: float, regular_amount: float|null, on_sale: bool, discount_percent: int|null}
     */
    public function getComboPrice(\App\Models\Combo $combo, ?User $user = null): array
    {
        $region = $this->regionForVisitor($user);
        $currency = $region === 'IN' ? 'INR' : 'USD';

        $inrOn = $this->settings->boolean('pricing.inr_enabled', true);
        $usdOn = $this->settings->boolean('pricing.usd_enabled', true);
        if ($currency === 'INR' && ! $inrOn && $usdOn) {
            $currency = 'USD';
        } elseif ($currency === 'USD' && ! $usdOn && $inrOn) {
            $currency = 'INR';
        }

        $regular = $currency === 'INR' ? (float) $combo->combo_price_inr : (float) $combo->combo_price_usd;
        $sale = $currency === 'INR' ? $combo->sale_price_inr : $combo->sale_price_usd;

        $onSale = $this->comboSaleIsActive($combo) && $sale !== null && (float) $sale < $regular;

        return [
            'currency' => $currency,
            'symbol' => $currency === 'INR' ? '₹' : '$',
            'amount' => $onSale ? (float) $sale : $regular,
            'regular_amount' => $onSale ? $regular : null,
            'on_sale' => $onSale,
            'discount_percent' => $this->discountPercent($onSale ? (float) $sale : null, $regular),
        ];
    }

    /**
     * Whole-number discount for the "Save 34%" / "34% off" chips.
     * Null when there is no live sale — views must never render an
     * empty "% off" chip.
     */
    protected function discountPercent(?float $sale, float $regular): ?int
    {
        if ($sale === null || $regular <= 0 || $sale >= $regular) {
            return null;
        }

        return (int) round((1 - $sale / $regular) * 100);
    }

    /**
     * Cart/order total for a set of books — always a single currency,
     * because every visitor has exactly one region.
     *
     * @param  iterable<Book>  $books
     * @return array{currency: 'INR'|'USD', symbol: string, total: float, lines: array}
     */
    public function total(iterable $books, ?User $user = null): array
    {
        $lines = [];
        $currency = null;
        $total = 0.0;

        foreach ($books as $book) {
            $quote = $this->getPrice($book, $user);
            $currency ??= $quote['currency'];
            $total += $quote['amount'];
            $lines[] = [
                'book_id' => $book->id,
                'title' => $book->title,
                'currency' => $quote['currency'],
                'amount' => $quote['amount'],
                'display' => $this->format($quote['amount'], $quote['currency']),
            ];
        }

        $currency ??= $this->regionForVisitor($user) === 'IN' ? 'INR' : 'USD';

        return [
            'currency' => $currency,
            'symbol' => $currency === 'INR' ? '₹' : '$',
            'total' => $total,
            'display_total' => $this->format($total, $currency),
            'lines' => $lines,
        ];
    }
}
