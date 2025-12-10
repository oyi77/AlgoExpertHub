<?php

namespace Addons\TradingManagement\Modules\TradingBot\Workers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\PositionMonitoringService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ProcessSignalBasedBotWorker
 * 
 * Worker for SIGNAL_BASED bots
 * Listens for published signals, validates, executes trades
 */
class ProcessSignalBasedBotWorker
{
    protected TradingBot $bot;
    protected PositionMonitoringService $positionService;

    public function __construct(TradingBot $bot)
    {
        $this->bot = $bot;
        $this->positionService = app(PositionMonitoringService::class);
    }

    /**
     * Run one iteration of the worker
     */
    public function run(): void
    {
        // 1. Monitor existing positions (check SL/TP)
        $this->monitorPositions();

        // 2. Check for new published signals
        $this->listenForSignals();
    }

    /**
     * Monitor open positions
     */
    protected function monitorPositions(): void
    {
        try {
            $result = $this->positionService->monitorPositions($this->bot);
            
            if ($result['sl_closed'] > 0 || $result['tp_closed'] > 0) {
                Log::info('Trading bot positions closed', [
                    'bot_id' => $this->bot->id,
                    'sl_closed' => $result['sl_closed'],
                    'tp_closed' => $result['tp_closed'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to monitor positions', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Listen for new published signals
     */
    protected function listenForSignals(): void
    {
        try {
            // Get signals published since last check
            // Check signals from last 10 minutes to catch any missed by observer
            $signals = Signal::where('is_published', 1)
                ->whereNotNull('published_date')
                ->where('published_date', '>=', now()->subMinutes(10))
                ->with(['pair', 'time', 'market'])
                ->get();

            foreach ($signals as $signal) {
                // Skip if already executed by this bot
                $alreadyExecuted = DB::table('trading_bot_positions')
                    ->where('bot_id', $this->bot->id)
                    ->where('signal_id', $signal->id)
                    ->exists();

                if ($alreadyExecuted) {
                    continue;
                }

                // Validate signal matches bot criteria
                if ($this->validateSignal($signal)) {
                    $this->executeSignal($signal);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to listen for signals', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Validate signal matches bot criteria
     * 
     * @param Signal $signal
     * @return bool
     */
    protected function validateSignal(Signal $signal): bool
    {
        if ($this->bot->symbol && isset($signal->pair->name)) {
            if (strtolower($this->bot->symbol) !== strtolower($signal->pair->name)) {
                return false;
            }
        }

        if ($this->bot->timeframe && isset($signal->time->name)) {
            if (strtolower($this->bot->timeframe) !== strtolower($signal->time->name)) {
                return false;
            }
        }

        if ($this->bot->filterStrategy && $this->bot->filterStrategy->enabled) {
            try {
                $evaluator = app(\Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator::class);
                $connection = $this->bot->exchangeConnection;
                $result = $evaluator->evaluate(
                    $this->bot->filterStrategy,
                    $signal->pair->name ?? $signal->currency_pair_id,
                    $signal->time->name ?? $signal->time_frame_id,
                    $connection
                );
                if (!($result['pass'] ?? false)) {
                    return false;
                }
            } catch (\Exception $e) {
                Log::warning('Filter strategy evaluation failed', [
                    'bot_id' => $this->bot->id,
                    'signal_id' => $signal->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Execute trade from signal
     * 
     * @param Signal $signal
     */
    protected function executeSignal(Signal $signal): void
    {
        try {
            // Double-check if already executed this signal (race condition protection)
            $existingPosition = DB::table('trading_bot_positions')
                ->where('bot_id', $this->bot->id)
                ->where('signal_id', $signal->id)
                ->where('status', 'open')
                ->first();

            if ($existingPosition) {
                Log::debug('Signal already executed by bot', [
                    'bot_id' => $this->bot->id,
                    'signal_id' => $signal->id,
                    'position_id' => $existingPosition->id,
                ]);
                return; // Already executed
            }

            // Verify bot still has active connection
            if (!$this->bot->exchangeConnection || !$this->bot->exchangeConnection->is_active) {
                Log::warning('Cannot execute signal - exchange connection not active', [
                    'bot_id' => $this->bot->id,
                    'signal_id' => $signal->id,
                ]);
                return;
            }

            // Determine direction
            $direction = in_array($signal->direction, ['buy', 'long']) ? 'buy' : 'sell';

            // Get trading preset for position sizing
            $preset = $this->bot->tradingPreset;
            $quantity = $preset ? $this->calculatePositionSize($preset, $signal) : 0.01;

            if ($quantity <= 0) {
                Log::warning('Invalid position size calculated', [
                    'bot_id' => $this->bot->id,
                    'signal_id' => $signal->id,
                    'quantity' => $quantity,
                ]);
                return;
            }

            // Prepare execution data
            $executionData = [
                'connection_id' => $this->bot->exchangeConnection->id,
                'bot_id' => $this->bot->id,
                'signal_id' => $signal->id,
                'symbol' => $signal->pair->name ?? 'UNKNOWN',
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $signal->open_price,
                'stop_loss' => $signal->sl,
                'take_profit' => $signal->tp,
            ];

            // Dispatch execution job (creates both ExecutionPosition and TradingBotPosition)
            \Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob::dispatch($executionData);

            Log::info('Trading bot signal execution dispatched', [
                'bot_id' => $this->bot->id,
                'bot_name' => $this->bot->name,
                'signal_id' => $signal->id,
                'direction' => $direction,
                'quantity' => $quantity,
                'symbol' => $executionData['symbol'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to execute signal', [
                'bot_id' => $this->bot->id,
                'signal_id' => $signal->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Calculate position size from trading preset
     * 
     * @param mixed $preset Trading preset or null
     * @param Signal $signal
     * @return float
     */
    protected function calculatePositionSize($preset, Signal $signal): float
    {
        if (!$preset) {
            return 0.01; // Default minimum
        }

        // Get position sizing strategy from preset
        $strategy = $preset->position_sizing_strategy ?? 'fixed';
        $value = $preset->position_sizing_value ?? 0.01;

        switch ($strategy) {
            case 'fixed':
                return (float) $value;
            
            case 'percentage':
                // Would need account balance from exchange
                // For now, use fixed fallback
                return 0.01;
            
            case 'fixed_amount':
                // Fixed dollar amount
                $entryPrice = $signal->open_price ?? 1;
                return $entryPrice > 0 ? ($value / $entryPrice) : 0.01;
            
            default:
                return 0.01;
        }
    }
}
