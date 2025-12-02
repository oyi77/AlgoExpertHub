<?php

use Addons\TradingExecutionEngine\App\Http\Controllers\User\AnalyticsController;
use Addons\TradingExecutionEngine\App\Http\Controllers\User\ConnectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('execution')->name('execution-')->group(function () {
    // Connections
    Route::resource('connections', ConnectionController::class);
    Route::post('connections/{id}/test', [ConnectionController::class, 'test'])->name('connections.test');
    Route::post('connections/{id}/activate', [ConnectionController::class, 'activate'])->name('connections.activate');
    Route::post('connections/{id}/deactivate', [ConnectionController::class, 'deactivate'])->name('connections.deactivate');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

