<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch fresh exchange rates every day at midnight UTC.
// Ensure FREECURRENCYAPI_KEY is set in .env before this runs.
Schedule::command('app:fetch-currency-rates')->daily();
