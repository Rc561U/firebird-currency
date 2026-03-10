<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Admin routes — protected behind authentication
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('currencies', [\App\Http\Controllers\Admin\CurrencyController::class, 'index'])
            ->name('currencies.index');

        Route::post('currencies/refresh', [\App\Http\Controllers\Admin\CurrencyController::class, 'refresh'])
            ->name('currencies.refresh');
    });

require __DIR__.'/settings.php';
