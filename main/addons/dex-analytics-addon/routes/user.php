<?php

declare(strict_types=1);

use Addons\DexAnalyticsAddon\App\Http\Controllers\User\AiInsightsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\User\AnalyticsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\User\DexAnalyticsController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\User\LeaderboardController;
use Addons\DexAnalyticsAddon\App\Http\Controllers\User\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DexAnalyticsController::class, 'dashboard'])->name('dashboard');

Route::prefix('watchlist')->name('watchlist.')->group(function (): void {
    Route::get('/', [WatchlistController::class, 'index'])->name('index');
});

Route::prefix('analytics')->name('analytics.')->group(function (): void {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/trader/{wallet}', [AnalyticsController::class, 'trader'])->name('trader');
});

Route::prefix('leaderboards')->name('leaderboards.')->group(function (): void {
    Route::get('/', [LeaderboardController::class, 'index'])->name('index');
});

Route::prefix('ai-insights')->name('ai-insights.')->group(function (): void {
    Route::get('/', [AiInsightsController::class, 'index'])->name('index');
    Route::post('/analyze', [AiInsightsController::class, 'analyze'])->name('analyze');
});
