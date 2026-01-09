<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\SystemMonitoringController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for admin panel functionality. All routes require admin authentication
| and appropriate permissions.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    
    // System Monitoring Dashboard
    Route::middleware('permission:manage-system,admin')->group(function () {
        Route::get('/monitoring', [SystemMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/health', [SystemMonitoringController::class, 'health'])->name('monitoring.health');
        Route::get('/monitoring/workers', [SystemMonitoringController::class, 'workers'])->name('monitoring.workers');
        Route::get('/monitoring/alerts', [SystemMonitoringController::class, 'alerts'])->name('monitoring.alerts');
        Route::get('/monitoring/history', [SystemMonitoringController::class, 'history'])->name('monitoring.history');
        Route::post('/monitoring/workers/{type}/restart', [SystemMonitoringController::class, 'restartWorkers'])->name('monitoring.workers.restart');
        Route::post('/monitoring/cache/clear', [SystemMonitoringController::class, 'clearCache'])->name('monitoring.cache.clear');
    });
});

