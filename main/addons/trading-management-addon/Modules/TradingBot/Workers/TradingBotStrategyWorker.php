<?php

namespace Addons\TradingManagement\Modules\TradingBot\Workers;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TechnicalAnalysisService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradeDecisionEngine;
use Addons\TradingManagement\Modules\TradingBot\Jobs\FilterAnalysisJob;
use Addons\TradingManagement\Modules\DataProvider\Services\SharedStreamManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * TradingBotStrategyWorker
 * 
 * Watches assigned trading bot, consumes streamed data, applies technical analysis,
 * and makes trading decisions
 */
class TradingBotStrategyWorker
{
    protected TradingBot $bot;
    protected TechnicalAnalysisService $analysisService;
    protected TradeDecisionEngine $decisionEngine;
    protected SharedStreamManager $streamManager;
    protected array $subscribedStreams = [];

    public function __construct(TradingBot $bot)
    {
        $this->bot = $bot;
        $this->analysisService = app(TechnicalAnalysisService::class);
        $this->decisionEngine = app(TradeDecisionEngine::class);
        $this->streamManager = app(SharedStreamManager::class);
    }

    /**
     * Run one iteration of the worker
     */
    public function run(): void
    {
        // 1. Ensure subscriptions to required streams
        $this->ensureSubscriptions();

        // 2. Consume streamed data
        $marketData = $this->consumeStreamedData();

        if (empty($marketData)) {
            return; // No data available yet
        }

        // 3. Check if bot has Expert Advisor - if yes, use EA instead of technical analysis
        if ($this->bot->expert_advisor_id && $this->bot->expertAdvisor) {
            $eaService = app(\Addons\TradingManagement\Modules\ExpertAdvisor\Services\EaExecutionService::class);
            $eaResult = $eaService->executeEa($this->bot->expertAdvisor, $this->bot, $marketData);
            
            if ($eaResult['signal'] !== 'hold') {
                // EA provided signal, use it
                $decision = [
                    'should_enter' => true,
                    'direction' => $eaResult['signal'],
                    'confidence' => $eaResult['confidence'] ?? 0.8,
                    'entry_price' => $eaResult['entry_price'] ?? $marketData[0]['close'] ?? 0,
                    'stop_loss' => $eaResult['sl'],
                    'take_profit' => $eaResult['tp'],
                ];
            } else {
                // EA says hold, skip
                return;
            }
        } else {
            // 3. Apply technical analysis (if no EA)
            $indicators = $this->analysisService->calculateIndicators($marketData, $this->bot->filterStrategy);
            $analysis = $this->analysisService->analyzeSignals($indicators);

            // 4. Make trading decision
            $decision = $this->decisionEngine->shouldEnterTrade($analysis, $this->bot);
        }

        // 5. If decision to trade, dispatch to Filter & Analysis Worker
        if ($decision['should_enter']) {
            FilterAnalysisJob::dispatch($this->bot, $decision, $marketData);
            
            Log::info('Trading decision made, dispatched to filter analysis', [
                'bot_id' => $this->bot->id,
                'direction' => $decision['direction'],
                'confidence' => $decision['confidence'],
            ]);
        }
    }

    /**
     * Ensure bot is subscribed to required streams
     */
    protected function ensureSubscriptions(): void
    {
        if ($this->bot->trading_mode !== 'MARKET_STREAM_BASED') {
            return; // Only for market stream based bots
        }

        $symbols = $this->bot->getStreamingSymbols();
        $timeframes = $this->bot->getStreamingTimeframes();

        if (empty($symbols) || empty($timeframes)) {
            return;
        }

        // Get data connection to find account_id
        $dataConnection = $this->bot->dataConnection;
        if (!$dataConnection) {
            return;
        }

        $credentials = $dataConnection->credentials ?? [];
        $accountId = $credentials['account_id'] ?? null;

        if (!$accountId) {
            return;
        }

        // Subscribe to each symbol/timeframe combination
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                $this->subscribeToStream($accountId, $symbol, $timeframe);
            }
        }
    }

    /**
     * Subscribe to a stream
     */
    protected function subscribeToStream(string $accountId, string $symbol, string $timeframe): void
    {
        $streamKey = "{$accountId}:{$symbol}:{$timeframe}";
        
        if (isset($this->subscribedStreams[$streamKey])) {
            return; // Already subscribed
        }

        try {
            // Get or create stream
            $stream = $this->streamManager->getOrCreateStream($accountId, $symbol, $timeframe);
            
            // Subscribe bot to stream
            $this->streamManager->subscribe($stream->id, 'bot', $this->bot->id);
            
            $this->subscribedStreams[$streamKey] = $stream->id;
            
            Log::info('Bot subscribed to stream', [
                'bot_id' => $this->bot->id,
                'stream_id' => $stream->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to subscribe bot to stream', [
                'bot_id' => $this->bot->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consume streamed data from Redis
     */
    protected function consumeStreamedData(): array
    {
        $dataConnection = $this->bot->dataConnection;
        if (!$dataConnection) {
            return [];
        }

        $credentials = $dataConnection->credentials ?? [];
        $accountId = $credentials['account_id'] ?? null;

        if (!$accountId) {
            return [];
        }

        $symbols = $this->bot->getStreamingSymbols();
        $timeframes = $this->bot->getStreamingTimeframes();

        if (empty($symbols) || empty($timeframes)) {
            return [];
        }

        $redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
        $ohlcvData = [];

        // Get latest data for each symbol/timeframe
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                $cacheKey = sprintf('%s:%s:%s:%s', $redisPrefix, $accountId, $symbol, $timeframe);
                
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    $data = json_decode($cached, true);
                    if ($data) {
                        // MetaAPI candle format: MetatraderCandle object
                        // Contains: symbol, timeframe, time, brokerTime, open, high, low, close, tickVolume, spread, volume
                        if (isset($data['open']) && isset($data['high']) && isset($data['low']) && isset($data['close'])) {
                            // Convert time to timestamp (ISO 8601 string or Date object)
                            $timestamp = time() * 1000; // Default to now
                            if (isset($data['time'])) {
                                if (is_string($data['time'])) {
                                    $timestamp = strtotime($data['time']) * 1000;
                                } elseif (is_numeric($data['time'])) {
                                    $timestamp = $data['time'] < 10000000000 ? $data['time'] * 1000 : $data['time'];
                                }
                            }
                            
                            // Convert to OHLCV format
                            $ohlcvData[] = [
                                'timestamp' => $timestamp,
                                'open' => (float) ($data['open'] ?? 0),
                                'high' => (float) ($data['high'] ?? 0),
                                'low' => (float) ($data['low'] ?? 0),
                                'close' => (float) ($data['close'] ?? 0),
                                'volume' => (int) ($data['volume'] ?? $data['tickVolume'] ?? 0),
                                'symbol' => $data['symbol'] ?? $symbol,
                                'timeframe' => $data['timeframe'] ?? $timeframe,
                            ];
                        }
                    }
                }
            }
        }

        return $ohlcvData;
    }

    /**
     * Cleanup subscriptions when bot stops
     */
    public function cleanup(): void
    {
        foreach ($this->subscribedStreams as $streamId) {
            try {
                $this->streamManager->unsubscribe($streamId, 'bot', $this->bot->id);
            } catch (\Exception $e) {
                Log::warning('Failed to unsubscribe bot from stream', [
                    'bot_id' => $this->bot->id,
                    'stream_id' => $streamId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->subscribedStreams = [];
    }
}
