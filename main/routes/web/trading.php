<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trading Routes
|--------------------------------------------------------------------------
|
| All trading-related routes including unified trading pages, trading
| management addon routes, signals, plans, legacy trade, and terminal.
|
| This file now includes modular route files for better organization.
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])->group(function () {
    Route::middleware('check_onboarding')->group(function () {
        Route::get('dashboard', [UserController::class, 'dashboard'])->name('dashboard');

        // Unified Trading Pages
        require __DIR__ . '/trading-unified.php';

        // Help Center
        Route::prefix('help')->name('help.')->group(function () {
            Route::get('/', [\App\Http\Controllers\User\HelpController::class, 'index'])->name('index');
            Route::get('/topic/{topic}', [\App\Http\Controllers\User\HelpController::class, 'topic'])->name('topic');
        });

        // Backward Compatibility Redirects
        require __DIR__ . '/trading-redirects.php';

        // Trading Management Addon Routes
        if (\App\Support\AddonRegistry::active('trading-management-addon')) {
            require __DIR__ . '/trading-management-presets.php';
            require __DIR__ . '/trading-management-copy.php';
            require __DIR__ . '/trading-management-connections.php';
        }

        // Core Trading Routes (Signals, Plans, Legacy Trade, Terminal)
        require __DIR__ . '/trading-core.php';
    });
});
