<?php

namespace Addons\TradingManagement\Modules\TradingBot\Observers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\BotExecutionService;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * BotSignalObserver
 * 
 * Handles signal execution through trading bots
 * Works alongside SignalObserver (connection-based execution)
 */
class BotSignalObserver
{
    protected BotExecutionService $botExecutionService;

    public function __construct(BotExecutionService $botExecutionService)
    {
        $this->botExecutionService = $botExecutionService;
    }

    /**
     * Handle the Signal "updated" event.
     */
    public function updated(Signal $signal): void
    {
        // Check if signal was just published
        if ($signal->is_published && $signal->wasChanged('is_published')) {
            $this->handleSignalPublished($signal);
        }
    }

    /**
     * Handle signal published - execute through active bots
     */
    protected function handleSignalPublished(Signal $signal): void
    {
        try {
            // Get all active bots
            $bots = $this->botExecutionService->getActiveBotsForSignal($signal);

            foreach ($bots as $bot) {
                // Evaluate bot's filter strategy
                $filterResult = $this->botExecutionService->evaluateBotFilter($signal, $bot);
                
                if (!$filterResult['pass']) {
                    Log::info('Bot filter rejected signal', [
                        'bot_id' => $bot->id,
                        'bot_name' => $bot->name,
                        'signal_id' => $signal->id,
                        'reason' => $filterResult['reason'],
                    ]);
                    continue;
                }

                if ($bot->aiModelProfile && $bot->aiModelProfile->enabled) {
                    try {
                        $aiAnalysisService = app(\Addons\AiTradingAddon\App\Services\MarketAnalysisAiService::class);
                        $decisionEngine = app(\Addons\AiTradingAddon\App\Services\AiDecisionEngine::class);

                        $aiResult = $aiAnalysisService->analyzeSignal([
                            'pair' => $signal->pair->name ?? null,
                            'timeframe' => $signal->time->name ?? null,
                            'direction' => $signal->direction ?? null,
                            'open_price' => $signal->open_price ?? null,
                            'take_profit' => $signal->tp ?? null,
                            'stop_loss' => $signal->sl ?? null,
                        ], $bot->aiModelProfile);

                        $decision = $decisionEngine->makeDecision($aiResult, $bot->tradingPreset);

                        if (!($decision['execute'] ?? false)) {
                            Log::info('AI decision rejected signal', [
                                'bot_id' => $bot->id,
                                'signal_id' => $signal->id,
                                'reason' => $decision['reason'] ?? 'AI reject',
                            ]);
                            continue;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AI decision check failed, proceeding', [
                            'bot_id' => $bot->id,
                            'signal_id' => $signal->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Check if already executed this signal
                $existingPosition = DB::table('trading_bot_positions')
                    ->where('bot_id', $bot->id)
                    ->where('signal_id', $signal->id)
                    ->where('status', 'open')
                    ->first();

                if ($existingPosition) {
                    Log::info('Bot already executed this signal', [
                        'bot_id' => $bot->id,
                        'signal_id' => $signal->id,
                    ]);
                    continue; // Already executed
                }

                // Determine direction
                $direction = in_array($signal->direction, ['buy', 'long']) ? 'buy' : 'sell';

                // Calculate position size from trading preset
                $preset = $bot->tradingPreset;
                $quantity = $this->calculatePositionSize($preset, $signal);

                // Prepare execution data for new ExecutionJob
                $executionData = [
                    'connection_id' => $bot->exchange_connection_id,
                    'bot_id' => $bot->id,
                    'signal_id' => $signal->id,
                    'symbol' => $signal->pair->name ?? 'UNKNOWN',
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'entry_price' => $signal->open_price,
                    'stop_loss' => $signal->sl,
                    'take_profit' => $signal->tp,
                ];

                // Dispatch new ExecutionJob (creates both ExecutionPosition and TradingBotPosition)
                ExecutionJob::dispatch($executionData);

                Log::info('Trading bot signal execution dispatched', [
                    'bot_id' => $bot->id,
                    'signal_id' => $signal->id,
                    'direction' => $direction,
                    'quantity' => $quantity,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Bot signal observer error", [
                'error' => $e->getMessage(),
                'signal_id' => $signal->id,
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
