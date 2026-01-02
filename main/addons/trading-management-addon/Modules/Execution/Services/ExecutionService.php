<?php

namespace Addons\TradingManagement\Modules\Execution\Services;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;
use Addons\TradingManagement\Shared\Contracts\ExchangeAdapterInterface;
use App\Models\Signal;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExecutionService
{
    protected ExchangeConnectionService $connectionService;

    public function __construct(ExchangeConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Execute a trade based on signal and calculated risk
     * 
     * @param Signal $signal Signal to execute
     * @param ExchangeConnection $connection Connection to execute on
     * @param array $riskCalculation Result from RiskCalculator ['lot_size', 'sl', 'tp', ...]
     * @param array $options Additional options (comment, magic, etc.)
     * @return ExecutionLog
     */
    public function executeOrder(Signal $signal, ExchangeConnection $connection, array $riskCalculation, array $options = []): ExecutionLog
    {
        $log = ExecutionLog::create([
            'connection_id' => $connection->id,
            'signal_id' => $signal->id,
            'symbol' => $signal->pair->name ?? $signal->symbol,
            'direction' => $signal->direction,
            'quantity' => $riskCalculation['lot_size'],
            'sl_price' => $riskCalculation['sl'] ?? null,
            'tp_price' => $riskCalculation['tp'] ?? null,
            'execution_type' => 'MARKET',
            'status' => 'PENDING',
        ]);

        try {
            // 1. Get Adapter
            /** @var ExchangeAdapterInterface $adapter */
            $adapter = $this->connectionService->getAdapter($connection);

            if (!$adapter || !($adapter instanceof ExchangeAdapterInterface)) {
                throw new Exception("Connection does not support execution (Adapter invalid)");
            }

            // 2. Prepare params
            $side = strtolower($signal->direction);
            $amount = (float) $riskCalculation['lot_size'];
            $params = array_merge($options, [
                'stopLoss' => $riskCalculation['sl'] ?? null,
                'takeProfit' => $riskCalculation['tp'] ?? null,
            ]);

            // 3. Execute
            // Assuming MARKET order for now. Can be enhanced for LIMIT.
            $result = $adapter->createMarketOrder(
                $signal->pair->name ?? $signal->symbol,
                $side,
                $amount,
                $params
            );

            // 4. Get execution price from exchange response
            $executionPrice = $result['price'] ?? $result['average'] ?? $result['fillPrice'] ?? null;
            
            // Expected entry price from signal
            $expectedEntryPrice = (float) $signal->open_price;
            
            // Calculate slippage if we have both expected and execution prices
            $slippagePips = null;
            if ($executionPrice !== null && $executionPrice > 0 && $expectedEntryPrice > 0) {
                $slippageService = app(SlippageProtectionService::class);
                $symbol = $signal->pair->name ?? $signal->symbol ?? '';
                
                if (!empty($symbol)) {
                    $slippagePips = $slippageService->calculateSlippage(
                        $expectedEntryPrice,
                        $executionPrice,
                        $side,
                        $symbol
                    );
                    
                    // Validate slippage
                    $maxSlippage = $slippageService->getMaxAllowedSlippage([]);
                    $slippageValidation = $slippageService->validateSlippage($slippagePips, $maxSlippage);
                    
                    if (!$slippageValidation['acceptable']) {
                        Log::warning('ExecutionService: Slippage exceeded on order execution', [
                            'log_id' => $log->id,
                            'symbol' => $symbol,
                            'expected_price' => $expectedEntryPrice,
                            'execution_price' => $executionPrice,
                            'slippage_pips' => $slippagePips,
                            'max_allowed' => $maxSlippage,
                        ]);
                    }
                }
            }
            
            // Use execution price as entry price (actual price from exchange)
            $entryPrice = $executionPrice ?? $expectedEntryPrice;

            // 5. Update Log Success
            $log->update([
                'status' => 'FILLED', // or PARTIAL
                'order_id' => $result['id'] ?? null,
                'entry_price' => $entryPrice, // Store execution price (actual price from exchange)
                'execution_price' => $executionPrice ?? $entryPrice, // Store actual execution price from exchange
                'slippage_pips' => $slippagePips, // Store slippage in pips
                'response_data' => $result,
                'executed_at' => now(),
            ]);
            
            // Log slippage for reference
            if ($slippagePips !== null) {
                Log::info('ExecutionService: Order executed with slippage', [
                    'log_id' => $log->id,
                    'expected_price' => $expectedEntryPrice,
                    'execution_price' => $executionPrice,
                    'slippage_pips' => $slippagePips,
                ]);
            }

            // Reset failure count on success
            $this->resetFailureCount($connection);

            return $log;

        } catch (\Exception $e) {
            // 5. Update Log Failure with specific error handling
            $errorMessage = $e->getMessage();
            $errorType = $this->classifyError($errorMessage);
            $userFriendlyMessage = $this->getUserFriendlyErrorMessage($errorType, $errorMessage);
            
            // Track failure for circuit breaker
            $this->trackFailure($connection);
            
            // Check circuit breaker
            $circuitBreakerCheck = $this->checkCircuitBreaker($connection);
            if ($circuitBreakerCheck['should_halt']) {
                Log::error("ExecutionService: Circuit breaker triggered - trading halted", [
                    'connection_id' => $connection->id,
                    'failure_count' => $circuitBreakerCheck['failure_count'],
                    'max_failures' => $circuitBreakerCheck['max_failures'],
                ]);
                
                // Deactivate connection
                $connection->update(['is_active' => false, 'status' => 'error']);
                
                $log->update([
                    'status' => 'FAILED',
                    'error_message' => 'Trading halted due to consecutive failures. Connection deactivated.',
                    'error_type' => 'CIRCUIT_BREAKER',
                ]);
                
                throw new Exception('Trading halted due to consecutive failures. Connection deactivated.');
            }
            
            Log::error("ExecutionService: Order failed", [
                'error' => $errorMessage,
                'error_type' => $errorType,
                'log_id' => $log->id,
                'connection_id' => $connection->id,
                'signal_id' => $signal->id,
                'symbol' => $signal->pair->name ?? $signal->symbol,
            ]);
            
            $log->update([
                'status' => 'FAILED',
                'error_message' => $userFriendlyMessage,
                'error_type' => $errorType,
            ]);

            // Rethrow or return failed log? Returning log allows caller to handle.
            return $log;
        }
    }

    /**
     * Classify error type for better handling
     */
    protected function classifyError(string $errorMessage): string
    {
        $errorLower = strtolower($errorMessage);
        
        // Market closed errors
        if (stripos($errorMessage, 'MARKET_CLOSED') !== false || 
            stripos($errorMessage, 'market is closed') !== false ||
            stripos($errorMessage, 'market closed') !== false) {
            return 'MARKET_CLOSED';
        }
        
        // Insufficient balance/equity
        if (stripos($errorLower, 'insufficient') !== false && 
            (stripos($errorLower, 'balance') !== false || stripos($errorLower, 'equity') !== false || stripos($errorLower, 'funds') !== false)) {
            return 'INSUFFICIENT_BALANCE';
        }
        
        // Invalid order parameters
        if (stripos($errorLower, 'invalid') !== false && 
            (stripos($errorLower, 'price') !== false || stripos($errorLower, 'volume') !== false || stripos($errorLower, 'lot') !== false)) {
            return 'INVALID_PARAMETERS';
        }
        
        // Rate limit errors
        if (stripos($errorLower, 'rate limit') !== false || 
            stripos($errorLower, 'too many requests') !== false ||
            stripos($errorLower, '429') !== false) {
            return 'RATE_LIMIT';
        }
        
        // Network/timeout errors
        if (stripos($errorLower, 'timeout') !== false || 
            stripos($errorLower, 'connection') !== false ||
            stripos($errorLower, 'network') !== false) {
            return 'NETWORK_ERROR';
        }
        
        // Authentication errors
        if (stripos($errorLower, 'authentication') !== false || 
            stripos($errorLower, 'unauthorized') !== false ||
            stripos($errorLower, '401') !== false ||
            stripos($errorLower, '403') !== false) {
            return 'AUTHENTICATION_ERROR';
        }
        
        // Symbol not found
        if (stripos($errorLower, 'symbol') !== false && 
            (stripos($errorLower, 'not found') !== false || stripos($errorLower, 'invalid') !== false)) {
            return 'INVALID_SYMBOL';
        }
        
        // Position limit errors
        if (stripos($errorLower, 'position') !== false && 
            (stripos($errorLower, 'limit') !== false || stripos($errorLower, 'maximum') !== false)) {
            return 'POSITION_LIMIT';
        }
        
        return 'UNKNOWN_ERROR';
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyErrorMessage(string $errorType, string $originalMessage): string
    {
        switch ($errorType) {
            case 'MARKET_CLOSED':
                return 'Market is currently closed. Trade will be retried when market reopens.';
            
            case 'INSUFFICIENT_BALANCE':
                return 'Insufficient balance to execute this trade. Please deposit more funds or reduce position size.';
            
            case 'INVALID_PARAMETERS':
                return 'Invalid order parameters. Please check entry price, stop loss, and take profit values.';
            
            case 'RATE_LIMIT':
                return 'Rate limit exceeded. Please wait a moment before trying again.';
            
            case 'NETWORK_ERROR':
                return 'Network error occurred. Please check your connection and try again.';
            
            case 'AUTHENTICATION_ERROR':
                return 'Authentication failed. Please check your exchange connection credentials.';
            
            case 'INVALID_SYMBOL':
                return 'Trading symbol is invalid or not available on this exchange.';
            
            case 'POSITION_LIMIT':
                return 'Maximum position limit reached. Please close existing positions before opening new ones.';
            
            default:
                // For unknown errors, return sanitized version (don't expose internal details)
                if (config('app.debug')) {
                    return $originalMessage;
                }
                return 'Trade execution failed. Please try again or contact support if the issue persists.';
        }
    }

    /**
     * Track execution failure for circuit breaker
     * 
     * @param ExchangeConnection $connection
     * @return void
     */
    protected function trackFailure(ExchangeConnection $connection): void
    {
        $cacheKey = "execution_failures:{$connection->id}";
        $failureCount = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $failureCount + 1, 3600); // Store for 1 hour
    }

    /**
     * Reset failure count on successful execution
     * 
     * @param ExchangeConnection $connection
     * @return void
     */
    protected function resetFailureCount(ExchangeConnection $connection): void
    {
        $cacheKey = "execution_failures:{$connection->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Check if circuit breaker should halt trading
     * 
     * @param ExchangeConnection $connection
     * @return array ['should_halt' => bool, 'failure_count' => int, 'max_failures' => int]
     */
    protected function checkCircuitBreaker(ExchangeConnection $connection): array
    {
        $circuitBreakerEnabled = $connection->circuit_breaker_enabled ?? true;
        $maxConsecutiveFailures = $connection->max_consecutive_failures ?? 5;
        
        if (!$circuitBreakerEnabled) {
            return [
                'should_halt' => false,
                'failure_count' => 0,
                'max_failures' => $maxConsecutiveFailures,
            ];
        }
        
        $cacheKey = "execution_failures:{$connection->id}";
        $failureCount = Cache::get($cacheKey, 0);
        
        return [
            'should_halt' => $failureCount >= $maxConsecutiveFailures,
            'failure_count' => $failureCount,
            'max_failures' => $maxConsecutiveFailures,
        ];
    }
}
