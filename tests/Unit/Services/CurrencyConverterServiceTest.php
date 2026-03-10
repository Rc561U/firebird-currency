<?php

namespace Tests\Unit\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Services\CurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CurrencyConverterServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyConverterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyConverterService;
    }

    // ─── Happy paths ──────────────────────────────────────────────────────────

    public function test_converts_usd_to_eur_correctly(): void
    {
        $this->seedRate('USD', '1.00000000');
        $this->seedRate('EUR', '0.92000000');

        $result = $this->service->convert(100, 'USD', 'EUR');

        // 100 / 1 * 0.92 = 92
        $this->assertEqualsWithDelta(92.0, $result, 0.0001);
    }

    public function test_converts_eur_to_rub_correctly(): void
    {
        $this->seedRate('EUR', '0.92000000');
        $this->seedRate('RUB', '89.50000000');

        $result = $this->service->convert(1, 'EUR', 'RUB');

        // 1 / 0.92 * 89.5 ≈ 97.28
        $this->assertEqualsWithDelta(97.28, $result, 0.01);
    }

    public function test_same_currency_returns_same_amount(): void
    {
        $result = $this->service->convert(42.5, 'USD', 'USD');

        $this->assertSame(42.5, $result);
    }

    public function test_converts_float_amount_correctly(): void
    {
        $this->seedRate('USD', '1.00000000');
        $this->seedRate('EUR', '0.92000000');

        $result = $this->service->convert(50.50, 'USD', 'EUR');

        $this->assertEqualsWithDelta(46.46, $result, 0.001);
    }

    // ─── Failure paths ────────────────────────────────────────────────────────

    public function test_throws_exception_when_currency_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Currency [XYZ] not found');

        $this->service->convert(100, 'XYZ', 'USD');
    }

    public function test_throws_exception_when_no_rate_available(): void
    {
        // USD exists with no rate; EUR exists with a rate.
        // Converting from USD will fail because USD has no rate.
        Currency::factory()->create(['code' => 'USD']);
        $this->seedRate('EUR', '0.92000000');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No rate found for currency [USD]');

        $this->service->convert(100, 'USD', 'EUR');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Seed a currency with a specific rate for testing.
     */
    private function seedRate(string $code, string $rate): void
    {
        $currency = Currency::factory()->create(['code' => $code]);
        CurrencyRate::factory()->create([
            'currency_id'  => $currency->id,
            'rate_to_base' => $rate,
            'fetched_at'   => now(),
        ]);
    }
}
