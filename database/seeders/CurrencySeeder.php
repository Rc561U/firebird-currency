<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * All currencies returned by freecurrencyapi.com /v1/latest
     * plus USD as the base. USD is always kept active as the base currency.
     *
     * @var array<int, array{code: string, name: string}>
     */
    private array $currencies = [
        ['code' => 'AUD', 'name' => 'Australian Dollar'],
        ['code' => 'BGN', 'name' => 'Bulgarian Lev'],
        ['code' => 'BRL', 'name' => 'Brazilian Real'],
        ['code' => 'CAD', 'name' => 'Canadian Dollar'],
        ['code' => 'CHF', 'name' => 'Swiss Franc'],
        ['code' => 'CNY', 'name' => 'Chinese Yuan'],
        ['code' => 'CZK', 'name' => 'Czech Koruna'],
        ['code' => 'DKK', 'name' => 'Danish Krone'],
        ['code' => 'EUR', 'name' => 'Euro'],
        ['code' => 'GBP', 'name' => 'British Pound'],
        ['code' => 'HKD', 'name' => 'Hong Kong Dollar'],
        ['code' => 'HRK', 'name' => 'Croatian Kuna'],
        ['code' => 'HUF', 'name' => 'Hungarian Forint'],
        ['code' => 'IDR', 'name' => 'Indonesian Rupiah'],
        ['code' => 'ILS', 'name' => 'Israeli New Shekel'],
        ['code' => 'INR', 'name' => 'Indian Rupee'],
        ['code' => 'ISK', 'name' => 'Icelandic Króna'],
        ['code' => 'JPY', 'name' => 'Japanese Yen'],
        ['code' => 'KRW', 'name' => 'South Korean Won'],
        ['code' => 'MXN', 'name' => 'Mexican Peso'],
        ['code' => 'MYR', 'name' => 'Malaysian Ringgit'],
        ['code' => 'NOK', 'name' => 'Norwegian Krone'],
        ['code' => 'NZD', 'name' => 'New Zealand Dollar'],
        ['code' => 'PHP', 'name' => 'Philippine Peso'],
        ['code' => 'PLN', 'name' => 'Polish Złoty'],
        ['code' => 'RON', 'name' => 'Romanian Leu'],
        ['code' => 'RUB', 'name' => 'Russian Ruble'],
        ['code' => 'SEK', 'name' => 'Swedish Krona'],
        ['code' => 'SGD', 'name' => 'Singapore Dollar'],
        ['code' => 'THB', 'name' => 'Thai Baht'],
        ['code' => 'TRY', 'name' => 'Turkish Lira'],
        ['code' => 'USD', 'name' => 'US Dollar'],
        ['code' => 'ZAR', 'name' => 'South African Rand'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                ['name' => $currency['name'], 'is_active' => true],
            );
        }
    }
}
