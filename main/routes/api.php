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
    
    // Admin Authentication
    Route::prefix('admin')->group(function () {
        Route::post('/login', [\App\Http\Controllers\Api\Admin\Auth\LoginController::class, 'login']);
    });
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
    });
});

// Admin Routes (require admin authentication)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Admin\Auth\LoginController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

    // User Management
    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::get('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
    Route::post('/users/{id}/status', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleStatus']);
    Route::post('/users/{id}/balance', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateBalance']);
    Route::post('/users/{id}/kyc/{status}', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateKycStatus']);
    Route::post('/users/{id}/mail', [\App\Http\Controllers\Api\Admin\UserController::class, 'sendMail']);

    // Plan Management
    Route::get('/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'index']);
    Route::post('/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'store']);
    Route::get('/plans/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'show']);
    Route::put('/plans/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'update']);
    Route::delete('/plans/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'destroy']);
    Route::post('/plans/{id}/status', [\App\Http\Controllers\Api\Admin\PlanController::class, 'toggleStatus']);

    // Signal Management
    Route::get('/signals', [\App\Http\Controllers\Api\Admin\SignalController::class, 'index']);
    Route::post('/signals', [\App\Http\Controllers\Api\Admin\SignalController::class, 'store']);
    Route::get('/signals/{id}', [\App\Http\Controllers\Api\Admin\SignalController::class, 'show']);
    Route::put('/signals/{id}', [\App\Http\Controllers\Api\Admin\SignalController::class, 'update']);
    Route::delete('/signals/{id}', [\App\Http\Controllers\Api\Admin\SignalController::class, 'destroy']);
    Route::post('/signals/{id}/publish', [\App\Http\Controllers\Api\Admin\SignalController::class, 'publish']);
    Route::post('/signals/{id}/assign-plans', [\App\Http\Controllers\Api\Admin\SignalController::class, 'assignPlans']);

    // Payment Management
    Route::get('/payments', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
    Route::get('/payments/{id}', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'show']);
    Route::post('/payments/{id}/approve', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'approve']);
    Route::post('/payments/{id}/reject', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'reject']);
    Route::get('/deposits', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'deposits']);
    Route::post('/deposits/{id}/approve', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'approveDeposit']);
    Route::post('/deposits/{id}/reject', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'rejectDeposit']);

    // Withdrawal Management
    Route::get('/withdrawals', [\App\Http\Controllers\Api\Admin\WithdrawController::class, 'index']);
    Route::get('/withdrawals/{withdraw}', [\App\Http\Controllers\Api\Admin\WithdrawController::class, 'show']);
    Route::post('/withdrawals/{withdraw}/approve', [\App\Http\Controllers\Api\Admin\WithdrawController::class, 'approve']);
    Route::post('/withdrawals/{withdraw}/reject', [\App\Http\Controllers\Api\Admin\WithdrawController::class, 'reject']);

    // Gateway Management
    Route::get('/gateways', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'index']);
    Route::post('/gateways', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'store']);
    Route::get('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'show']);
    Route::put('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'update']);
    Route::delete('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'destroy']);
    Route::post('/gateways/{id}/status', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'toggleStatus']);

    // Ticket Management
    Route::get('/tickets', [\App\Http\Controllers\Api\Admin\TicketController::class, 'index']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Admin\TicketController::class, 'show']);
    Route::post('/tickets/{id}/reply', [\App\Http\Controllers\Api\Admin\TicketController::class, 'reply']);
    Route::post('/tickets/{id}/close', [\App\Http\Controllers\Api\Admin\TicketController::class, 'close']);
    Route::delete('/tickets/{id}', [\App\Http\Controllers\Api\Admin\TicketController::class, 'destroy']);

    // Configuration
    Route::get('/configuration', [\App\Http\Controllers\Api\Admin\ConfigurationController::class, 'index']);
    Route::put('/configuration', [\App\Http\Controllers\Api\Admin\ConfigurationController::class, 'update']);
});

// Reference Data (public endpoints)
Route::get('/currency-pairs', [\App\Http\Controllers\Api\ReferenceDataController::class, 'currencyPairs']);
Route::get('/timeframes', [\App\Http\Controllers\Api\ReferenceDataController::class, 'timeframes']);
Route::get('/markets', [\App\Http\Controllers\Api\ReferenceDataController::class, 'markets']);

// Webhook Routes (no authentication required, uses channel source ID)
Route::post('/webhook/telegram/{channelSourceId}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);
Route::post('/webhook/channel/{channelSourceId}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'handle']);
