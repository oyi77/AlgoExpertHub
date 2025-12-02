<?php

use Illuminate\Support\Facades\Route;
use Addons\AiTradingAddon\App\Http\Controllers\Backend\AiModelProfileController;
use Addons\AiTradingAddon\App\Http\Controllers\Backend\AiDecisionLogController;

Route::middleware(['permission:signal,admin'])->group(function () {
    Route::resource('ai-model-profiles', AiModelProfileController::class);
    
    // AI & Filter Decision Logs (Observability)
    Route::prefix('ai-decision-logs')->name('ai-decision-logs.')->group(function () {
        Route::get('/', [AiDecisionLogController::class, 'index'])->name('index');
        Route::get('/{id}', [AiDecisionLogController::class, 'show'])->name('show');
    });
});
