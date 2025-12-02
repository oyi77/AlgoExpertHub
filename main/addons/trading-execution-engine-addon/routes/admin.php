<?php

use Addons\TradingExecutionEngine\App\Http\Controllers\Backend\AnalyticsController;
use Addons\TradingExecutionEngine\App\Http\Controllers\Backend\ConnectionController;
use Addons\TradingExecutionEngine\App\Http\Controllers\Backend\ExecutionController;
use Addons\TradingExecutionEngine\App\Http\Controllers\Backend\PositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('execution')->name('execution-')->group(function () {
    // Connections
    Route::resource('connections', ConnectionController::class);
    Route::post('connections/test', [ConnectionController::class, 'testConnection'])->name('connections.test');
    Route::post('connections/{id}/test', [ConnectionController::class, 'test'])->name('connections.test.id');
    Route::post('connections/{id}/activate', [ConnectionController::class, 'activate'])->name('connections.activate');
    Route::post('connections/{id}/deactivate', [ConnectionController::class, 'deactivate'])->name('connections.deactivate');

    // Executions
    Route::get('executions', [ExecutionController::class, 'index'])->name('executions.index');
    Route::get('executions/{id}', [ExecutionController::class, 'show'])->name('executions.show');

    // Positions
    Route::get('positions', [PositionController::class, 'index'])->name('positions.index');
    Route::get('positions/closed', [PositionController::class, 'closed'])->name('positions.closed');
    Route::post('positions/{id}/close', [PositionController::class, 'close'])->name('positions.close');

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

