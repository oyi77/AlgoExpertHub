<?php

namespace Addons\TradingManagement\Modules\TradingBot\Workers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\TradingBot\Services\TechnicalAnalysisService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradeDecisionEngine;
use Addons\TradingManagement\Modules\TradingBot\Services\PositionMonitoringService;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
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

    public function __construct(TradingBot $bot)
    {
        $this->bot = $bot;
        $this->analysisService = app(TechnicalAnalysisService::class);
        $this->decisionEngine = app(TradeDecisionEngine::class);
        $this->positionService = app(PositionMonitoringService::class);
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
     */
    protected function analyzeMarket(): void
    {
        try {
            // 1. Stream market data
            $ohlcv = $this->streamMarketData();
            
            if (empty($ohlcv)) {
                Log::warning('No market data received', ['bot_id' => $this->bot->id]);
                return;
            }

            // 2. Calculate technical indicators
            $indicators = $this->analysisService->calculateIndicators($ohlcv, $this->bot->filterStrategy);
            
            // 3. Analyze signals
            $analysis = $this->analysisService->analyzeSignals($indicators);
            
            // 4. Make trading decision
            $decision = $this->decisionEngine->shouldEnterTrade($analysis, $this->bot);
            
            // 5. Execute trade if conditions met
            if ($decision['should_enter']) {
                $this->executeTrade($decision, $ohlcv);
            }

            // Update last analysis time
            $this->bot->update(['last_market_analysis_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze market', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stream market data from data connection
     * 
     * @return array OHLCV candles
     */
    protected function streamMarketData(): array
    {
        if (!$this->bot->dataConnection) {
            return [];
        }

        $symbols = $this->bot->getStreamingSymbols();
        $timeframes = $this->bot->getStreamingTimeframes();

        if (empty($symbols) || empty($timeframes)) {
            return [];
        }

        // Use first symbol and timeframe for now
        $symbol = $symbols[0];
        $timeframe = $timeframes[0];

        try {
            $connection = $this->bot->dataConnection;
            $credentials = $connection->credentials ?? [];
            $provider = $connection->provider;
            $adapter = new CcxtAdapter($credentials, $provider);
            $result = $adapter->fetchCandles($symbol, $timeframe, 100);
            if (!isset($result['success']) || !$result['success']) {
                return [];
            }
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to stream market data', [
                'bot_id' => $this->bot->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Execute trade based on decision
     * 
     * @param array $decision
     * @param array $ohlcv
     */
    protected function executeTrade(array $decision, array $ohlcv): void
    {
        try {
            // Get current price
            $currentPrice = end($ohlcv)['close'] ?? null;
            if (!$currentPrice) {
                return;
            }

            // Calculate position size
            $quantity = $this->decisionEngine->calculatePositionSize($this->bot, null, $currentPrice);
            
            // Apply risk management
            $decision = $this->decisionEngine->applyRiskManagement($decision, $this->bot, $currentPrice);

            $symbol = $this->bot->getStreamingSymbols()[0] ?? null;
            if (!$symbol) {
                return;
            }

            TradingBotPosition::create([
                'bot_id' => $this->bot->id,
                'symbol' => $symbol,
                'direction' => $decision['direction'],
                'entry_price' => $currentPrice,
                'current_price' => $currentPrice,
                'stop_loss' => $decision['stop_loss'] ?? null,
                'take_profit' => $decision['take_profit'] ?? null,
                'quantity' => $quantity,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            Log::info('Trading bot trade executed', [
                'bot_id' => $this->bot->id,
                'direction' => $decision['direction'],
                'quantity' => $quantity,
                'price' => $currentPrice,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to execute trade', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
