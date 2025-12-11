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
            Route::post('/{id}/resume', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'resume']);
            Route::post('/{id}/restart', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'restart']);
            Route::get('/{id}/worker-status', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'workerStatus']);
            Route::get('/{id}/positions', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'positions']);
            Route::get('/{id}/logs', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'logs']);
            Route::get('/{id}/metrics', [\App\Http\Controllers\Api\User\TradingBotApiController::class, 'metrics']);
        });

        // Trading Configuration
        Route::prefix('trading-config')->group(function () {
            // Exchange Connections
            Route::get('/connections', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'getConnections']);
            Route::post('/connections', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'createConnection']);
            Route::put('/connections/{id}', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'updateConnection']);
            Route::delete('/connections/{id}', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'deleteConnection']);
            Route::post('/connections/{id}/test', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'testConnection']);
            
            // Risk Presets
            Route::get('/presets', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'getPresets']);
            Route::post('/presets', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'createPreset']);
            
            // Filter Strategies
            Route::get('/filter-strategies', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'getFilterStrategies']);
            
            // AI Profiles
            Route::get('/ai-profiles', [\App\Http\Controllers\Api\User\TradingConfigApiController::class, 'getAiProfiles']);
        });

        // Trading Operations
        Route::prefix('trading-operations')->group(function () {
            Route::get('/execution-logs', [\App\Http\Controllers\Api\User\TradingOperationsController::class, 'executionLogs']);
            Route::post('/manual-trade', [\App\Http\Controllers\Api\User\TradingOperationsController::class, 'manualTrade']);
            Route::get('/statistics', [\App\Http\Controllers\Api\User\TradingOperationsController::class, 'statistics']);
        });

        // Crypto Trading
        Route::prefix('crypto-trading')->group(function () {
            Route::get('/current-price', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'currentPrice']);
            Route::get('/ticker', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'latestTicker']);
            Route::post('/open-trade', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'openTrade']);
            Route::get('/trades', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'trades']);
            Route::post('/trades/{id}/close', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'closeTrade']);
            Route::get('/stream-prices', [\App\Http\Controllers\Api\User\CryptoTradeController::class, 'streamPrices']);
        });

        // Copy Trading
        Route::prefix('copy-trading')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'getSettings']);
            Route::put('/settings', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'updateSettings']);
            Route::get('/traders', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'getTraders']);
            Route::get('/traders/{id}', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'getTrader']);
            Route::get('/subscriptions', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'getSubscriptions']);
            Route::get('/history', [\App\Http\Controllers\Api\User\CopyTradingController::class, 'getHistory']);
        });

        // Withdrawals
        Route::prefix('withdrawals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\WithdrawApiController::class, 'index']);
            Route::get('/gateways', [\App\Http\Controllers\Api\User\WithdrawApiController::class, 'getGateways']);
            Route::post('/', [\App\Http\Controllers\Api\User\WithdrawApiController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\User\WithdrawApiController::class, 'show']);
        });

        // Two-Factor Authentication
        Route::prefix('2fa')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\User\TwoFactorApiController::class, 'status']);
            Route::post('/generate-secret', [\App\Http\Controllers\Api\User\TwoFactorApiController::class, 'generateSecret']);
            Route::post('/enable', [\App\Http\Controllers\Api\User\TwoFactorApiController::class, 'enable']);
            Route::post('/disable', [\App\Http\Controllers\Api\User\TwoFactorApiController::class, 'disable']);
            Route::post('/verify', [\App\Http\Controllers\Api\User\TwoFactorApiController::class, 'verify']);
        });

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\TransactionApiController::class, 'index']);
            Route::get('/summary', [\App\Http\Controllers\Api\User\TransactionApiController::class, 'summary']);
            Route::get('/{trx}', [\App\Http\Controllers\Api\User\TransactionApiController::class, 'show']);
        });

        // Onboarding
        Route::prefix('onboarding')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\User\OnboardingApiController::class, 'status']);
            Route::post('/step', [\App\Http\Controllers\Api\User\OnboardingApiController::class, 'completeStep']);
            Route::post('/skip', [\App\Http\Controllers\Api\User\OnboardingApiController::class, 'skip']);
            Route::post('/complete', [\App\Http\Controllers\Api\User\OnboardingApiController::class, 'complete']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\NotificationApiController::class, 'index']);
            Route::get('/unread-count', [\App\Http\Controllers\Api\User\NotificationApiController::class, 'unreadCount']);
            Route::post('/mark-as-read', [\App\Http\Controllers\Api\User\NotificationApiController::class, 'markAsRead']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\User\NotificationApiController::class, 'destroy']);
        });

        // Referrals
        Route::prefix('referrals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\ReferralApiController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\User\ReferralApiController::class, 'stats']);
        });

        // Money Transfer
        Route::prefix('transfer')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\User\TransferApiController::class, 'transfer']);
            Route::get('/history', [\App\Http\Controllers\Api\User\TransferApiController::class, 'history']);
        });

        // Multi-Channel Signals
        Route::prefix('channel-sources')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\ChannelSourceApiController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\User\ChannelSourceApiController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\User\ChannelSourceApiController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\User\ChannelSourceApiController::class, 'destroy']);
        });

        // Marketplace
        Route::prefix('marketplace')->group(function () {
            Route::get('/bot-templates', [\App\Http\Controllers\Api\User\MarketplaceApiController::class, 'botTemplates']);
            Route::post('/bot-templates/{id}/clone', [\App\Http\Controllers\Api\User\MarketplaceApiController::class, 'cloneTemplate']);
            Route::get('/trading-presets', [\App\Http\Controllers\Api\User\MarketplaceApiController::class, 'tradingPresets']);
            Route::post('/trading-presets/{id}/clone', [\App\Http\Controllers\Api\User\MarketplaceApiController::class, 'clonePreset']);
        });

        // Backtesting
        Route::prefix('backtesting')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'index']);
            Route::post('/run', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'run']);
            Route::get('/{id}', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'show']);
        });

        // Copy Trading
        Route::prefix('copy-trading')->group(function () {
            Route::get('/traders', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'traders']);
            Route::post('/traders/{id}/follow', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'followTrader']);
            Route::delete('/traders/{id}/unfollow', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'unfollowTrader']);
            Route::get('/subscriptions', [\App\Http\Controllers\Api\User\BacktestingApiController::class, 'mySubscriptions']);
        });
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

    // Channel Signals (Auto-created signals)
    Route::prefix('channel-signals')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'update']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'reject']);
        Route::post('/bulk/approve', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'bulkApprove']);
        Route::post('/bulk/reject', [\App\Http\Controllers\Api\Admin\ChannelSignalController::class, 'bulkReject']);
    });

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
    Route::get('/gateways/online', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'getOnlineGateways']);
    Route::get('/gateways/offline', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'getOfflineGateways']);
    Route::get('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'show']);
    Route::put('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'update']);
    Route::delete('/gateways/{gateway}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'destroy']);
    Route::post('/gateways/{id}/status', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'toggleStatus']);
    Route::put('/gateways/{id}/online', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'updateOnlineGateway']);
    Route::post('/gateways/gourl', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'updateGourlGateway']);
    Route::post('/gateways/offline', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'createOfflineGateway']);
    Route::put('/gateways/offline/{id}', [\App\Http\Controllers\Api\Admin\GatewayController::class, 'updateOfflineGateway']);

    // Ticket Management
    Route::get('/tickets', [\App\Http\Controllers\Api\Admin\TicketController::class, 'index']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\Admin\TicketController::class, 'show']);
    Route::post('/tickets/{id}/reply', [\App\Http\Controllers\Api\Admin\TicketController::class, 'reply']);
    Route::post('/tickets/{id}/close', [\App\Http\Controllers\Api\Admin\TicketController::class, 'close']);
    Route::delete('/tickets/{id}', [\App\Http\Controllers\Api\Admin\TicketController::class, 'destroy']);

    // Configuration
    Route::get('/configuration', [\App\Http\Controllers\Api\Admin\ConfigurationController::class, 'index']);
    Route::put('/configuration', [\App\Http\Controllers\Api\Admin\ConfigurationController::class, 'update']);

    // Management (Pages, Sections, Settings)
    Route::prefix('management')->group(function () {
        // Pages
        Route::get('/pages', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'getPages']);
        Route::post('/pages', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'createPage']);
        Route::put('/pages/{id}', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'updatePage']);
        Route::delete('/pages/{id}', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'deletePage']);

        // Sections
        Route::get('/sections', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'getSections']);
        Route::get('/sections/{name}', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'getSectionContent']);
        Route::put('/sections/{name}', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'updateSectionContent']);

        // Settings
        Route::get('/settings', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'getSettings']);
        Route::put('/settings', [\App\Http\Controllers\Api\Admin\ManagementController::class, 'updateSettings']);
    });

    // Backup Management
    Route::prefix('backups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\BackupApiController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\BackupApiController::class, 'create']);
        Route::get('/download', [\App\Http\Controllers\Api\Admin\BackupApiController::class, 'download']);
        Route::delete('/', [\App\Http\Controllers\Api\Admin\BackupApiController::class, 'delete']);
        Route::post('/restore', [\App\Http\Controllers\Api\Admin\BackupApiController::class, 'restore']);
    });

    // Email Management
    Route::prefix('email')->group(function () {
        Route::get('/config', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'getConfig']);
        Route::put('/config', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'updateConfig']);
        Route::get('/templates', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'listTemplates']);
        Route::get('/templates/{slug}', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'getTemplate']);
        Route::put('/templates/{slug}', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'updateTemplate']);
        Route::get('/subscribers', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'listSubscribers']);
        Route::post('/send-single', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'sendSingle']);
        Route::post('/send-bulk', [\App\Http\Controllers\Api\Admin\EmailApiController::class, 'sendBulk']);
    });

    // Roles & Permissions
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\RoleApiController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\RoleApiController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\Admin\RoleApiController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\RoleApiController::class, 'destroy']);
    });

    // Admin Users
    Route::prefix('admins')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\AdminUserApiController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\AdminUserApiController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\Admin\AdminUserApiController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\AdminUserApiController::class, 'destroy']);
    });

    // Logs & Reports
    Route::prefix('logs')->group(function () {
        Route::get('/transactions', [\App\Http\Controllers\Api\Admin\LogsApiController::class, 'transactions']);
        Route::get('/payments', [\App\Http\Controllers\Api\Admin\LogsApiController::class, 'payments']);
        Route::get('/withdrawals', [\App\Http\Controllers\Api\Admin\LogsApiController::class, 'withdrawals']);
        Route::get('/commissions', [\App\Http\Controllers\Api\Admin\LogsApiController::class, 'commissions']);
        Route::get('/trades', [\App\Http\Controllers\Api\Admin\LogsApiController::class, 'trades']);
    });

    // Language Management
    Route::prefix('languages')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'destroy']);
        Route::get('/{code}/translations', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'getTranslations']);
        Route::put('/{code}/translations', [\App\Http\Controllers\Api\Admin\LanguageApiController::class, 'updateTranslation']);
        Route::post('/{lang}/auto-translate', [\App\Http\Controllers\Api\Admin\LanguageTranslationController::class, 'autoTranslate']);
        Route::get('/settings', [\App\Http\Controllers\Api\Admin\LanguageTranslationController::class, 'getSettings']);
        Route::put('/settings', [\App\Http\Controllers\Api\Admin\LanguageTranslationController::class, 'updateSettings']);
        Route::post('/test', [\App\Http\Controllers\Api\Admin\LanguageTranslationController::class, 'testApi']);
    });

    // Theme Management
    Route::prefix('themes')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ThemeApiController::class, 'index']);
        Route::post('/activate', [\App\Http\Controllers\Api\Admin\ThemeApiController::class, 'activate']);
        Route::post('/upload', [\App\Http\Controllers\Api\Admin\ThemeApiController::class, 'upload']);
        Route::delete('/{theme}', [\App\Http\Controllers\Api\Admin\ThemeApiController::class, 'destroy']);
    });

    // System Management
    Route::prefix('system')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'getSystemStatus']);
        Route::post('/optimize', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimize']);
        Route::post('/cache/clear', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'clearCache']);
        Route::post('/optimize/assets', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimizeAssets']);
        Route::post('/optimize/http', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimizeHttp']);
        Route::post('/optimize/media', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimizeMedia']);
        Route::post('/optimize/cache', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimizeCache']);
        Route::post('/optimize/database', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'optimizeDatabase']);
        Route::post('/cache/prewarm', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'prewarmCache']);
        Route::post('/backup/create', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'createBackup']);
        Route::post('/backup/load', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'loadBackup']);
        Route::post('/backup/delete', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'deleteBackup']);
        Route::post('/database/reseed', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'reseedDatabase']);
        Route::post('/database/reset', [\App\Http\Controllers\Api\Admin\SystemManagementController::class, 'resetDatabase']);
        
        // AlgoExpert++ System Tools
        Route::get('/health', [\App\Http\Controllers\Api\Admin\SystemToolsApiController::class, 'health']);
        Route::get('/performance-stats', [\App\Http\Controllers\Api\Admin\SystemToolsApiController::class, 'performance']);
        Route::get('/cron-jobs', [\App\Http\Controllers\Api\Admin\SystemToolsApiController::class, 'cronJobs']);
        Route::get('/horizon/stats', [\App\Http\Controllers\Api\Admin\SystemToolsApiController::class, 'horizonStats']);
        Route::post('/horizon/clear-failed', [\App\Http\Controllers\Api\Admin\SystemToolsApiController::class, 'clearFailedJobs']);
    });

    // Theme Management
    Route::prefix('themes')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'index']);
        Route::put('/{name}', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'update']);
        Route::put('/backend/{name}', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'updateBackend']);
        Route::post('/{theme}/color', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'changeColor']);
        Route::post('/upload', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'upload']);
        Route::get('/template/download', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'downloadTemplate']);
        Route::delete('/{theme}', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'destroy']);
        Route::post('/deactivate-all', [\App\Http\Controllers\Api\Admin\ThemeManagementController::class, 'deactivateAll']);
    });

    // Addon Management
    Route::prefix('addons')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\AddonApiController::class, 'index']);
        Route::post('/upload', [\App\Http\Controllers\Api\Admin\AddonApiController::class, 'upload']);
        Route::post('/{addon}/status', [\App\Http\Controllers\Api\Admin\AddonApiController::class, 'updateStatus']);
        Route::get('/{addon}/modules', [\App\Http\Controllers\Api\Admin\AddonApiController::class, 'modules']);
        Route::post('/{addon}/modules/{module}', [\App\Http\Controllers\Api\Admin\AddonApiController::class, 'updateModule']);
    });
});

// Reference Data (public endpoints)
Route::get('/currency-pairs', [\App\Http\Controllers\Api\ReferenceDataController::class, 'currencyPairs']);
Route::get('/timeframes', [\App\Http\Controllers\Api\ReferenceDataController::class, 'timeframes']);
Route::get('/markets', [\App\Http\Controllers\Api\ReferenceDataController::class, 'markets']);

// System Configuration
Route::controller(\App\Http\Controllers\Api\SystemController::class)->group(function () {
    Route::get('/config', 'config');
    Route::get('/languages', 'languages');
    Route::get('/translations/{lang}', 'translations');
});

// Frontend Content
Route::get('/content/{name}', [\App\Http\Controllers\Api\ContentController::class, 'index']);

// Webhook Routes (no authentication required, uses channel source ID)
Route::post('/webhook/telegram/{channelSourceId}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);
Route::post('/webhook/channel/{channelSourceId}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'handle']);
