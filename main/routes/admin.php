<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Main admin routes file that includes modular route groups.
| Routes are split into logical files based on responsibility (SoC).
|
*/

// Load modular admin route files
require __DIR__ . '/admin/auth.php';
require __DIR__ . '/admin/dashboard.php';
require __DIR__ . '/admin/documentation.php';
require __DIR__ . '/admin/admins.php';
require __DIR__ . '/admin/plans.php';
require __DIR__ . '/admin/signals.php';
require __DIR__ . '/admin/users.php';
require __DIR__ . '/admin/financial.php';
require __DIR__ . '/admin/tickets.php';
require __DIR__ . '/admin/roles.php';
require __DIR__ . '/admin/referrals.php';
require __DIR__ . '/admin/system.php';
require __DIR__ . '/admin/logs.php';
require __DIR__ . '/admin/monitoring.php';
require __DIR__ . '/admin/addons.php';
require __DIR__ . '/admin/profile.php';
require __DIR__ . '/admin/notifications.php';
require __DIR__ . '/admin/subscribers.php';

// Note: Addon routes (trading-management, multi-channel, ai-connections, etc.)
// are loaded by their respective AddonServiceProvider classes, not here.


// SPA Catch-all Route (Must be last)
Route::get('/admin/app/{any?}', function () {
    return view('backend.spa_layout');
})->where('any', '.*')->name('admin.spa');
