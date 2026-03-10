<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Converts monetary amounts between currencies using stored exchange rates.
 *
 * Architecture note: Rates are cached for 1 hour to avoid redundant DB queries
 * on repeated conversions. The cache is keyed per-currency so individual
 * currencies can be invalidated without flushing the whole set.
 *
 * BCMath is used for all arithmetic to avoid floating-point precision loss —
 * critical when dealing with high-value financial conversions (e.g. JPY ↔ BTC).
 */
class CurrencyConverterService
{
    /** BCMath scale — 8 decimal places, matching rate_to_base precision */
    private const SCALE = 8;

    /**
     * Convert an amount from one currency to another.
     *
     * The formula converts via USD as the intermediary base:
     *   amount_in_from_currency → USD → amount_in_to_currency
     *
     * i.e.  result = (amount / from_rate) * to_rate
     *
     * @throws RuntimeException When a currency is not found or has no rate.
     */
    public function convert(float|int $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return (float) $amount;
        }

        $fromRate = $this->getRate($fromCode);
        $toRate   = $this->getRate($toCode);

        // Perform division first, then multiplication to retain precision.
        // bcscale ensures we don't lose digits during intermediate steps.
        $amountStr = number_format($amount, self::SCALE, '.', '');
        $inBase    = bcdiv($amountStr, $fromRate, self::SCALE);
        $result    = bcmul($inBase, $toRate, self::SCALE);

        return (float) $result;
    }

    /**
     * Retrieve the latest rate for a given currency code.
     * Results are cached for 1 hour.
     *
     * @throws RuntimeException When the currency or its rate cannot be found.
     */
    private function getRate(string $code): string
    {
        return Cache::remember(
            "currency_rate_{$code}",
            now()->addHour(),
            function () use ($code): string {
                $currency = Currency::where('code', $code)
                    ->with('latestRate')
                    ->first();

                if ($currency === null) {
                    throw new RuntimeException("Currency [{$code}] not found.");
                }

                if ($currency->latestRate === null) {
                    throw new RuntimeException("No rate found for currency [{$code}].");
                }

                return $currency->latestRate->rate_to_base;
            }
        );
    }
}

