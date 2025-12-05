<?php

namespace Addons\TradingManagement\Modules\TradingBot\Observers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\BotExecutionService;
use Addons\TradingExecutionEngine\App\Jobs\ExecuteSignalJob;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

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

                // Execute through bot
                // Use existing ExecuteSignalJob but pass bot_id in options
                $options = [
                    'trading_bot_id' => $bot->id,
                    'is_paper_trading' => $this->botExecutionService->isPaperTrading($bot),
                ];

                // Dispatch job with bot context
                ExecuteSignalJob::dispatch($signal, $bot->exchange_connection_id, $options);
            }
        } catch (\Exception $e) {
            Log::error("Bot signal observer error", [
                'error' => $e->getMessage(),
                'signal_id' => $signal->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
