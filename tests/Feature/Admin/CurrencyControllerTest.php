<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/admin/currencies');

        $response->assertRedirectToRoute('login');
    }

    // ─── Index page ──────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_currency_index(): void
    {
        $user = User::factory()->create();

        // withoutVite() prevents the Vite manifest lookup so the Blade view
        // renders successfully in tests without a production build.
        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('/admin/currencies');

        $response->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('admin/currency/index')
            );
    }

    public function test_index_passes_currencies_with_rates_to_view(): void
    {
        $user = User::factory()->create();

        $usd = Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'name' => 'Euro']);

        CurrencyRate::factory()->create([
            'currency_id'  => $usd->id,
            'rate_to_base' => '1.00000000',
            'fetched_at'   => now(),
        ]);
        CurrencyRate::factory()->create([
            'currency_id'  => $eur->id,
            'rate_to_base' => '0.92000000',
            'fetched_at'   => now(),
        ]);

        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('/admin/currencies');

        $response->assertInertia(
            fn ($page) => $page
                ->component('admin/currency/index')
                ->has('currencies', 2)
                ->where('currencies.0.code', 'EUR') // ordered by code
                ->where('currencies.1.code', 'USD')
        );
    }

    public function test_index_only_shows_active_currencies(): void
    {
        $user = User::factory()->create();

        Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        Currency::factory()->create(['code' => 'XXX', 'is_active' => false]);

        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('/admin/currencies');

        $response->assertInertia(
            fn ($page) => $page
                ->component('admin/currency/index')
                ->has('currencies', 1)
                ->where('currencies.0.code', 'USD')
        );
    }

    // ─── Refresh action ───────────────────────────────────────────────────────

    public function test_refresh_redirects_with_success_flash_on_success(): void
    {
        $user = User::factory()->create();

        // Mock the Artisan facade to return success without hitting the real API
        Artisan::shouldReceive('call')
            ->once()
            ->with('app:fetch-currency-rates')
            ->andReturn(0);

        $response = $this->actingAs($user)
            ->post('/admin/currencies/refresh');

        $response->assertRedirectToRoute('admin.currencies.index')
            ->assertSessionHas('success');
    }

    public function test_refresh_redirects_with_error_flash_on_command_failure(): void
    {
        $user = User::factory()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('app:fetch-currency-rates')
            ->andReturn(1);

        $response = $this->actingAs($user)
            ->post('/admin/currencies/refresh');

        $response->assertRedirectToRoute('admin.currencies.index')
            ->assertSessionHas('error');
    }

    public function test_refresh_requires_authentication(): void
    {
        $response = $this->post('/admin/currencies/refresh');

        $response->assertRedirectToRoute('login');
    }

    // ─── Null rate test ───────────────────────────────────────────────────────

    public function test_index_shows_null_rate_when_no_rate_fetched_yet(): void
    {
        $user = User::factory()->create();

        Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);

        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('/admin/currencies');

        $response->assertInertia(
            fn ($page) => $page
                ->component('admin/currency/index')
                ->where('currencies.0.rate', null)
        );
    }
}
