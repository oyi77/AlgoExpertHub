<?php

declare(strict_types=1);

use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\AiInsightsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\AnalyticsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\DexAnalyticsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\LeaderboardController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\SettingsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\Backend\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-dex-analytics,admin')->group(function (): void {
    Route::get('/', [DexAnalyticsController::class, 'dashboard'])->name('dashboard');

    Route::prefix('watchlist')->name('watchlist.')->group(function (): void {
        Route::get('/', [WatchlistController::class, 'index'])->name('index');
        Route::get('/create', [WatchlistController::class, 'create'])->name('create');
        Route::post('/', [WatchlistController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [WatchlistController::class, 'edit'])->name('edit');
        Route::put('/{id}', [WatchlistController::class, 'update'])->name('update');
        Route::delete('/{id}', [WatchlistController::class, 'destroy'])->name('destroy');
        Route::post('/import', [WatchlistController::class, 'import'])->name('import');
        Route::get('/export', [WatchlistController::class, 'export'])->name('export');
    });

    Route::prefix('analytics')->name('analytics.')->group(function (): void {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/trader/{wallet}', [AnalyticsController::class, 'trader'])->name('trader');
        Route::get('/performance', [AnalyticsController::class, 'performance'])->name('performance');
        Route::get('/pnl', [AnalyticsController::class, 'pnl'])->name('pnl');
        Route::get('/positions', [AnalyticsController::class, 'positions'])->name('positions');
        Route::get('/funding', [AnalyticsController::class, 'funding'])->name('funding');
        Route::get('/liquidations', [AnalyticsController::class, 'liquidations'])->name('liquidations');
    });

    Route::prefix('leaderboards')->name('leaderboards.')->group(function (): void {
        Route::get('/', [LeaderboardController::class, 'index'])->name('index');
        Route::get('/top-traders', [LeaderboardController::class, 'topTraders'])->name('top-traders');
        Route::get('/smart-money', [LeaderboardController::class, 'smartMoney'])->name('smart-money');
        Route::get('/copy-suitable', [LeaderboardController::class, 'copySuitable'])->name('copy-suitable');
    });

    Route::prefix('ai-insights')->name('ai-insights.')->group(function (): void {
        Route::get('/', [AiInsightsController::class, 'index'])->name('index');
        Route::post('/analyze', [AiInsightsController::class, 'analyze'])->name('analyze');
        Route::get('/clustering', [AiInsightsController::class, 'clustering'])->name('clustering');
        Route::get('/crowded-trades', [AiInsightsController::class, 'crowdedTrades'])->name('crowded-trades');
        Route::get('/regime', [AiInsightsController::class, 'regime'])->name('regime');
    });

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/test-platform', [SettingsController::class, 'testPlatform'])->name('test-platform');
    });
});
