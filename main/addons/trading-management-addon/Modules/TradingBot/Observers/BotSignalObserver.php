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

                // TODO: AI confirmation check (if bot has AI profile)
                // For now, skip if filter passes

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
