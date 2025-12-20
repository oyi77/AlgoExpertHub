<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Main web routes file that includes all route groups. Routes are split
| into logical files for better organization and maintainability.
|
*/

// Admin redirect
Route::get('admin', function () {
    return redirect()->route('admin.login');
});

// Health check endpoint for monitoring
Route::get('/health', function () {
    $status = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'checks' => []
    ];

    // Database check
    try {
        \DB::connection()->getPdo();
        $status['checks']['database'] = 'connected';
    } catch (\Exception $e) {
        $status['checks']['database'] = 'disconnected';
        $status['status'] = 'degraded';
    }

    // Queue check
    try {
        $queueSize = \Queue::size();
        $status['checks']['queue'] = [
            'status' => 'ok',
            'pending_jobs' => $queueSize
        ];
    } catch (\Exception $e) {
        $status['checks']['queue'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $status['status'] = 'degraded';
    }

    // Cache check
    try {
        $cacheDriver = config('cache.default');
        \Cache::put('health_check', 'ok', 10);
        $cacheValue = \Cache::get('health_check');
        $status['checks']['cache'] = [
            'status' => $cacheValue === 'ok' ? 'working' : 'error',
            'driver' => $cacheDriver
        ];
        if ($cacheValue !== 'ok') {
            $status['status'] = 'degraded';
        }
    } catch (\Exception $e) {
        $status['checks']['cache'] = [
            'status' => 'error',
            'driver' => config('cache.default', 'unknown'),
            'error' => $e->getMessage()
        ];
        $status['status'] = 'degraded';
    }

    // Octane check (if available)
    if (class_exists(\Laravel\Octane\Octane::class)) {
        $status['checks']['octane'] = 'available';
    } else {
        $status['checks']['octane'] = 'not_installed';
    }

    $httpStatus = $status['status'] === 'ok' ? 200 : 503;
    return response()->json($status, $httpStatus);
})->name('health');

// Load route groups
require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/onboarding.php';
require __DIR__ . '/web/trading.php';
require __DIR__ . '/web/payments.php';
require __DIR__ . '/web/user.php';
require __DIR__ . '/web/public.php';
