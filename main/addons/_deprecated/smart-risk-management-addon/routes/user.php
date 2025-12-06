<?php

use Addons\SmartRiskManagement\App\Http\Controllers\User\SrmDashboardController;
use Addons\SmartRiskManagement\App\Http\Controllers\User\SrmAdjustmentController;
use Addons\SmartRiskManagement\App\Http\Controllers\User\SrmInsightController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Smart Risk Management User Routes
|--------------------------------------------------------------------------
*/

Route::prefix('srm')->name('srm.')->group(function () {
    // My SRM Dashboard
    Route::get('/', [SrmDashboardController::class, 'index'])->name('dashboard');
    
    // SRM Adjustments History
    Route::get('adjustments', [SrmAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('adjustments/{id}', [SrmAdjustmentController::class, 'show'])->name('adjustments.show');
    
    // Performance Insights
    Route::get('insights', [SrmInsightController::class, 'index'])->name('insights.index');
});

