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
            // This would ideally use events, but for polling we check recent signals
            $signals = Signal::where('is_published', 1)
                ->where('published_date', '>=', now()->subMinutes(5))
                ->get();

            foreach ($signals as $signal) {
                // Validate signal matches bot criteria
                if ($this->validateSignal($signal)) {
                    $this->executeSignal($signal);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to listen for signals', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
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
            // Check if already executed this signal
            $existingPosition = DB::table('trading_bot_positions')
                ->where('bot_id', $this->bot->id)
                ->where('signal_id', $signal->id)
                ->where('status', 'open')
                ->first();

            if ($existingPosition) {
                return; // Already executed
            }

            // Determine direction
            $direction = in_array($signal->direction, ['buy', 'long']) ? 'buy' : 'sell';

            // Calculate position size (would use trading preset)
            $quantity = 0.01; // Placeholder

            // Place order via execution engine (delegated)

            // Create position record
            DB::table('trading_bot_positions')->insert([
                'bot_id' => $this->bot->id,
                'signal_id' => $signal->id,
                'symbol' => $signal->pair->name ?? 'UNKNOWN',
                'direction' => $direction,
                'entry_price' => $signal->open_price,
                'current_price' => $signal->open_price,
                'stop_loss' => $signal->sl,
                'take_profit' => $signal->tp,
                'quantity' => $quantity,
                'status' => 'open',
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Trading bot signal executed', [
                'bot_id' => $this->bot->id,
                'signal_id' => $signal->id,
                'direction' => $direction,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to execute signal', [
                'bot_id' => $this->bot->id,
                'signal_id' => $signal->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
