<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Addons\TradingExecutionEngine\App\Contracts\ExecutionServiceInterface;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionLog;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use App\Models\Plan;
use App\Models\Signal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignalExecutionService implements ExecutionServiceInterface
{
    protected ConnectionService $connectionService;
    protected NotificationService $notificationService;

    public function __construct(
        ConnectionService $connectionService,
        NotificationService $notificationService
    ) {
        $this->connectionService = $connectionService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute a signal on a connection.
     */
    public function executeSignal(Signal $signal, int $connectionId, array $options = []): array
    {
        try {
            $connection = ExecutionConnection::findOrFail($connectionId);

            // Check AI decision from channel message (Sprint 2: AI Integration)
            $aiDecision = $this->getAiDecision($signal, $connection);
            if ($aiDecision && !$aiDecision['execute']) {
                return [
                    'success' => false,
                    'execution_log_id' => null,
                    'position_id' => null,
                    'message' => 'AI rejected signal: ' . ($aiDecision['reason'] ?? 'Unknown reason'),
                ];
            }

            // Apply AI risk factor as size multiplier if provided
            if ($aiDecision && isset($aiDecision['adjusted_risk_factor']) && $aiDecision['adjusted_risk_factor'] < 1.0) {
                $options['size_multiplier'] = $aiDecision['adjusted_risk_factor'];
            }

            // OpenRouter AI Market Analysis (if enabled in preset)
            if ($this->shouldPerformMarketAnalysis($connection)) {
                $analysisResult = $this->performMarketAnalysis($signal, $connection, $options);
                
                if ($analysisResult['should_skip']) {
                    return [
                        'success' => false,
                        'execution_log_id' => null,
                        'position_id' => null,
                        'message' => $analysisResult['reason'],
                    ];
                }
                
                // Apply size adjustment if recommended (combine with AI risk factor if both exist)
                if (isset($analysisResult['size_multiplier'])) {
                    $existingMultiplier = $options['size_multiplier'] ?? 1.0;
                    $options['size_multiplier'] = $existingMultiplier * $analysisResult['size_multiplier'];
                }
            }

            // Check if can execute
            $canExecute = $this->canExecute($signal, $connectionId);
            if (!$canExecute['can_execute']) {
                return [
                    'success' => false,
                    'execution_log_id' => null,
                    'position_id' => null,
                    'message' => $canExecute['reason'],
                ];
            }

            // Get adapter
            $adapter = $this->connectionService->getAdapter($connection);
            if (!$adapter) {
                return [
                    'success' => false,
                    'execution_log_id' => null,
                    'position_id' => null,
                    'message' => 'Unsupported exchange/broker type',
                ];
            }

            // Get symbol from signal
            $symbol = $this->getSymbolFromSignal($signal);
            if (!$symbol) {
                return [
                    'success' => false,
                    'execution_log_id' => null,
                    'position_id' => null,
                    'message' => 'Could not determine symbol from signal',
                ];
            }

            // Calculate position size
            $quantity = $this->calculatePositionSize($signal, $connection, $options);

            // Prepare order options
            $orderOptions = [];
            if ($signal->sl > 0) {
                $orderOptions['sl_price'] = $signal->sl;
            }
            if ($signal->tp > 0) {
                $orderOptions['tp_price'] = $signal->tp;
            }

            // Place order
            $executionType = $options['execution_type'] ?? 'market';
            $result = $executionType === 'limit' && isset($options['limit_price'])
                ? $adapter->placeLimitOrder($symbol, $signal->direction, $quantity, $options['limit_price'], $orderOptions)
                : $adapter->placeMarketOrder($symbol, $signal->direction, $quantity, $orderOptions);

            // Create execution log
            $executionLog = ExecutionLog::create([
                'signal_id' => $signal->id,
                'connection_id' => $connectionId,
                'execution_type' => $executionType,
                'order_id' => $result['order_id'] ?? null,
                'symbol' => $symbol,
                'direction' => $signal->direction,
                'quantity' => $quantity,
                'entry_price' => $result['price'] ?? $signal->open_price,
                'sl_price' => $signal->sl ?? null,
                'tp_price' => $signal->tp ?? null,
                'status' => $result['success'] ? 'executed' : 'failed',
                'executed_at' => $result['success'] ? now() : null,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
                'response_data' => $result['data'] ?? [],
            ]);

            // Update connection last used
            $connection->updateLastUsed();

            // Create position if order was successful
            $positionId = null;
            if ($result['success'] && $executionLog->isExecuted()) {
                $position = ExecutionPosition::create([
                    'signal_id' => $signal->id,
                    'connection_id' => $connectionId,
                    'execution_log_id' => $executionLog->id,
                    'order_id' => $result['order_id'],
                    'symbol' => $symbol,
                    'direction' => $signal->direction,
                    'quantity' => $quantity,
                    'entry_price' => $executionLog->entry_price,
                    'current_price' => $executionLog->entry_price,
                    'sl_price' => $signal->sl ?? null,
                    'tp_price' => $signal->tp ?? null,
                    'status' => 'open',
                ]);
                $positionId = $position->id;

                // Send notification
                $this->notificationService->notifyExecution(
                    $connection,
                    $signal,
                    $position,
                    'execution',
                    'Order executed successfully'
                );
            } else {
                // Send error notification
                $this->notificationService->notifyError(
                    $connection,
                    $signal,
                    'execution',
                    $result['message'] ?? 'Failed to execute order'
                );
            }

            return [
                'success' => $result['success'],
                'execution_log_id' => $executionLog->id,
                'position_id' => $positionId,
                'message' => $result['message'] ?? ($result['success'] ? 'Order executed successfully' : 'Failed to execute order'),
            ];
        } catch (\Exception $e) {
            Log::error("Signal execution failed", [
                'signal_id' => $signal->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'execution_log_id' => null,
                'position_id' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a signal can be executed on a connection.
     */
    public function canExecute(Signal $signal, int $connectionId): array
    {
        try {
            $connection = ExecutionConnection::findOrFail($connectionId);

            // Check if connection is active
            if (!$connection->isActive()) {
                return [
                    'can_execute' => false,
                    'reason' => 'Connection is not active',
                ];
            }

            // Check if signal has required data
            if (!$signal->currency_pair_id || !$signal->open_price || !$signal->direction) {
                return [
                    'can_execute' => false,
                    'reason' => 'Signal missing required data',
                ];
            }

            // Check balance (basic check)
            $adapter = $this->connectionService->getAdapter($connection);
            if ($adapter) {
                $balance = $adapter->getBalance();
                // Basic balance check - can be enhanced
            }

            return [
                'can_execute' => true,
                'reason' => null,
            ];
        } catch (\Exception $e) {
            return [
                'can_execute' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available connections for a signal.
     */
    public function getAvailableConnections(Signal $signal, ?int $userId = null, ?int $adminId = null): array
    {
        $connections = $this->connectionService->getActiveConnections($userId, $adminId);
        
        $available = [];
        foreach ($connections as $connection) {
            $canExecute = $this->canExecute($signal, $connection->id);
            if ($canExecute['can_execute']) {
                $available[] = $connection->id;
            }
        }

        return $available;
    }

    /**
     * Get symbol from signal.
     */
    protected function getSymbolFromSignal(Signal $signal): ?string
    {
        if (!$signal->pair) {
            return null;
        }

        // Return currency pair name (e.g., BTC/USD, EURUSD)
        return $signal->pair->name;
    }

    /**
     * Calculate position size based on signal and connection settings.
     */
    protected function calculatePositionSize(Signal $signal, ExecutionConnection $connection, array $options): float
    {
        $settings = $connection->settings ?? [];
        $strategy = $settings['position_sizing_strategy'] ?? 'fixed';

        switch ($strategy) {
            case 'percentage':
                // Percentage of balance
                $adapter = $this->connectionService->getAdapter($connection);
                $balance = $adapter->getBalance();
                $totalBalance = $balance['balance'] ?? 0;
                $percentage = $settings['position_size_percentage'] ?? 1;
                $price = $signal->open_price;
                return ($totalBalance * $percentage / 100) / $price;

            case 'fixed_amount':
                // Fixed dollar amount
                $amount = $settings['position_size_amount'] ?? 100;
                $price = $signal->open_price;
                return $amount / $price;

            case 'signal_based':
                // Use quantity from signal if available (future enhancement)
                return $options['quantity'] ?? 0.01;

            case 'fixed':
            default:
                // Fixed quantity
                $baseSize = $settings['position_size_quantity'] ?? 0.01;
                
                // Apply size multiplier from market analysis if provided
                if (isset($options['size_multiplier'])) {
                    $baseSize *= $options['size_multiplier'];
                }
                
                return $baseSize;
        }
    }

    /**
     * Check if market analysis should be performed for this connection.
     */
    protected function shouldPerformMarketAnalysis(ExecutionConnection $connection): bool
    {
        // Check if OpenRouter addon is available
        if (!class_exists(\Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer::class)) {
            return false;
        }

        // Check if enabled in connection settings
        $settings = $connection->settings ?? [];
        return $settings['enable_ai_market_analysis'] ?? false;
    }

    /**
     * Perform AI market analysis on signal before execution.
     */
    protected function performMarketAnalysis(Signal $signal, ExecutionConnection $connection, array $options): array
    {
        try {
            // Get market analyzer service
            $analyzer = app(\Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer::class);

            // Get market data (simplified - can be enhanced with real market data)
            $marketData = $this->getMarketData($signal, $connection);

            // Perform analysis
            $result = $analyzer->analyzeSignal($signal, $marketData);

            // Process recommendation
            if ($result->shouldReject()) {
                return [
                    'should_skip' => true,
                    'reason' => 'AI Market Analysis: Rejected - ' . $result->reasoning,
                ];
            }

            if ($result->shouldSizeDown()) {
                return [
                    'should_skip' => false,
                    'size_multiplier' => 0.5, // Reduce position size by 50%
                    'reason' => 'AI Market Analysis: Size reduced - ' . $result->reasoning,
                ];
            }

            if ($result->needsManualReview()) {
                // For manual review, we can either skip or proceed with caution
                $settings = $connection->settings ?? [];
                $skipOnManualReview = $settings['skip_on_manual_review'] ?? true;
                
                if ($skipOnManualReview) {
                    return [
                        'should_skip' => true,
                        'reason' => 'AI Market Analysis: Manual review required - ' . $result->reasoning,
                    ];
                }
            }

            // Accepted or proceed
            return [
                'should_skip' => false,
                'size_multiplier' => 1.0,
                'reason' => 'AI Market Analysis: Approved - ' . $result->reasoning,
            ];

        } catch (\Exception $e) {
            Log::warning('Market analysis failed, proceeding with execution', [
                'signal_id' => $signal->id,
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            // On error, proceed with execution (fail-safe)
            return [
                'should_skip' => false,
                'size_multiplier' => 1.0,
                'reason' => 'Market analysis unavailable',
            ];
        }
    }

    /**
     * Get market data for analysis (simplified placeholder).
     * In production, this should fetch real market data from the exchange/broker.
     */
    protected function getMarketData(Signal $signal, ExecutionConnection $connection): array
    {
        // Placeholder - in production, fetch real candles and indicators
        // from the exchange/broker via the adapter
        
        try {
            $adapter = $this->connectionService->getAdapter($connection);
            $symbol = $this->getSymbolFromSignal($signal);
            
            if ($adapter && $symbol) {
                // Try to get recent candles (if adapter supports it)
                // This is a placeholder - actual implementation depends on adapter capabilities
                $candles = []; // $adapter->getCandles($symbol, $signal->time->name, 50);
                
                return [
                    'candles' => $candles,
                    'indicators' => [
                        'trend' => 'unknown',
                        'volatility' => 'medium',
                    ],
                ];
            }
        } catch (\Exception $e) {
            Log::debug('Could not fetch market data for analysis', [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Return minimal data if fetching fails
        return [
            'candles' => [],
            'indicators' => [],
        ];
    }
}

