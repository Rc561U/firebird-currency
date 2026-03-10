<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Admin panel controller for the currency rates overview.
 *
 * Architecture note: The refresh() method runs the Artisan command
 * synchronously via Artisan::call() and redirects back with a flash message.
 * This keeps the controller thin — all fetch logic stays in the command.
 */
class CurrencyController extends Controller
{
    public function index(): Response
    {
        // Eager-load only the latest rate per currency to prevent N+1 queries
        $currencies = Currency::with('latestRate')
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Currency $currency) => [
                'id'         => $currency->id,
                'code'       => $currency->code,
                'name'       => $currency->name,
                'rate'       => $currency->latestRate?->rate_to_base,
                'fetched_at' => $currency->latestRate?->fetched_at?->toISOString(),
            ]);

        return Inertia::render('admin/currency/index', [
            'currencies' => $currencies,
        ]);
    }

    /**
     * Trigger an on-demand rates refresh by running the Artisan command.
     * Redirects back to index with a flash message indicating success or failure.
     */
    public function refresh(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('app:fetch-currency-rates');

            if ($exitCode !== 0) {
                return redirect()->route('admin.currencies.index')
                    ->with('error', 'Failed to fetch rates. Check that FREECURRENCYAPI_KEY is set and the API is reachable.');
            }

            return redirect()->route('admin.currencies.index')
                ->with('success', 'Exchange rates refreshed successfully.');
        } catch (Throwable $e) {
            Log::error('CurrencyController::refresh failed', ['message' => $e->getMessage()]);

            return redirect()->route('admin.currencies.index')
                ->with('error', 'An unexpected error occurred: '.$e->getMessage());
        }
    }
}
