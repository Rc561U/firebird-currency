<?php

namespace Tests\Feature\Commands;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchCurrencyRatesCommandTest extends TestCase
{
    use RefreshDatabase;

    // ─── Happy path ───────────────────────────────────────────────────────────

    public function test_command_fetches_and_stores_rates_successfully(): void
    {
        config(['services.freecurrencyapi.key' => 'test-key']);

        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

        Http::fake([
            'api.freecurrencyapi.com/*' => Http::response([
                'data' => [
                    'USD' => 1.0,
                    'EUR' => 0.92,
                ],
            ], 200),
        ]);

        $this->artisan('app:fetch-currency-rates')
            ->assertSuccessful();

        $this->assertDatabaseCount('currency_rates', 2);
        $this->assertDatabaseHas('currency_rates', [
            'rate_to_base' => '0.92000000',
        ]);
    }

    // ─── Missing API key ──────────────────────────────────────────────────────

    public function test_command_fails_when_api_key_is_missing(): void
    {
        config(['services.freecurrencyapi.key' => null]);

        $this->artisan('app:fetch-currency-rates')
            ->assertFailed();

        $this->assertDatabaseCount('currency_rates', 0);
    }

    // ─── API error ────────────────────────────────────────────────────────────

    public function test_command_fails_and_logs_when_api_returns_error(): void
    {
        config(['services.freecurrencyapi.key' => 'test-key']);

        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

        Http::fake([
            'api.freecurrencyapi.com/*' => Http::response([], 403),
        ]);

        Log::shouldReceive('error')->once();

        $this->artisan('app:fetch-currency-rates')
            ->assertFailed();

        $this->assertDatabaseCount('currency_rates', 0);
    }

    // ─── Empty data payload ───────────────────────────────────────────────────

    public function test_command_fails_when_api_returns_empty_data(): void
    {
        config(['services.freecurrencyapi.key' => 'test-key']);

        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

        Http::fake([
            'api.freecurrencyapi.com/*' => Http::response(['data' => []], 200),
        ]);

        Log::shouldReceive('warning')->once();

        $this->artisan('app:fetch-currency-rates')
            ->assertFailed();
    }

    // ─── No active currencies ─────────────────────────────────────────────────

    public function test_command_warns_when_no_active_currencies_exist(): void
    {
        config(['services.freecurrencyapi.key' => 'test-key']);

        $this->artisan('app:fetch-currency-rates')
            ->assertSuccessful();

        Http::assertNothingSent();
    }
}
