<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

/**
 * Seeds real rates sourced from freecurrencyapi.com on 2026-03-11.
 * These are used for local development and testing. Production rates
 * are kept fresh via the daily `app:fetch-currency-rates` scheduler.
 *
 * Source: https://api.freecurrencyapi.com/v1/latest?apikey=...  (2026-03-11)
 */
class CurrencyRateSeeder extends Seeder
{
    /**
     * Rates relative to USD (base currency = 1.00000000).
     *
     * @var array<string, string>
     */
    private array $rates = [
        'AUD' => '1.41480020',
        'BGN' => '1.66500028',
        'BRL' => '5.20669081',
        'CAD' => '1.35880027',
        'CHF' => '0.77798010',
        'CNY' => '6.91035120',
        'CZK' => '20.97020364',
        'DKK' => '6.43167088',
        'EUR' => '0.86085917',
        'GBP' => '0.74496509',
        'HKD' => '7.82100128',
        'HRK' => '6.48559586',
        'HUF' => '333.50404999',
        'IDR' => '16854.80188717',
        'ILS' => '3.09550056',
        'INR' => '91.75001497',
        'ISK' => '124.91001937',
        'JPY' => '157.86502701',
        'KRW' => '1470.59821210',
        'MXN' => '17.69700199',
        'MYR' => '3.95140048',
        'NOK' => '9.59834168',
        'NZD' => '1.68740026',
        'PHP' => '59.25200826',
        'PLN' => '3.65721059',
        'RON' => '4.38776064',
        'RUB' => '78.24611047',
        'SEK' => '9.14634124',
        'SGD' => '1.27520019',
        'THB' => '31.74700516',
        'TRY' => '44.09240461',
        'USD' => '1.00000000',
        'ZAR' => '16.33500214',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fetchedAt = now();

        foreach ($this->rates as $code => $rate) {
            $currency = Currency::where('code', $code)->first();

            if ($currency === null) {
                continue;
            }

            CurrencyRate::create([
                'currency_id'  => $currency->id,
                'rate_to_base' => $rate,
                'fetched_at'   => $fetchedAt,
            ]);
        }
    }
}
