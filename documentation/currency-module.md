# Currency Conversion Module

## Overview

This module provides currency exchange rate management for the Firebird Currency application. It fetches real-time rates from [freecurrencyapi.com](https://freecurrencyapi.com), stores them with high-precision decimals, exposes an admin panel to review rates, and provides a service for programmatic currency conversion.

---

## Architecture

```
freecurrencyapi.com
        │
        │  HTTP (Laravel Http facade)
        ▼
FetchCurrencyRatesCommand  (scheduled daily)
        │
        │  Eloquent
        ▼
currency_rates table  ◄──── CurrencyRate model
        │
        │  latestRate() HasOne
        ▼
CurrencyConverterService   (BCMath, 1h cache)
        │
        │  Inertia::render()
        ▼
Admin\CurrencyController
        │
        │  Inertia props
        ▼
resources/js/pages/admin/currency/index.tsx
```

**Design decisions:**
- **Single Responsibility** — The Artisan command only fetches & persists. The service only converts. The controller only reads & renders.
- **No SDK** — Raw `Http` facade calls keep the integration transparent and easy to mock in tests.
- **High-precision decimals** — `decimal(16, 8)` in the database + BCMath in PHP avoid floating-point drift.
- **Historical rates** — Each fetch creates a new `currency_rates` row (no upsert), preserving audit history.
- **Cache** — Per-currency 1-hour cache in `CurrencyConverterService` prevents repeated DB hits on batch conversions.

---

## Environment Setup

Add the following to your `.env` file:

```dotenv
FREECURRENCYAPI_KEY=your_api_key_here
```

Get your free API key at: [https://freecurrencyapi.com](https://freecurrencyapi.com)

The key is read via `config/services.php`:

```php
'freecurrencyapi' => [
    'key'      => env('FREECURRENCYAPI_KEY'),
    'base_url' => 'https://api.freecurrencyapi.com/v1',
],
```

---

## Database Schema

### `currencies`

| Column      | Type         | Notes                              |
|-------------|--------------|------------------------------------|
| id          | bigint PK    |                                    |
| code        | varchar(3)   | ISO 4217 code, e.g. `USD`, `EUR`  |
| name        | varchar      | Human-readable name                |
| is_active   | boolean      | Soft-disable without deleting data |
| created_at  | timestamp    |                                    |
| updated_at  | timestamp    |                                    |

### `currency_rates`

| Column        | Type             | Notes                                    |
|---------------|------------------|------------------------------------------|
| id            | bigint PK        |                                          |
| currency_id   | bigint FK        | → `currencies.id` (cascade delete)      |
| rate_to_base  | decimal(16, 8)   | Rate vs USD (base). 8 dp precision.      |
| fetched_at    | timestamp        | When this snapshot was taken             |
| created_at    | timestamp        |                                          |
| updated_at    | timestamp        |                                          |

**Base currency:** USD (`rate_to_base = 1.00000000` for USD).

---

## Default Currencies (Seeder)

Run the seeder to populate the database with default currencies:

```bash
vendor/bin/sail artisan db:seed --class=CurrencySeeder
vendor/bin/sail artisan db:seed --class=CurrencyRateSeeder
```

Default currencies: **USD, EUR, RUB, GBP, JPY**.

The `CurrencyRateSeeder` inserts approximate dev/testing rates only. Production rates come from the API.

---

## Artisan Command

### `app:fetch-currency-rates`

Fetches the latest rates from `freecurrencyapi.com` for all active currencies in the database.

```bash
vendor/bin/sail artisan app:fetch-currency-rates
```

**Behaviour:**
- Fails gracefully if `FREECURRENCYAPI_KEY` is missing.
- Logs errors via `Log::error()` on HTTP failure.
- Inserts a new `currency_rates` row per currency (historical append).
- Only processes active currencies (`is_active = true`).

### Scheduler

The command is registered in `routes/console.php` to run **once daily** at midnight:

```php
Schedule::command('app:fetch-currency-rates')->daily();
```

Ensure the Laravel scheduler is running in your cron:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

With Sail:

```bash
vendor/bin/sail artisan schedule:work  # for local development
```

---

## CurrencyConverterService

**Namespace:** `App\Services\CurrencyConverterService`

### Method

```php
public function convert(float|int $amount, string $fromCode, string $toCode): float
```

**Algorithm:**

```
result = (amount / from_rate_to_usd) * to_rate_to_usd
```

- All arithmetic uses **BCMath** at 8 decimal places.
- Rates are cached per-currency for **1 hour** to reduce DB load.
- Throws `RuntimeException` if the currency code is not found or has no rate.

### Usage Example

```php
use App\Services\CurrencyConverterService;

$converter = app(CurrencyConverterService::class);

// Convert 100 USD to EUR
$eur = $converter->convert(100, 'USD', 'EUR');  // → ~92.0

// Convert 1 EUR to RUB
$rub = $converter->convert(1, 'EUR', 'RUB');    // → ~97.28
```

---

## Admin Panel

### Route

```
GET /admin/currencies   →  admin.currencies.index
```

Requires authentication + email verification.

### Controller

`App\Http\Controllers\Admin\CurrencyController@index`

Passes to the React component:

```typescript
interface CurrencyRow {
    id: number;
    code: string;         // e.g. "EUR"
    name: string;         // e.g. "Euro"
    rate: string | null;  // e.g. "0.92000000", null if never fetched
    fetched_at: string | null; // ISO 8601, null if never fetched
}
```

### React Component

**File:** `resources/js/pages/admin/currency/index.tsx`

Displays a styled table of all active currencies with their latest rates and last-updated timestamps. Shows a friendly empty state with a command hint if no rates have been fetched yet.

### Sidebar Link

The **"Currency Rates"** link appears in the main sidebar (`app-sidebar.tsx`) for all authenticated users, powered by the Wayfinder-generated route `@/routes/admin/currencies`.

---

## Tests

| File | Coverage |
|------|----------|
| `tests/Feature/Admin/CurrencyControllerTest.php` | Auth guard, page render, data shape, active filter, null rate |
| `tests/Feature/Commands/FetchCurrencyRatesCommandTest.php` | Happy path, missing key, API error, empty payload, no currencies |
| `tests/Unit/Services/CurrencyConverterServiceTest.php` | USD→EUR, EUR→RUB, same currency, float amount, missing currency, missing rate |

Run the currency test suite:

```bash
vendor/bin/sail artisan test --compact \
  tests/Feature/Admin/CurrencyControllerTest.php \
  tests/Feature/Commands/FetchCurrencyRatesCommandTest.php \
  tests/Unit/Services/CurrencyConverterServiceTest.php
```

---

## File Map

```
app/
  Console/Commands/
    FetchCurrencyRatesCommand.php     ← Artisan command
  Http/Controllers/Admin/
    CurrencyController.php            ← Admin index controller
  Models/
    Currency.php                      ← Currency model
    CurrencyRate.php                  ← CurrencyRate model
  Services/
    CurrencyConverterService.php      ← Conversion logic

database/
  factories/
    CurrencyFactory.php
    CurrencyRateFactory.php
  migrations/
    *_create_currencies_table.php
    *_create_currency_rates_table.php
  seeders/
    CurrencySeeder.php               ← USD, EUR, RUB, GBP, JPY
    CurrencyRateSeeder.php           ← Dev/test approximate rates

resources/js/
  pages/admin/currency/
    index.tsx                        ← React exchange-rates table
  routes/admin/currencies/
    index.ts                         ← Wayfinder-generated route
  components/
    app-sidebar.tsx                  ← Added "Currency Rates" nav item

routes/
  web.php                            ← /admin/currencies route
  console.php                        ← Scheduler registration

config/
  services.php                       ← freecurrencyapi config block

tests/
  Feature/Admin/CurrencyControllerTest.php
  Feature/Commands/FetchCurrencyRatesCommandTest.php
  Unit/Services/CurrencyConverterServiceTest.php
```

