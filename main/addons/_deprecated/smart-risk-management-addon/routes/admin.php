<?php

use Addons\SmartRiskManagement\App\Http\Controllers\Backend\SignalProviderMetricsController;
use Addons\SmartRiskManagement\App\Http\Controllers\Backend\PredictionController;
use Addons\SmartRiskManagement\App\Http\Controllers\Backend\ModelController;
use Addons\SmartRiskManagement\App\Http\Controllers\Backend\AbTestController;
use Addons\SmartRiskManagement\App\Http\Controllers\Backend\SrmSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Smart Risk Management Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('srm')->name('srm.')->group(function () {
    // Signal Provider Metrics
    Route::get('signal-providers', [SignalProviderMetricsController::class, 'index'])->name('signal-providers.index');
    Route::get('signal-providers/{id}', [SignalProviderMetricsController::class, 'show'])->name('signal-providers.show');
    
    // Predictions
    Route::get('predictions', [PredictionController::class, 'index'])->name('predictions.index');
    Route::get('predictions/{id}', [PredictionController::class, 'show'])->name('predictions.show');
    
    // Model Management
    Route::get('models', [ModelController::class, 'index'])->name('models.index');
    Route::get('models/{id}', [ModelController::class, 'show'])->name('models.show');
    Route::post('models/{id}/retrain', [ModelController::class, 'retrain'])->name('models.retrain');
    Route::post('models/{id}/deploy', [ModelController::class, 'deploy'])->name('models.deploy');
    
    // A/B Testing
    Route::resource('ab-tests', AbTestController::class);
    Route::post('ab-tests/{id}/start', [AbTestController::class, 'start'])->name('ab-tests.start');
    Route::post('ab-tests/{id}/stop', [AbTestController::class, 'stop'])->name('ab-tests.stop');
    Route::get('ab-tests/{id}/results', [AbTestController::class, 'results'])->name('ab-tests.results');
    
    // Settings
    Route::get('settings', [SrmSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SrmSettingsController::class, 'update'])->name('settings.update');
});

