<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trading Management - User Routes
|--------------------------------------------------------------------------
|
| All trading management routes for user panel
| 
| Structure: Same as admin but scoped to user's own data
|
*/

// Dashboard (overview)
Route::get('/', function () {
    return view('trading-management::user.dashboard');
})->name('dashboard');

// Backtesting routes
Route::prefix('backtesting')->name('backtesting.')->group(function () {
    Route::get('/', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'index'])->name('index');
    Route::get('/create', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'create'])->name('create');
    Route::post('/', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'store'])->name('store');
    Route::get('/{id}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'show'])->name('show');
    Route::delete('/{id}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/run', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'run'])->name('run');
    Route::get('/{id}/status', [\Addons\TradingManagement\Modules\Backtesting\Controllers\User\BacktestController::class, 'status'])->name('status');
});

// Marketplace
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    // Bot Templates
    Route::get('bots', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\BotMarketplaceController::class, 'index'])->name('bots.index');
    Route::get('bots/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\BotMarketplaceController::class, 'show'])->name('bots.show');
    Route::post('bots/{id}/clone', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\BotMarketplaceController::class, 'clone'])->name('bots.clone');
    Route::post('bots/{id}/rate', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\BotMarketplaceController::class, 'rate'])->name('bots.rate');
    Route::get('my-clones', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\BotMarketplaceController::class, 'myClones'])->name('my-clones');
    
    // Trader Profiles  
    Route::get('traders', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\TraderMarketplaceController::class, 'index'])->name('traders.index');
    Route::get('traders/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\TraderMarketplaceController::class, 'show'])->name('traders.show');
    Route::post('traders/{id}/follow', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\TraderMarketplaceController::class, 'follow'])->name('traders.follow');
    Route::post('traders/{id}/unfollow', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\TraderMarketplaceController::class, 'unfollow'])->name('traders.unfollow');
    Route::post('traders/{id}/rate', [\Addons\TradingManagement\Modules\Marketplace\Controllers\User\TraderMarketplaceController::class, 'rate'])->name('traders.rate');
});

// Trading Bots (Coinrule-like bot builder)
Route::prefix('trading-bots')->name('trading-bots.')->group(function () {
    Route::get('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'index'])->name('index');
    Route::get('/marketplace', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'marketplace'])->name('marketplace');
    Route::get('/clone/{template}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'clone'])->name('clone');
    Route::post('/clone/{template}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'storeClone'])->name('clone.store');
    
    // Wizard routes (unified bot creation flow)
    Route::prefix('wizard')->name('wizard.')->group(function () {
        Route::get('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'index'])->name('index');
        Route::get('/step/{step}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'step'])->name('step');
        Route::post('/step/{step}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'processStep'])->name('step.process');
        Route::post('/complete', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'complete'])->name('complete');
        Route::get('/back/{step}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'back'])->name('back');
        Route::post('/cancel', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotWizardController::class, 'cancel'])->name('cancel');
    });
    
    Route::get('/create', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'create'])->name('create');
    Route::post('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'store'])->name('store');
    Route::get('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'update'])->name('update');
    Route::delete('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-active', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{id}/start', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'start'])->name('start');
    Route::post('/{id}/stop', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'stop'])->name('stop');
    Route::post('/{id}/pause', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'pause'])->name('pause');
    Route::post('/{id}/resume', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'resume'])->name('resume');
    Route::post('/{id}/restart', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'restart'])->name('restart');
    
    // AJAX endpoints for monitoring
    Route::get('/{id}/worker-status', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'workerStatus'])->name('worker-status');
    Route::get('/{id}/positions', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'positions'])->name('positions');
    Route::get('/{id}/logs', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'logs'])->name('logs');
    Route::get('/{id}/metrics', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'metrics'])->name('metrics');
    
    // Analysis routes
    Route::get('/{id}/analysis', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'analysis'])->name('analysis');
    Route::get('/{id}/executions', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'executions'])->name('executions');
    Route::get('/{id}/monitor', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'monitor'])->name('monitor');
    
    // BotAnalysisController routes
    Route::prefix('analysis')->name('analysis.')->group(function () {
        Route::get('/{id}/metrics', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\BotAnalysisController::class, 'metrics'])->name('metrics');
        Route::get('/{id}/chart', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\BotAnalysisController::class, 'chart'])->name('chart');
        Route::get('/compare', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\BotAnalysisController::class, 'compare'])->name('compare');
        Route::get('/{id}/export', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\BotAnalysisController::class, 'export'])->name('export');
    });
    
    // AJAX endpoint for loading symbols from exchange connection
    Route::get('/exchange-symbols', [\Addons\TradingManagement\Modules\TradingBot\Controllers\User\TradingBotController::class, 'getExchangeSymbols'])->name('exchange-symbols');
});

