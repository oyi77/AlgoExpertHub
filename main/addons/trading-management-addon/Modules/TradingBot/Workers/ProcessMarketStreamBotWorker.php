<?php

namespace Addons\TradingManagement\Modules\TradingBot\Workers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\TradingBot\Services\TechnicalAnalysisService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradeDecisionEngine;
use Addons\TradingManagement\Modules\TradingBot\Services\PositionMonitoringService;
use Addons\TradingManagement\Modules\TradingBot\Workers\TradingBotStrategyWorker;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMarketStreamBotWorker
 * 
 * Worker for MARKET_STREAM_BASED bots
 * Continuously streams OHLCV, analyzes, makes decisions, executes trades
 */
class ProcessMarketStreamBotWorker
{
    protected TradingBot $bot;
    protected TechnicalAnalysisService $analysisService;
    protected TradeDecisionEngine $decisionEngine;
    protected PositionMonitoringService $positionService;
    protected TradingBotStrategyWorker $strategyWorker;

    public function __construct(TradingBot $bot)
    {
        $this->bot = $bot;
        $this->analysisService = app(TechnicalAnalysisService::class);
        $this->decisionEngine = app(TradeDecisionEngine::class);
        $this->positionService = app(PositionMonitoringService::class);
        $this->strategyWorker = new TradingBotStrategyWorker($bot);
    }

    /**
     * Run one iteration of the worker
     */
    public function run(): void
    {
        // 1. Monitor existing positions (check SL/TP)
        $this->monitorPositions();

        // 2. Check if it's time for market analysis
        if ($this->shouldAnalyzeMarket()) {
            $this->analyzeMarket();
        }
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
     * Check if should analyze market (based on interval)
     */
    protected function shouldAnalyzeMarket(): bool
    {
        $interval = $this->bot->market_analysis_interval ?? 60;
        $lastAnalysis = $this->bot->last_market_analysis_at;

        if (!$lastAnalysis) {
            return true;
        }

        return now()->diffInSeconds($lastAnalysis) >= $interval;
    }

    /**
     * Analyze market and make trading decisions
     * 
     * Now uses TradingBotStrategyWorker which consumes from shared streams
     */
    protected function analyzeMarket(): void
    {
        try {
            // Use strategy worker which handles:
            // - Subscribing to shared streams
            // - Consuming streamed data from Redis
            // - Applying technical analysis
            // - Making trading decisions
            // - Dispatching to Filter & Analysis Worker
            $this->strategyWorker->run();

            // Update last analysis time
            $this->bot->update(['last_market_analysis_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze market', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
