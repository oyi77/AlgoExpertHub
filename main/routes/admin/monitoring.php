<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\QueueManagementController;
use App\Http\Controllers\Backend\CacheManagementController;
use App\Http\Controllers\Backend\SystemMonitoringController;

/*
|--------------------------------------------------------------------------
| Admin System Monitoring & Management Routes
|--------------------------------------------------------------------------
|
| Queue and cache management routes. Requires manage-system permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-system,admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // System Monitoring Dashboard
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [SystemMonitoringController::class, 'index'])->name('index');
        Route::get('/health', [SystemMonitoringController::class, 'health'])->name('health');
        Route::get('/workers', [SystemMonitoringController::class, 'workers'])->name('workers');
        Route::get('/alerts', [SystemMonitoringController::class, 'alerts'])->name('alerts');
        Route::get('/history', [SystemMonitoringController::class, 'history'])->name('history');
        Route::post('/workers/restart/{type}', [SystemMonitoringController::class, 'restartWorkers'])->name('workers.restart');
        Route::post('/cache/clear', [SystemMonitoringController::class, 'clearCache'])->name('cache.clear');
    });

    // Queue Management
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/', [QueueManagementController::class, 'index'])->name('index');
        Route::get('/health', [QueueManagementController::class, 'health'])->name('health');
        Route::get('/metrics', [QueueManagementController::class, 'metrics'])->name('metrics');
        Route::get('/statistics', [QueueManagementController::class, 'statistics'])->name('statistics');
        Route::post('/scale', [QueueManagementController::class, 'scale'])->name('scale');
        Route::post('/restart', [QueueManagementController::class, 'restart'])->name('restart');
        Route::post('/clear-metrics', [QueueManagementController::class, 'clearMetrics'])->name('clear-metrics');
    });
    
    // Cache Management
    Route::prefix('cache')->name('cache.')->group(function () {
        Route::get('/', [CacheManagementController::class, 'index'])->name('index');
        Route::post('/warm', [CacheManagementController::class, 'warm'])->name('warm');
        Route::post('/clear-tags', [CacheManagementController::class, 'clearByTags'])->name('clear-tags');
        Route::post('/clear-all', [CacheManagementController::class, 'clearAll'])->name('clear-all');
        Route::get('/stats', [CacheManagementController::class, 'stats'])->name('stats');
        Route::get('/query-stats', [CacheManagementController::class, 'queryStats'])->name('query-stats');
    });
});
