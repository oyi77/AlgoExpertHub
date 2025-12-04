<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trading Management - User Routes
|--------------------------------------------------------------------------
|
| All trading management routes for user panel
| 
| Structure: Same as admin but scoped to user's own data
|
*/

// Dashboard (overview)
Route::get('/', function () {
    return view('trading-management::user.dashboard');
})->name('dashboard');

// Placeholder routes (same structure as admin)
Route::get('/config', function () {
    return '<h1>My Trading Configuration</h1><p>Manage my data connections and risk settings</p>';
})->name('config.index');

Route::get('/operations', function () {
    return '<h1>Auto Trading</h1><p>My trading operations and positions</p>';
})->name('operations.index');

Route::get('/strategy', function () {
    return '<h1>My Strategies</h1><p>My filter strategies and AI models</p>';
})->name('strategy.index');

Route::get('/copy-trading', function () {
    return '<h1>Copy Trading</h1><p>Browse traders and manage my subscriptions</p>';
})->name('copy-trading.index');

Route::get('/test', function () {
    return '<h1>Backtesting</h1><p>Test my strategies on historical data</p>';
})->name('test.index');

// Trading Bots (Coinrule-like bot builder)
Route::prefix('trading-bots')->name('trading-bots.')->group(function () {
    Route::get('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'index'])->name('index');
    Route::get('/marketplace', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'marketplace'])->name('marketplace');
    Route::get('/clone/{template}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'clone'])->name('clone');
    Route::post('/clone/{template}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'storeClone'])->name('clone.store');
    Route::get('/create', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'create'])->name('create');
    Route::post('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'store'])->name('store');
    Route::get('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'update'])->name('update');
    Route::delete('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-active', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'toggleActive'])->name('toggle-active');
});

// Full routes will be implemented in respective phases

