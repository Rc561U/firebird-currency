<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches latest currency rates from freecurrencyapi.com and stores them.
 *
 * Architecture note: This command is deliberately kept thin — it delegates
 * HTTP concerns to Laravel's Http facade and persistence to Eloquent.
 * No SDK dependency is introduced (per spec); raw HTTP keeps the integration
 * transparent and testable via Http::fake().
 */
class FetchCurrencyRatesCommand extends Command
{
    protected $signature = 'app:fetch-currency-rates';

    protected $description = 'Fetch the latest currency exchange rates from freecurrencyapi.com';

    public function handle(): int
    {
        $apiKey = config('services.freecurrencyapi.key');
        $baseUrl = config('services.freecurrencyapi.base_url');

        if (empty($apiKey)) {
            $this->error('FREECURRENCYAPI_KEY is not configured. Set it in your .env file.');
            Log::error('FetchCurrencyRates: API key is missing.');

            return self::FAILURE;
        }

        // Only fetch rates for active currencies registered in our database
        $currencies = Currency::where('is_active', true)->pluck('code')->all();

        if (empty($currencies)) {
            $this->warn('No active currencies found in the database. Run the seeder first.');

            return self::SUCCESS;
        }

        $this->info('Fetching rates for: '.implode(', ', $currencies));

        try {
            $response = Http::timeout(15)
                ->withQueryParameters([
                    'apikey'      => $apiKey,
                    'base_currency' => 'USD', // USD is our base currency
                    'currencies'  => implode(',', $currencies),
                ])
                ->get("{$baseUrl}/latest");

            if ($response->failed()) {
                $this->error("API request failed with status: {$response->status()}");
                Log::error('FetchCurrencyRates: API request failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return self::FAILURE;
            }

            /** @var array<string, float> $data */
            $data = $response->json('data', []);

            if (empty($data)) {
                $this->error('API returned empty data.');
                Log::warning('FetchCurrencyRates: Empty data payload returned from API.', [
                    'body' => $response->body(),
                ]);

                return self::FAILURE;
            }

            $this->persistRates($data);

            $this->info('Currency rates updated successfully ('.count($data).' rates).');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('An error occurred: '.$e->getMessage());
            Log::error('FetchCurrencyRates: Unexpected error.', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Persist the fetched rates by inserting a new snapshot row per currency.
     * We insert new rows (not upsert) to retain historical data for audit trails.
     *
     * @param array<string, float> $rates
     */
    private function persistRates(array $rates): void
    {
        $fetchedAt = now();

        // Index currencies by code for O(1) lookup instead of N+1 queries
        $currencies = Currency::where('is_active', true)
            ->whereIn('code', array_keys($rates))
            ->get()
            ->keyBy('code');

        foreach ($rates as $code => $rate) {
            $currency = $currencies->get($code);

            if ($currency === null) {
                continue;
            }

            CurrencyRate::create([
                'currency_id'  => $currency->id,
                'rate_to_base' => (string) $rate,
                'fetched_at'   => $fetchedAt,
            ]);
        }
    }
}

