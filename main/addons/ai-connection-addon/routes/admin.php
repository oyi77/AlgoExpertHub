<?php

use Illuminate\Support\Facades\Route;
use Addons\AiConnectionAddon\App\Http\Controllers\Backend\ProviderController;
use Addons\AiConnectionAddon\App\Http\Controllers\Backend\ConnectionController;
use Addons\AiConnectionAddon\App\Http\Controllers\Backend\UsageAnalyticsController;

/*
|--------------------------------------------------------------------------
| AI Connection Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for managing AI providers, connections, and viewing analytics
|
*/

// Providers management
Route::prefix('providers')->name('providers.')->group(function () {
    Route::get('/', [ProviderController::class, 'index'])->name('index');
    Route::get('/create', [ProviderController::class, 'create'])->name('create');
    Route::post('/', [ProviderController::class, 'store'])->name('store');
    Route::get('/{provider}/edit', [ProviderController::class, 'edit'])->name('edit');
    Route::put('/{provider}', [ProviderController::class, 'update'])->name('update');
    Route::delete('/{provider}', [ProviderController::class, 'destroy'])->name('destroy');
});

// Connections management
Route::prefix('connections')->name('connections.')->group(function () {
    Route::get('/', [ConnectionController::class, 'index'])->name('index');
    Route::get('/create', [ConnectionController::class, 'create'])->name('create');
    Route::post('/', [ConnectionController::class, 'store'])->name('store');
    Route::get('/{connection}/edit', [ConnectionController::class, 'edit'])->name('edit');
    Route::put('/{connection}', [ConnectionController::class, 'update'])->name('update');
    Route::delete('/{connection}', [ConnectionController::class, 'destroy'])->name('destroy');
    Route::post('/{connection}/test', [ConnectionController::class, 'test'])->name('test');
    Route::post('/{connection}/toggle-status', [ConnectionController::class, 'toggleStatus'])->name('toggle-status');
});

// Usage analytics
Route::prefix('usage-analytics')->name('usage-analytics.')->group(function () {
    Route::get('/', [UsageAnalyticsController::class, 'index'])->name('index');
    Route::get('/connection/{connection}', [UsageAnalyticsController::class, 'connection'])->name('connection');
    Route::get('/export', [UsageAnalyticsController::class, 'export'])->name('export');
});

