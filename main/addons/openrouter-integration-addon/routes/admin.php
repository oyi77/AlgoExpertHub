<?php

use Addons\OpenRouterIntegration\App\Http\Controllers\Backend\OpenRouterConfigController;
use Addons\OpenRouterIntegration\App\Http\Controllers\Backend\OpenRouterModelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OpenRouter Integration Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'admin', 'demo'])
    ->prefix('admin/openrouter')
    ->name('admin.openrouter.')
    ->group(function () {
        
        // Configuration Management
        Route::resource('configurations', OpenRouterConfigController::class);
        Route::post('configurations/{id}/test', [OpenRouterConfigController::class, 'testConnection'])
            ->name('configurations.test');
        Route::post('configurations/{id}/toggle', [OpenRouterConfigController::class, 'toggleStatus'])
            ->name('configurations.toggle');

        // Model Management
        Route::get('models', [OpenRouterModelController::class, 'index'])
            ->name('models.index');
        Route::post('models/sync', [OpenRouterModelController::class, 'sync'])
            ->name('models.sync');
        Route::get('models/{id}', [OpenRouterModelController::class, 'show'])
            ->name('models.show');
    });

