<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * BotExecutionService
 * 
 * Handles signal execution through trading bots
 * Integrates filter strategies and AI confirmation
 */
class BotExecutionService
{
    protected FilterStrategyEvaluator $filterEvaluator;

    public function __construct(FilterStrategyEvaluator $filterEvaluator)
    {
        $this->filterEvaluator = $filterEvaluator;
    }

    /**
     * Get active bots for a signal
     * 
     * @param Signal $signal
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBotsForSignal(Signal $signal)
    {
        // Get all active bots
        $bots = TradingBot::active()
            ->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->get();

        // Filter bots that match signal criteria
        $eligibleBots = $bots->filter(function ($bot) use ($signal) {
            // Check if bot's connection exists and is active
            if (!$bot->exchangeConnection) {
                return false;
            }

            // Check connection status (handle both old and new connection models)
            $isActive = $bot->exchangeConnection->is_active ?? 
                       ($bot->exchangeConnection->status === 'active') ?? 
                       false;
            
            if (!$isActive) {
                return false;
            }

            // Check if bot's preset is enabled
            if (!$bot->tradingPreset || !$bot->tradingPreset->enabled) {
                return false;
            }

            // Check if connection has trade execution enabled (for new model)
            if (isset($bot->exchangeConnection->trade_execution_enabled) && 
                !$bot->exchangeConnection->trade_execution_enabled) {
                return false;
            }

            if ($bot->symbol && isset($signal->pair->name)) {
                if (strtolower($bot->symbol) !== strtolower($signal->pair->name)) {
                    return false;
                }
            }

            if ($bot->timeframe && isset($signal->time->name)) {
                if (strtolower($bot->timeframe) !== strtolower($signal->time->name)) {
                    return false;
                }
            }

            return true;
        });

        return $eligibleBots;
    }

    /**
     * Check if signal passes bot's filter strategy
     * 
     * @param Signal $signal
     * @param TradingBot $bot
     * @return array ['pass' => bool, 'reason' => string]
     */
    public function evaluateBotFilter(Signal $signal, TradingBot $bot): array
    {
        // If no filter strategy, always pass
        if (!$bot->filterStrategy || !$bot->filterStrategy->enabled) {
            return ['pass' => true, 'reason' => 'No filter strategy assigned'];
        }

        try {
            // Get connection for market data
            $connection = $bot->exchangeConnection;
            
            // Evaluate filter strategy
            $result = $this->filterEvaluator->evaluate(
                $bot->filterStrategy,
                $signal->pair->name ?? $signal->currency_pair_id,
                $signal->time->name ?? $signal->time_frame_id,
                $connection
            );

            return [
                'pass' => $result['pass'] ?? false,
                'reason' => $result['reason'] ?? 'Filter evaluation completed',
                'indicators' => $result['indicators'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Bot filter evaluation failed', [
                'bot_id' => $bot->id,
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);

            // On error, fail safe (don't execute)
            return [
                'pass' => false,
                'reason' => 'Filter evaluation error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if bot is in paper trading mode
     * 
     * @param TradingBot $bot
     * @return bool
     */
    public function isPaperTrading(TradingBot $bot): bool
    {
        // Bot-level paper trading flag takes priority
        if ($bot->is_paper_trading) {
            return true;
        }

        // Check connection-level paper trading (if exists)
        if ($bot->exchangeConnection) {
            // For new ExchangeConnection model
            if (isset($bot->exchangeConnection->execution_settings['is_paper_trading'])) {
                return (bool) $bot->exchangeConnection->execution_settings['is_paper_trading'];
            }
            
            // For old ExecutionConnection model (trading-execution-engine-addon)
            if (isset($bot->exchangeConnection->is_paper_trading)) {
                return (bool) $bot->exchangeConnection->is_paper_trading;
            }
        }

        return false;
    }

    /**
     * Execute signal through bot
     * 
     * This is called from SignalExecutionService after filter/AI checks
     * 
     * @param Signal $signal
     * @param TradingBot $bot
     * @param array $options
     * @return array
     */
    public function executeThroughBot(Signal $signal, TradingBot $bot, array $options = []): array
    {
        // Check if paper trading
        if ($this->isPaperTrading($bot)) {
            // Simulate execution (log only, no real order)
            return $this->simulateExecution($signal, $bot, $options);
        }

        // Real execution via SignalExecutionService
        // This will be handled by the existing execution flow
        // We just need to pass the bot_id in options
        $options['trading_bot_id'] = $bot->id;
        
        // The actual execution is done by SignalExecutionService
        // This method is mainly for paper trading simulation
        return [
            'success' => true,
            'message' => 'Execution delegated to SignalExecutionService',
            'trading_bot_id' => $bot->id,
        ];
    }

    /**
     * Simulate execution for paper trading
     * 
     * @param Signal $signal
     * @param TradingBot $bot
     * @param array $options
     * @return array
     */
    protected function simulateExecution(Signal $signal, TradingBot $bot, array $options = []): array
    {
        // Calculate position size using preset
        $preset = $bot->tradingPreset;
        $connection = $bot->exchangeConnection;

        // Simulate order placement
        $orderId = 'PAPER_' . time() . '_' . rand(1000, 9999);
        
        Log::info('Paper trading execution simulated', [
            'bot_id' => $bot->id,
            'signal_id' => $signal->id,
            'order_id' => $orderId,
            'connection' => $connection->name,
        ]);

        // Create execution log with paper trading flag
        // This will be handled by SignalExecutionService with paper trading mode
        return [
            'success' => true,
            'execution_log_id' => null, // Will be created by execution service
            'position_id' => null, // Will be created by execution service
            'message' => 'Paper trading execution simulated',
            'order_id' => $orderId,
            'trading_bot_id' => $bot->id,
            'is_paper_trading' => true,
        ];
    }

    /**
     * Update bot statistics after execution
     * 
     * @param TradingBot $bot
     * @param bool $success
     * @param float|null $profit
     * @return void
     */
    public function updateBotStatistics(TradingBot $bot, bool $success, ?float $profit = null): void
    {
        $bot->increment('total_executions');
        
        if ($success) {
            $bot->increment('successful_executions');
        } else {
            $bot->increment('failed_executions');
        }

        if ($profit !== null) {
            $bot->increment('total_profit', $profit);
        }

        // Recalculate win rate
        if ($bot->total_executions > 0) {
            $bot->win_rate = ($bot->successful_executions / $bot->total_executions) * 100;
        }

        $bot->save();
    }
}
