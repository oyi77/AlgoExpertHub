<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    // User Authentication
    Route::post('/register', [\App\Http\Controllers\Api\Auth\RegisterController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\Auth\LoginController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-code', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'verifyCode']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'resetPassword']);
    Route::post('/account/request-deletion', [\App\Http\Controllers\Api\Auth\RegisterController::class, 'requestAccountDeletion'])->middleware('auth:sanctum');

    // Admin Authentication
    Route::prefix('admin')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Api\Admin\Auth\LoginController::class, 'login']);
    });
});

// Public cached endpoints
Route::middleware(['cache.response:600'])->group(function () {
    Route::get('/plans', function () {
        return response()->json(\App\Models\Plan::where('status', 1)->get());
    });

    Route::get('/markets', function () {
        return response()->json(\App\Models\Market::where('status', 1)->get());
    });

    Route::get('/currency-pairs', function () {
        return response()->json(\App\Models\CurrencyPair::where('status', 1)->get());
    });

    Route::get('/time-frames', function () {
        return response()->json(\App\Models\TimeFrame::where('status', 1)->get());
    });

    Route::get('/signals/public', function () {
        return response()->json(
            \App\Models\Signal::published()
                ->withDisplayData()
                ->recent(20)
                ->get()
        );
    });

    // Social Authentication
    Route::get('/social/{provider}/redirect', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'redirect']);
    Route::post('/social/{provider}/callback', [\App\Http\Controllers\Api\Auth\SocialAuthController::class, 'callback']);
});

// Real-time market data (no cache for live updates)
Route::get('/market-data/realtime', function () {
    $service = app(\App\Services\Trading\MarketDataService::class);
    $data = $service->getLandingPageData();

    return response()->json([
        'success' => true,
        'data' => $data,
        'timestamp' => now()->toISOString()
    ]);
});

// Trading Terminal - Public endpoints
Route::get('/trading-terminal/trading-pairs', [\App\Http\Controllers\TradingTerminalController::class, 'getTradingPairs']);

// Admin API Routes (Authenticated via Session or Token)
// Using web middleware for session-based auth from admin panel
// Note: These routes must use 'web' middleware to support session cookies
Route::prefix('admin')->middleware(['web', 'auth:admin', 'permission:manage-user,admin'])->group(function () {
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
    Route::post('/users/{id}/mail', [\App\Http\Controllers\Api\Admin\UserController::class, 'sendMail']);
    Route::post('/users/{id}/balance', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateBalance']);
    Route::post('/users/{id}/kyc/{status}', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateKycStatus']);
    // Status counts for tabs
    Route::get('/users/stats/counts', [\App\Http\Controllers\Api\Admin\UserController::class, 'getCounts']);
});

// Authenticated User Routes
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    // User logout and token refresh
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Auth\LoginController::class, 'logout']);
    Route::post('/auth/refresh', [\App\Http\Controllers\Api\Auth\LoginController::class, 'refresh']);

    // User Profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\User\ProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\User\ProfileController::class, 'update']);
        Route::post('/change-password', [\App\Http\Controllers\Api\User\ProfileController::class, 'changePassword']);

        // GDPR Compliance
        Route::get('/gdpr/export', [\App\Http\Controllers\Api\User\UserGdprController::class, 'exportData']);
        Route::post('/gdpr/request-deletion', [\App\Http\Controllers\Api\User\UserGdprController::class, 'requestDeletion']);

        // KYC
        Route::get('/kyc', [\App\Http\Controllers\Api\User\KycController::class, 'index']);
        Route::post('/kyc', [\App\Http\Controllers\Api\User\KycController::class, 'store']);

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\User\DashboardController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Api\User\DashboardController::class, 'stats']);

        // Plans & Subscriptions
        Route::get('/plans', [\App\Http\Controllers\Api\User\PlanController::class, 'index']);
        Route::get('/plans/{id}', [\App\Http\Controllers\Api\User\PlanController::class, 'show']);
        Route::post('/plans/subscribe', [\App\Http\Controllers\Api\User\PlanController::class, 'subscribe']);
        Route::get('/subscriptions', [\App\Http\Controllers\Api\User\SubscriptionController::class, 'index']);
        Route::get('/subscriptions/current', [\App\Http\Controllers\Api\User\SubscriptionController::class, 'current']);

        // Signals
        Route::get('/signals', [\App\Http\Controllers\Api\User\SignalController::class, 'index']);
        Route::get('/signals/{id}', [\App\Http\Controllers\Api\User\SignalController::class, 'show']);
        Route::get('/signals/dashboard', [\App\Http\Controllers\Api\User\SignalController::class, 'dashboard']);

        // Payments & Deposits
        Route::post('/payments', [\App\Http\Controllers\Api\User\PaymentController::class, 'store']);
        Route::get('/payments', [\App\Http\Controllers\Api\User\PaymentController::class, 'index']);
        Route::get('/payments/{trx}', [\App\Http\Controllers\Api\User\PaymentController::class, 'show']);
        Route::post('/deposits', [\App\Http\Controllers\Api\User\PaymentController::class, 'deposit']);
        Route::get('/deposits', [\App\Http\Controllers\Api\User\PaymentController::class, 'deposits']);

        // Tickets
        Route::get('/tickets', [\App\Http\Controllers\Api\User\TicketController::class, 'index']);
        Route::post('/tickets', [\App\Http\Controllers\Api\User\TicketController::class, 'store']);
        Route::get('/tickets/{id}', [\App\Http\Controllers\Api\User\TicketController::class, 'show']);
        Route::post('/tickets/{id}/reply', [\App\Http\Controllers\Api\User\TicketController::class, 'reply']);
        Route::post('/tickets/{id}/close', [\App\Http\Controllers\Api\User\TicketController::class, 'close']);

        // Trading
        Route::prefix('trading')->group(function () {
            Route::get('/signals', [\App\Http\Controllers\Api\User\TradingController::class, 'getSignals']);
            Route::get('/executions', [\App\Http\Controllers\Api\User\TradingController::class, 'getExecutions']);
            Route::post('/execute', [\App\Http\Controllers\Api\User\TradingController::class, 'executeTrade']);
        });

        // Trading Bots
        Route::prefix('trading-bots')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'store']);
            Route::get('/options', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'getAvailableOptions']);
            Route::get('/{id}', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'destroy']);
            Route::post('/{id}/start', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'start']);
            Route::post('/{id}/stop', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'stop']);
            Route::post('/{id}/pause', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'pause']);
        });
    });
});
