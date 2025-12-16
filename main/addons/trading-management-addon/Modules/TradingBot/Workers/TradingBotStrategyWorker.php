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
        $startTime = microtime(true);
        
        Log::info('TradingBotStrategyWorker: Starting analysis', [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'trading_mode' => $this->bot->trading_mode,
        ]);

        // 1. Ensure subscriptions to required streams
        $this->ensureSubscriptions();

        // 1.5. Check stream health before consuming data
        $streamHealth = $this->checkStreamHealth();
        if (!$streamHealth['healthy']) {
            Log::warning('TradingBotStrategyWorker: Stream health check failed', [
                'bot_id' => $this->bot->id,
                'health_status' => $streamHealth,
            ]);
            
            // Try to recover using SDK fetch
            if ($streamHealth['can_recover']) {
                $this->recoverWithSdkFetch();
            }
        }

        // 2. Consume streamed data
        $marketData = $this->consumeStreamedData();

        Log::info('TradingBotStrategyWorker: Market data consumed', [
            'bot_id' => $this->bot->id,
            'data_count' => count($marketData),
            'has_data' => !empty($marketData),
            'symbols' => $this->bot->getStreamingSymbols(),
            'timeframes' => $this->bot->getStreamingTimeframes(),
        ]);

        if (empty($marketData)) {
            Log::warning('TradingBotStrategyWorker: No market data available', [
                'bot_id' => $this->bot->id,
                'data_connection_id' => $this->bot->data_connection_id,
                'symbols' => $this->bot->getStreamingSymbols(),
                'timeframes' => $this->bot->getStreamingTimeframes(),
            ]);
            
            // Try to recover using SDK fetch as fallback
            $this->recoverWithSdkFetch();
            
            // Try consuming again after recovery
            $marketData = $this->consumeStreamedData();
            
            if (empty($marketData)) {
                return; // Still no data available
            }
        }

        // 3. Check if bot has Expert Advisor - if yes, use EA instead of technical analysis
        if ($this->bot->expert_advisor_id && $this->bot->expertAdvisor) {
            Log::info('TradingBotStrategyWorker: Using Expert Advisor', [
                'bot_id' => $this->bot->id,
                'ea_id' => $this->bot->expert_advisor_id,
            ]);

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
                Log::info('TradingBotStrategyWorker: EA says hold', [
                    'bot_id' => $this->bot->id,
                ]);
                return;
            }
        } else {
            // 3. Apply technical analysis (if no EA)
            Log::info('TradingBotStrategyWorker: Applying technical analysis', [
                'bot_id' => $this->bot->id,
                'has_filter_strategy' => !is_null($this->bot->filterStrategy),
                'filter_strategy_id' => $this->bot->filter_strategy_id,
            ]);

            $indicators = $this->analysisService->calculateIndicators($marketData, $this->bot->filterStrategy);

            Log::info('TradingBotStrategyWorker: Indicators calculated', [
                'bot_id' => $this->bot->id,
                'indicators' => array_keys($indicators),
                'indicator_count' => count($indicators),
            ]);

            $analysis = $this->analysisService->analyzeSignals($indicators, $marketData);

            Log::info('TradingBotStrategyWorker: Signal analysis completed', [
                'bot_id' => $this->bot->id,
                'signal' => $analysis['signal'] ?? 'unknown',
                'strength' => $analysis['strength'] ?? 0,
                'reason' => $analysis['reason'] ?? 'no reason',
            ]);

            // 4. Make trading decision
            $decision = $this->decisionEngine->shouldEnterTrade($analysis, $this->bot);

            Log::info('TradingBotStrategyWorker: Trading decision made', [
                'bot_id' => $this->bot->id,
                'should_enter' => $decision['should_enter'] ?? false,
                'direction' => $decision['direction'] ?? null,
                'confidence' => $decision['confidence'] ?? 0,
                'reason' => $decision['reason'] ?? 'no reason',
            ]);

            // Apply risk management (SL/TP) if not already set
            if ($decision['should_enter'] && (!isset($decision['stop_loss']) || !isset($decision['take_profit']))) {
                $entryPrice = $marketData[0]['close'] ?? 0;
                if ($entryPrice > 0) {
                    $decision = $this->decisionEngine->applyRiskManagement($decision, $this->bot, $entryPrice);

                    Log::info('TradingBotStrategyWorker: Risk management applied', [
                        'bot_id' => $this->bot->id,
                        'entry_price' => $entryPrice,
                        'stop_loss' => $decision['stop_loss'] ?? null,
                        'take_profit' => $decision['take_profit'] ?? null,
                    ]);
                }
            }
        }

        // 5. If decision to trade, dispatch to Filter & Analysis Worker
        if ($decision['should_enter']) {
            // Ensure we have required fields
            if (!isset($decision['entry_price']) && !empty($marketData)) {
                $decision['entry_price'] = $marketData[0]['close'] ?? 0;
            }

            // Add paper trading flag to decision
            $decision['is_paper_trading'] = $this->bot->is_paper_trading ?? false;

            FilterAnalysisJob::dispatch($this->bot, $decision, $marketData);

            Log::info('TradingBotStrategyWorker: Trading decision made, dispatched to filter analysis', [
                'bot_id' => $this->bot->id,
                'direction' => $decision['direction'],
                'confidence' => $decision['confidence'],
                'entry_price' => $decision['entry_price'] ?? null,
                'stop_loss' => $decision['stop_loss'] ?? null,
                'take_profit' => $decision['take_profit'] ?? null,
                'is_paper_trading' => $decision['is_paper_trading'],
                'note' => $decision['is_paper_trading'] ? 'Paper trading mode - trade will be simulated' : 'Live trading mode - real order will be placed',
            ]);
        } else {
            Log::info('TradingBotStrategyWorker: No trade decision - not entering', [
                'bot_id' => $this->bot->id,
                'reason' => $decision['reason'] ?? 'unknown',
                'confidence' => $decision['confidence'] ?? 0,
            ]);
        }
        
        // Log performance metrics
        $executionTime = (microtime(true) - $startTime) * 1000; // milliseconds
        Log::debug('TradingBotStrategyWorker: Analysis completed', [
            'bot_id' => $this->bot->id,
            'execution_time_ms' => round($executionTime, 2),
            'data_count' => count($marketData),
        ]);
    }
    
    /**
     * Check stream health - verify data freshness and availability
     * 
     * @return array Health status with 'healthy', 'can_recover', 'issues'
     */
    protected function checkStreamHealth(): array
    {
        $dataConnection = $this->bot->dataConnection;
        if (!$dataConnection) {
            return [
                'healthy' => false,
                'can_recover' => false,
                'issues' => ['no_data_connection'],
            ];
        }

        $credentials = $dataConnection->credentials ?? [];
        $accountId = $credentials['account_id'] ?? null;
        if (!$accountId) {
            return [
                'healthy' => false,
                'can_recover' => false,
                'issues' => ['no_account_id'],
            ];
        }

        $symbols = $this->bot->getStreamingSymbols();
        $timeframes = $this->bot->getStreamingTimeframes();
        $redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
        
        $issues = [];
        $staleDataCount = 0;
        $missingDataCount = 0;
        $maxAgeMinutes = 5; // Consider data stale after 5 minutes
        
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                $candlesCacheKey = sprintf('%s:%s:%s:%s:candles', $redisPrefix, $accountId, $symbol, $timeframe);
                
                // Check if Redis has data
                $candlesList = Redis::lrange($candlesCacheKey, -1, -1); // Get latest candle
                if (empty($candlesList)) {
                    $missingDataCount++;
                    $issues[] = "no_data_{$symbol}_{$timeframe}";
                    continue;
                }
                
                // Check data freshness
                try {
                    $latestCandleJson = $candlesList[0];
                    $latestCandle = json_decode($latestCandleJson, true);
                    if ($latestCandle && isset($latestCandle['timestamp'])) {
                        $timestamp = $latestCandle['timestamp'];
                        $ageMinutes = (time() * 1000 - $timestamp) / 60000;
                        
                        if ($ageMinutes > $maxAgeMinutes) {
                            $staleDataCount++;
                            $issues[] = "stale_data_{$symbol}_{$timeframe}_{$ageMinutes}min";
                        }
                    }
                } catch (\Exception $e) {
                    $issues[] = "parse_error_{$symbol}_{$timeframe}";
                }
            }
        }
        
        $healthy = empty($issues) && $staleDataCount === 0 && $missingDataCount === 0;
        $canRecover = $missingDataCount > 0 || $staleDataCount > 0; // Can recover if data is missing or stale
        
        return [
            'healthy' => $healthy,
            'can_recover' => $canRecover,
            'issues' => $issues,
            'stale_data_count' => $staleDataCount,
            'missing_data_count' => $missingDataCount,
            'total_checks' => count($symbols) * count($timeframes),
        ];
    }
    
    /**
     * Recover missing or stale data using SDK fetch
     * 
     * @return void
     */
    protected function recoverWithSdkFetch(): void
    {
        try {
            $dataConnection = $this->bot->dataConnection;
            if (!$dataConnection) {
                return;
            }
            
            Log::info('TradingBotStrategyWorker: Attempting SDK-based data recovery', [
                'bot_id' => $this->bot->id,
            ]);
            
            $symbols = $this->bot->getStreamingSymbols();
            $timeframes = $this->bot->getStreamingTimeframes();
            
            foreach ($symbols as $symbol) {
                foreach ($timeframes as $timeframe) {
                    try {
                        // Fetch historical data via SDK (MetaApiAdapter uses SDK with fallback)
                        $fetchedCandles = $this->fetchHistoricalDataFromMetaAPI($dataConnection, $symbol, $timeframe, 200);
                        
                        if (!empty($fetchedCandles)) {
                            // Store in Redis list (same format as streaming)
                            $credentials = $dataConnection->credentials ?? [];
                            $accountId = $credentials['account_id'] ?? null;
                            if ($accountId) {
                                $redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
                                $candlesCacheKey = sprintf('%s:%s:%s:%s:candles', $redisPrefix, $accountId, $symbol, $timeframe);
                                
                                // Convert to cache format
                                $candlesToCache = [];
                                foreach ($fetchedCandles as $candle) {
                                    $timestamp = $candle['timestamp'] ?? time() * 1000;
                                    $candlesToCache[] = json_encode([
                                        'symbol' => $symbol,
                                        'timeframe' => $timeframe,
                                        'open' => $candle['open'] ?? 0,
                                        'high' => $candle['high'] ?? 0,
                                        'low' => $candle['low'] ?? 0,
                                        'close' => $candle['close'] ?? 0,
                                        'volume' => $candle['volume'] ?? 0,
                                        'tickVolume' => $candle['tick_volume'] ?? $candle['volume'] ?? 0,
                                        'time' => date('c', (int)($timestamp / 1000)),
                                        'timestamp' => (int)($timestamp < 10000000000 ? $timestamp * 1000 : $timestamp),
                                        'brokerTime' => date('c', (int)($timestamp / 1000)),
                                    ]);
                                }
                                
                                // Store in Redis
                                Redis::del($candlesCacheKey);
                                if (!empty($candlesToCache)) {
                                    Redis::rpush($candlesCacheKey, ...$candlesToCache);
                                    Redis::expire($candlesCacheKey, config('trading-management.metaapi.streaming.stream_ttl', 60));
                                    
                                    Log::info('TradingBotStrategyWorker: Recovered data via SDK', [
                                        'bot_id' => $this->bot->id,
                                        'symbol' => $symbol,
                                        'timeframe' => $timeframe,
                                        'candles_count' => count($fetchedCandles),
                                    ]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('TradingBotStrategyWorker: SDK recovery failed for symbol/timeframe', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('TradingBotStrategyWorker: SDK recovery failed', [
                'bot_id' => $this->bot->id,
                'error' => $e->getMessage(),
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
            Log::warning('TradingBotStrategyWorker: No data connection', [
                'bot_id' => $this->bot->id,
                'data_connection_id' => $this->bot->data_connection_id,
            ]);
            return [];
        }

        $credentials = $dataConnection->credentials ?? [];
        $accountId = $credentials['account_id'] ?? null;

        if (!$accountId) {
            Log::warning('TradingBotStrategyWorker: No account_id in data connection', [
                'bot_id' => $this->bot->id,
                'data_connection_id' => $dataConnection->id,
            ]);
            return [];
        }

        $symbols = $this->bot->getStreamingSymbols();
        $timeframes = $this->bot->getStreamingTimeframes();

        if (empty($symbols) || empty($timeframes)) {
            Log::warning('TradingBotStrategyWorker: No symbols or timeframes configured', [
                'bot_id' => $this->bot->id,
                'symbols' => $symbols,
                'timeframes' => $timeframes,
            ]);
            return [];
        }

        $redisPrefix = config('trading-management.metaapi.streaming.redis_prefix', 'metaapi:stream');
        $ohlcvData = [];
        $cacheKeysChecked = [];

        // Get MarketDataService for historical data
        $marketDataService = app(\Addons\TradingManagement\Modules\MarketData\Services\MarketDataService::class);

        Log::info('TradingBotStrategyWorker: Starting data consumption', [
            'bot_id' => $this->bot->id,
            'account_id' => $accountId,
            'symbols' => $symbols,
            'timeframes' => $timeframes,
            'redis_prefix' => $redisPrefix,
            'data_connection_id' => $dataConnection->id ?? null,
        ]);

        // Get latest data for each symbol/timeframe
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                Log::info('TradingBotStrategyWorker: Processing symbol/timeframe', [
                    'bot_id' => $this->bot->id,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'ohlcvData_count_before' => count($ohlcvData),
                ]);
                $cacheKey = sprintf('%s:%s:%s:%s', $redisPrefix, $accountId, $symbol, $timeframe);
                $candlesCacheKey = sprintf('%s:%s:%s:%s:candles', $redisPrefix, $accountId, $symbol, $timeframe);
                $cacheKeysChecked[] = $cacheKey;

                $cachedCandles = [];
                $latestCandle = null;
                $candlesFromCache = 0;
                $currentCandles = []; // Initialize before use

                // First, try to get multiple candles from Redis list (new format)
                try {
                    Log::info('TradingBotStrategyWorker: Attempting to fetch from Redis list', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'candles_cache_key' => $candlesCacheKey,
                        'account_id' => $accountId,
                    ]);
                    $candlesList = Redis::lrange($candlesCacheKey, 0, -1); // Get all candles from list
                    Log::info('TradingBotStrategyWorker: Redis lrange result', [
                        'bot_id' => $this->bot->id,
                        'cache_key' => $candlesCacheKey,
                        'list_count' => is_array($candlesList) ? count($candlesList) : 0,
                        'is_empty' => empty($candlesList),
                        'sample_raw_candle' => !empty($candlesList) ? substr($candlesList[0], 0, 200) : null, // First 200 chars of first candle
                    ]);
                    if (!empty($candlesList)) {
                        $processedCount = 0;
                        $skippedCount = 0;
                        foreach ($candlesList as $index => $candleJson) {
                            $candleData = json_decode($candleJson, true);
                            if ($candleData && isset($candleData['open'], $candleData['high'], $candleData['low'], $candleData['close'])) {
                                $timestamp = $candleData['timestamp'] ?? (isset($candleData['time']) ? strtotime($candleData['time']) * 1000 : time() * 1000);
                                $cachedCandles[] = [
                                    'timestamp' => (int) $timestamp,
                                    'open' => (float) $candleData['open'],
                                    'high' => (float) $candleData['high'],
                                    'low' => (float) $candleData['low'],
                                    'close' => (float) $candleData['close'],
                                    'volume' => (int) ($candleData['volume'] ?? $candleData['tickVolume'] ?? 0),
                                    'symbol' => $candleData['symbol'] ?? $symbol,
                                    'timeframe' => $candleData['timeframe'] ?? $timeframe,
                                ];
                                $processedCount++;
                                
                                // Log sample of first 3 candles for debugging
                                if ($processedCount <= 3) {
                                    Log::info('TradingBotStrategyWorker: Sample candle from Redis', [
                                        'bot_id' => $this->bot->id,
                                        'candle_index' => $processedCount,
                                        'raw_json' => $candleJson,
                                        'decoded_data' => $candleData,
                                        'processed_candle' => [
                                            'timestamp' => (int) $timestamp,
                                            'open' => (float) $candleData['open'],
                                            'high' => (float) $candleData['high'],
                                            'low' => (float) $candleData['low'],
                                            'close' => (float) $candleData['close'],
                                            'volume' => (int) ($candleData['volume'] ?? $candleData['tickVolume'] ?? 0),
                                            'symbol' => $candleData['symbol'] ?? $symbol,
                                            'timeframe' => $candleData['timeframe'] ?? $timeframe,
                                        ],
                                    ]);
                                }
                            } else {
                                $skippedCount++;
                                if ($skippedCount <= 3) {
                                    Log::warning('TradingBotStrategyWorker: Skipped invalid candle from Redis', [
                                        'bot_id' => $this->bot->id,
                                        'candle_index' => $index,
                                        'raw_json' => substr($candleJson, 0, 200),
                                        'decoded_data' => $candleData,
                                        'has_required_fields' => isset($candleData['open'], $candleData['high'], $candleData['low'], $candleData['close']),
                                        'data_keys' => $candleData ? array_keys($candleData) : null,
                                    ]);
                                }
                            }
                        }
                        
                        Log::info('TradingBotStrategyWorker: Redis candle processing summary', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'total_in_redis' => count($candlesList),
                            'processed_count' => $processedCount,
                            'skipped_count' => $skippedCount,
                        ]);
                        // Sort by timestamp (oldest first)
                        usort($cachedCandles, function ($a, $b) {
                            return $a['timestamp'] <=> $b['timestamp'];
                        });
                        $candlesFromCache = count($cachedCandles);
                        if ($candlesFromCache > 0) {
                            $latestCandle = end($cachedCandles);
                            // Set currentCandles to the fetched candles
                            $currentCandles = $cachedCandles;
                            // Log sample of processed candles
                            $sampleProcessedCandles = array_slice($currentCandles, 0, 3);
                            Log::info('TradingBotStrategyWorker: Fetched candles from Redis list', [
                                'bot_id' => $this->bot->id,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'candles_count' => $candlesFromCache,
                                'cache_key' => $candlesCacheKey,
                                'currentCandles_count_after_set' => count($currentCandles),
                                'sample_processed_candles' => $sampleProcessedCandles,
                                'first_candle' => !empty($currentCandles) ? $currentCandles[0] : null,
                                'last_candle' => !empty($currentCandles) ? end($currentCandles) : null,
                            ]);
                            
                            // Ensure all candles have symbol and timeframe before adding to ohlcvData
                            foreach ($currentCandles as &$candle) {
                                if (!isset($candle['symbol'])) {
                                    $candle['symbol'] = $symbol;
                                }
                                if (!isset($candle['timeframe'])) {
                                    $candle['timeframe'] = $timeframe;
                                }
                            }
                            unset($candle); // Break reference
                            
                            // Add candles to ohlcvData immediately after fetching from Redis
                            $candlesToAdd = [];
                            foreach ($currentCandles as $candle) {
                                // Validate candle has required OHLCV fields
                                if (isset($candle['open'], $candle['high'], $candle['low'], $candle['close'], $candle['timestamp'])) {
                                    $candlesToAdd[] = $candle;
                                } else {
                                    Log::warning('TradingBotStrategyWorker: Skipping invalid candle from Redis', [
                                        'bot_id' => $this->bot->id,
                                        'symbol' => $symbol,
                                        'timeframe' => $timeframe,
                                        'candle_keys' => array_keys($candle),
                                        'missing_fields' => array_diff(['open', 'high', 'low', 'close', 'timestamp'], array_keys($candle)),
                                    ]);
                                }
                            }
                            
                            if (!empty($candlesToAdd)) {
                                foreach ($candlesToAdd as $candle) {
                                    $ohlcvData[] = $candle;
                                }
                                
                                // Log sample of first 3 candles being added
                                $sampleCandles = array_slice($candlesToAdd, 0, 3);
                                Log::info('TradingBotStrategyWorker: Added candles from Redis list to ohlcvData', [
                                    'bot_id' => $this->bot->id,
                                    'symbol' => $symbol,
                                    'timeframe' => $timeframe,
                                    'added_count' => count($candlesToAdd),
                                    'ohlcvData_count_after' => count($ohlcvData),
                                    'sample_candles' => $sampleCandles,
                                    'first_candle' => !empty($candlesToAdd) ? $candlesToAdd[0] : null,
                                    'last_candle' => !empty($candlesToAdd) ? end($candlesToAdd) : null,
                                ]);
                            } else {
                                Log::warning('TradingBotStrategyWorker: No valid candles to add from Redis', [
                                    'bot_id' => $this->bot->id,
                                    'symbol' => $symbol,
                                    'timeframe' => $timeframe,
                                    'currentCandles_count' => count($currentCandles),
                                    'sample_currentCandle' => !empty($currentCandles) ? $currentCandles[0] : null,
                                ]);
                            }
                        } else {
                            Log::debug('TradingBotStrategyWorker: No candles in Redis list', [
                                'bot_id' => $this->bot->id,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'cache_key' => $candlesCacheKey,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('TradingBotStrategyWorker: Failed to get candles from Redis list', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'cache_key' => $candlesCacheKey,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                // Fallback: Try to get latest candle from single cache key (backward compatibility)
                // Only run fallback if we didn't get candles from Redis list
                Log::info('TradingBotStrategyWorker: Checking if fallback needed', [
                    'bot_id' => $this->bot->id,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'currentCandles_empty' => empty($currentCandles),
                    'currentCandles_count' => count($currentCandles),
                    'ohlcvData_count' => count($ohlcvData),
                ]);
                
                if (empty($currentCandles)) {
                    $cached = Redis::get($cacheKey);
                    if (!$cached) {
                        $cached = Cache::get($cacheKey);
                    }

                    // If not found, try alternative symbol formats in cache
                    if (!$cached) {
                        $alternativeSymbols = $this->getAlternativeSymbolFormats($symbol);
                        foreach ($alternativeSymbols as $altSymbol) {
                            $altCacheKey = sprintf('%s:%s:%s:%s', $redisPrefix, $accountId, $altSymbol, $timeframe);
                            $cached = Redis::get($altCacheKey);
                            if (!$cached) {
                                $cached = Cache::get($altCacheKey);
                            }
                            if ($cached) {
                                Log::debug('TradingBotStrategyWorker: Found cached data with alternative symbol', [
                                    'bot_id' => $this->bot->id,
                                    'original_symbol' => $symbol,
                                    'alternative_symbol' => $altSymbol,
                                    'timeframe' => $timeframe,
                                    'cache_key' => $altCacheKey,
                                ]);
                                break;
                            }
                        }
                    }

                    if ($cached) {
                        $data = null;

                        // Handle both JSON string (from Socket.IO/polling) and array (legacy/Cache::put)
                        if (is_array($cached)) {
                            // Already an array (from Cache::put or auto-deserialization)
                            $data = $cached;
                        } elseif (is_string($cached)) {
                            // JSON string (from Redis::setex)
                            $decoded = json_decode($cached, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $data = $decoded;
                            } else {
                                Log::warning('TradingBotStrategyWorker: Failed to decode JSON', [
                                    'bot_id' => $this->bot->id,
                                    'cache_key' => $cacheKey,
                                    'json_error' => json_last_error_msg(),
                                ]);
                                // Continue to try database
                            }
                        } else {
                            Log::warning('TradingBotStrategyWorker: Unexpected cached data type', [
                                'bot_id' => $this->bot->id,
                                'cache_key' => $cacheKey,
                                'type' => gettype($cached),
                            ]);
                            // Continue to try database
                        }

                        if ($data && is_array($data)) {
                            // Handle different cached data formats:
                            // 1. Direct candle object: {open, high, low, close, time, ...}
                            // 2. Wrapper object: {symbol, timeframe, candles: [...], last_update, source}
                            $candleData = null;

                            if (isset($data['open']) && isset($data['high']) && isset($data['low']) && isset($data['close'])) {
                                // Format 1: Direct candle object
                                $candleData = $data;
                            } elseif (isset($data['candles']) && is_array($data['candles']) && !empty($data['candles'])) {
                                // Format 2: Wrapper with candles array - get the latest candle
                                $candlesArray = $data['candles'];
                                // Get the last candle (most recent)
                                $candleData = end($candlesArray);
                                // Reset array pointer
                                reset($candlesArray);

                                Log::debug('TradingBotStrategyWorker: Extracted candle from wrapper format', [
                                    'bot_id' => $this->bot->id,
                                    'cache_key' => $cacheKey,
                                    'candles_count' => count($candlesArray),
                                    'has_candle_data' => !empty($candleData),
                                ]);
                            }

                            if ($candleData && isset($candleData['open']) && isset($candleData['high']) && isset($candleData['low']) && isset($candleData['close'])) {
                                // Convert time to timestamp (ISO 8601 string or Date object)
                                $timestamp = time() * 1000; // Default to now
                                if (isset($candleData['time'])) {
                                    if (is_string($candleData['time'])) {
                                        $timestamp = strtotime($candleData['time']) * 1000;
                                    } elseif (is_numeric($candleData['time'])) {
                                        $timestamp = $candleData['time'] < 10000000000 ? $candleData['time'] * 1000 : $candleData['time'];
                                    }
                                }

                                // Convert to OHLCV format
                                $latestCandle = [
                                    'timestamp' => $timestamp,
                                    'open' => (float) ($candleData['open'] ?? 0),
                                    'high' => (float) ($candleData['high'] ?? 0),
                                    'low' => (float) ($candleData['low'] ?? 0),
                                    'close' => (float) ($candleData['close'] ?? 0),
                                    'volume' => (int) ($candleData['volume'] ?? $candleData['tickVolume'] ?? 0),
                                    'symbol' => $candleData['symbol'] ?? $data['symbol'] ?? $symbol,
                                    'timeframe' => $candleData['timeframe'] ?? $data['timeframe'] ?? $timeframe,
                                ];
                            } else {
                                Log::warning('TradingBotStrategyWorker: Invalid candle data format', [
                                    'bot_id' => $this->bot->id,
                                    'cache_key' => $cacheKey,
                                    'data_keys' => array_keys($data),
                                    'has_candle_data' => !empty($candleData),
                                    'candle_data_keys' => $candleData ? array_keys($candleData) : null,
                                ]);
                            }
                        } else {
                            Log::warning('TradingBotStrategyWorker: Failed to decode cached data', [
                                'bot_id' => $this->bot->id,
                                'cache_key' => $cacheKey,
                            ]);
                        }
                    }

                    // Fetch historical data from database for technical indicators
                    // Technical indicators need multiple candles (SMA needs period, RSI needs period+1, etc.)
                    // Try original symbol first, then alternative formats
                    $symbolsToTry = array_unique([$symbol] + $this->getAlternativeSymbolFormats($symbol));
                    $historicalCandles = null;
                    $foundSymbol = null;

                    foreach ($symbolsToTry as $trySymbol) {
                        try {
                            $candles = $marketDataService->getLatest($trySymbol, $timeframe, 100);

                            if ($candles->isNotEmpty()) {
                                $historicalCandles = $candles;
                                $foundSymbol = $trySymbol;
                                Log::debug('TradingBotStrategyWorker: Found historical data with alternative symbol', [
                                    'bot_id' => $this->bot->id,
                                    'original_symbol' => $symbol,
                                    'found_symbol' => $trySymbol,
                                    'timeframe' => $timeframe,
                                    'candle_count' => $candles->count(),
                                ]);
                                break;
                            }
                        } catch (\Exception $e) {
                            // Continue trying other symbols
                            continue;
                        }
                    }

                    // Initialize currentCandles with cached candles (if we fetched from Redis, it's already set at line 350)
                    // Defensive check: ensure we preserve candles from Redis fetch
                    if (empty($currentCandles) && !empty($cachedCandles)) {
                        $currentCandles = $cachedCandles;
                        Log::debug('TradingBotStrategyWorker: Initialized currentCandles from cachedCandles', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'cachedCandles_count' => count($cachedCandles),
                            'currentCandles_count' => count($currentCandles),
                        ]);
                    }
                    
                    // Log state before DB check
                    Log::info('TradingBotStrategyWorker: Before DB historical check', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'currentCandles_count' => count($currentCandles),
                        'candlesFromCache' => $candlesFromCache,
                        'has_historicalCandles' => !is_null($historicalCandles),
                        'sample_currentCandle' => !empty($currentCandles) ? $currentCandles[0] : null,
                        'historicalCandles_count' => $historicalCandles ? $historicalCandles->count() : 0,
                    ]);
                    
                    $candlesFromDb = 0;

                    if ($historicalCandles && $historicalCandles->isNotEmpty()) {
                        // Convert MarketData models to OHLCV array format
                        $dbCandles = [];
                        foreach ($historicalCandles as $candle) {
                            $dbCandles[] = [
                                'timestamp' => $candle->timestamp,
                                'open' => (float) $candle->open,
                                'high' => (float) $candle->high,
                                'low' => (float) $candle->low,
                                'close' => (float) $candle->close,
                                'volume' => (int) ($candle->volume ?? 0),
                                'symbol' => $symbol, // Use original symbol
                                'timeframe' => $candle->timeframe,
                            ];
                        }
                        $candlesFromDb = count($dbCandles);

                        // Merge cached candles with database candles (deduplicate by timestamp)
                        // Ensure all candles have symbol and timeframe before merging
                        foreach ($dbCandles as &$dbCandle) {
                            if (!isset($dbCandle['symbol'])) {
                                $dbCandle['symbol'] = $symbol;
                            }
                            if (!isset($dbCandle['timeframe'])) {
                                $dbCandle['timeframe'] = $timeframe;
                            }
                        }
                        unset($dbCandle);
                        
                        foreach ($currentCandles as &$cachedCandle) {
                            if (!isset($cachedCandle['symbol'])) {
                                $cachedCandle['symbol'] = $symbol;
                            }
                            if (!isset($cachedCandle['timeframe'])) {
                                $cachedCandle['timeframe'] = $timeframe;
                            }
                        }
                        unset($cachedCandle);
                        
                        $mergedCandles = [];
                        $seenTimestamps = [];

                        // Add all candles, keeping only the newest for each timestamp
                        foreach (array_merge($dbCandles, $currentCandles) as $candle) {
                            $ts = $candle['timestamp'] ?? 0;
                            if ($ts > 0 && (!isset($seenTimestamps[$ts]) || $seenTimestamps[$ts] < $candle['timestamp'])) {
                                $mergedCandles[$ts] = $candle;
                                $seenTimestamps[$ts] = $ts;
                            }
                        }

                        // Sort by timestamp (oldest first)
                        ksort($mergedCandles);
                        $currentCandles = array_values($mergedCandles);

                        // Update latest candle if we have newer data
                        if (!empty($currentCandles)) {
                            $latestCandle = end($currentCandles);
                        }

                        Log::info('TradingBotStrategyWorker: Merged cached and database candles', [
                            'bot_id' => $this->bot->id,
                            'original_symbol' => $symbol,
                            'found_symbol' => $foundSymbol,
                            'timeframe' => $timeframe,
                            'candles_from_cache' => $candlesFromCache,
                            'candles_from_db' => $candlesFromDb,
                            'total_candles' => count($currentCandles),
                        ]);
                    } elseif (!empty($currentCandles)) {
                        // Only cached candles available (no DB historical data)
                        // Ensure all candles have symbol and timeframe for proper deduplication
                        foreach ($currentCandles as &$candle) {
                            if (!isset($candle['symbol'])) {
                                $candle['symbol'] = $symbol;
                            }
                            if (!isset($candle['timeframe'])) {
                                $candle['timeframe'] = $timeframe;
                            }
                        }
                        unset($candle); // Break reference
                        
                        Log::info('TradingBotStrategyWorker: Using candles from cache only', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'candles_count' => count($currentCandles),
                            'candlesFromCache' => $candlesFromCache,
                            'path' => 'cache_only_no_db',
                        ]);
                    } elseif ($latestCandle) {
                        // Only latest candle from single cache key (backward compatibility)
                        $currentCandles = [$latestCandle];

                        Log::info('TradingBotStrategyWorker: Using only latest candle from cache (no historical data)', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'tried_symbols' => $symbolsToTry,
                        ]);
                    } else {
                        // No cache and no historical data
                        $currentCandles = [];
                        Log::debug('TradingBotStrategyWorker: No cached data and no historical data in database', [
                            'bot_id' => $this->bot->id,
                            'cache_key' => $cacheKey,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'tried_symbols' => $symbolsToTry,
                            'account_id' => $accountId,
                        ]);
                    }

                    // If we have less than 20 candles, try to fetch historical data from MetaAPI
                    $minCandlesRequired = 20;
                    if (!empty($currentCandles) && count($currentCandles) < $minCandlesRequired) {
                        Log::info('TradingBotStrategyWorker: Insufficient candles, fetching from MetaAPI', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'current_count' => count($currentCandles),
                            'required' => $minCandlesRequired,
                        ]);

                        $fetchedCandles = $this->fetchHistoricalDataFromMetaAPI($dataConnection, $symbol, $timeframe, 50);
                        if (!empty($fetchedCandles)) {
                            // Merge fetched candles with existing (deduplicate by timestamp)
                            $mergedCandles = [];
                            $seenTimestamps = [];

                            // Ensure fetched candles have symbol and timeframe
                            foreach ($fetchedCandles as &$fetchedCandle) {
                                if (!isset($fetchedCandle['symbol'])) {
                                    $fetchedCandle['symbol'] = $symbol;
                                }
                                if (!isset($fetchedCandle['timeframe'])) {
                                    $fetchedCandle['timeframe'] = $timeframe;
                                }
                            }
                            unset($fetchedCandle);
                            
                            foreach (array_merge($currentCandles, $fetchedCandles) as $candle) {
                                $ts = $candle['timestamp'] ?? 0;
                                if ($ts > 0 && (!isset($seenTimestamps[$ts]) || $seenTimestamps[$ts] < $candle['timestamp'])) {
                                    $mergedCandles[$ts] = $candle;
                                    $seenTimestamps[$ts] = $ts;
                                }
                            }

                            // Sort by timestamp (oldest first)
                            ksort($mergedCandles);
                            $currentCandles = array_values($mergedCandles);

                            // Update latest candle
                            if (!empty($currentCandles)) {
                                $latestCandle = end($currentCandles);
                            }

                            Log::info('TradingBotStrategyWorker: Fetched historical data from MetaAPI', [
                                'bot_id' => $this->bot->id,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'fetched_count' => count($fetchedCandles),
                                'total_candles' => count($currentCandles),
                            ]);
                        }
                    }

                    // Add all candles for this symbol/timeframe to ohlcvData
                    // Ensure each candle has symbol and timeframe before adding
                    $candlesToAdd = [];
                    foreach ($currentCandles as $candle) {
                        // Ensure required fields exist
                        if (!isset($candle['symbol'])) {
                            $candle['symbol'] = $symbol;
                        }
                        if (!isset($candle['timeframe'])) {
                            $candle['timeframe'] = $timeframe;
                        }
                        // Validate candle has required OHLCV fields
                        if (isset($candle['open'], $candle['high'], $candle['low'], $candle['close'], $candle['timestamp'])) {
                            $candlesToAdd[] = $candle;
                        } else {
                            Log::warning('TradingBotStrategyWorker: Skipping invalid candle', [
                                'bot_id' => $this->bot->id,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'candle_keys' => array_keys($candle),
                                'missing_fields' => array_diff(['open', 'high', 'low', 'close', 'timestamp'], array_keys($candle)),
                            ]);
                        }
                    }
                    
                    Log::info('TradingBotStrategyWorker: Adding candles to ohlcvData', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'currentCandles_count' => count($currentCandles),
                        'valid_candles_count' => count($candlesToAdd),
                        'ohlcvData_count_before' => count($ohlcvData),
                    ]);
                    
                    if (!empty($candlesToAdd)) {
                        foreach ($candlesToAdd as $candle) {
                            $ohlcvData[] = $candle;
                        }
                        
                        // Log sample of candles being added
                        $sampleCandles = array_slice($candlesToAdd, 0, 3);
                        Log::info('TradingBotStrategyWorker: Added candles to ohlcvData (fallback path)', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'added_count' => count($candlesToAdd),
                            'ohlcvData_count_after' => count($ohlcvData),
                            'sample_candles' => $sampleCandles,
                            'first_candle' => !empty($candlesToAdd) ? $candlesToAdd[0] : null,
                            'last_candle' => !empty($candlesToAdd) ? end($candlesToAdd) : null,
                        ]);
                    } else {
                        Log::warning('TradingBotStrategyWorker: No valid candles to add to ohlcvData', [
                            'bot_id' => $this->bot->id,
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'currentCandles_count' => count($currentCandles),
                            'cachedCandles_count' => count($cachedCandles),
                            'candlesFromCache' => $candlesFromCache,
                            'sample_currentCandle' => !empty($currentCandles) ? $currentCandles[0] : null,
                            'sample_cachedCandle' => !empty($cachedCandles) ? $cachedCandles[0] : null,
                        ]);
                    }

                    // If still no data after trying cache, database, and MetaAPI, log warning
                    $hasDataForThisSymbol = $this->hasDataForSymbol($ohlcvData, $symbol);
                    if (!$hasDataForThisSymbol) {
                        // Check if stream exists and has subscribers
                        $stream = \Addons\TradingManagement\Modules\DataProvider\Models\MetaapiStream::where('account_id', $accountId)
                            ->where('symbol', $symbol)
                            ->where('timeframe', $timeframe)
                            ->first();

                        if ($stream) {
                            // Check if stream worker is actually running by checking Redis for any data
                            $testKey = sprintf('%s:%s:%s:%s', $redisPrefix, $accountId, $symbol, $timeframe);
                            $hasData = Redis::exists($testKey);

                            Log::info('TradingBotStrategyWorker: Stream exists but no data available', [
                                'bot_id' => $this->bot->id,
                                'stream_id' => $stream->id,
                                'stream_status' => $stream->status,
                                'subscriber_count' => $stream->subscriber_count,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'cache_key' => $cacheKey,
                                'redis_key_exists' => $hasData,
                                'hint' => $hasData ? 'Data exists in Redis but lookup failed. Check Redis connection.' : 'MetaAPI stream worker may not be running or polling failed. Run: php artisan metaapi:stream-worker ' . $accountId,
                            ]);
                        } else {
                            Log::warning('TradingBotStrategyWorker: Stream not found in database', [
                                'bot_id' => $this->bot->id,
                                'account_id' => $accountId,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'hint' => 'Bot may not be subscribed to stream. Check ensureSubscriptions() logs.',
                            ]);
                        }
                    }
                }
            }
        }

        // Remove duplicates and sort by timestamp (oldest first for indicators)
        $ohlcvData = $this->deduplicateAndSortCandles($ohlcvData);

        // Validate data quality
        $this->validateMarketData($ohlcvData);

        // Calculate data consumption metrics
        $dataQuality = $this->assessDataQuality($ohlcvData);
        $symbolsProcessed = array_unique(array_column($ohlcvData, 'symbol'));
        $timeframesProcessed = array_unique(array_column($ohlcvData, 'timeframe'));
        
        // Log sample of final ohlcvData for debugging
        $sampleOhlcvData = [];
        if (!empty($ohlcvData)) {
            $sampleOhlcvData = [
                'first_3_candles' => array_slice($ohlcvData, 0, 3),
                'last_3_candles' => array_slice($ohlcvData, -3),
                'total_count' => count($ohlcvData),
            ];
        }
        
        Log::info('TradingBotStrategyWorker: Data consumption summary', [
            'bot_id' => $this->bot->id,
            'cache_keys_checked' => count($cacheKeysChecked),
            'ohlcv_data_count' => count($ohlcvData),
            'cache_keys' => $cacheKeysChecked,
            'has_data' => !empty($ohlcvData),
            'data_quality' => $dataQuality,
            'symbols_processed' => array_values($symbolsProcessed),
            'timeframes_processed' => array_values($timeframesProcessed),
            'unique_symbol_timeframe_combinations' => count($symbolsProcessed) * count($timeframesProcessed),
            'sample_data' => $sampleOhlcvData,
        ]);

        return $ohlcvData;
    }

    /**
     * Fetch historical data from MetaAPI when cache/database don't have enough
     */
    protected function fetchHistoricalDataFromMetaAPI($dataConnection, string $symbol, string $timeframe, int $limit = 50): array
    {
        try {
            // Get adapter for data connection
            $adapterService = app(\Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService::class);
            $adapter = $adapterService->getAdapter($dataConnection);

            if (!$adapter || !method_exists($adapter, 'fetchOHLCV')) {
                Log::warning('TradingBotStrategyWorker: Adapter does not support fetchOHLCV', [
                    'bot_id' => $this->bot->id,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'connection_id' => $dataConnection->id,
                ]);
                return [];
            }

            // Try original symbol first, then alternatives
            $symbolsToTry = array_unique([$symbol] + $this->getAlternativeSymbolFormats($symbol));
            $candles = [];

            foreach ($symbolsToTry as $trySymbol) {
                try {
                    $fetched = $adapter->fetchOHLCV($trySymbol, $timeframe, $limit);

                    if (!empty($fetched)) {
                        // Convert to OHLCV format
                        foreach ($fetched as $candle) {
                            $candles[] = [
                                'timestamp' => isset($candle['timestamp'])
                                    ? (int) ($candle['timestamp'] < 10000000000 ? $candle['timestamp'] * 1000 : $candle['timestamp'])
                                    : time() * 1000,
                                'open' => (float) ($candle['open'] ?? 0),
                                'high' => (float) ($candle['high'] ?? 0),
                                'low' => (float) ($candle['low'] ?? 0),
                                'close' => (float) ($candle['close'] ?? 0),
                                'volume' => (int) ($candle['volume'] ?? 0),
                                'symbol' => $symbol, // Use original symbol
                                'timeframe' => $timeframe,
                            ];
                        }

                        Log::info('TradingBotStrategyWorker: Fetched historical data from MetaAPI', [
                            'bot_id' => $this->bot->id,
                            'original_symbol' => $symbol,
                            'fetched_symbol' => $trySymbol,
                            'timeframe' => $timeframe,
                            'candles_count' => count($candles),
                        ]);

                        // Store in database for future use
                        try {
                            // Store directly using MarketData model (unified connections use execution_connections table)
                            $marketData = \Addons\TradingManagement\Modules\MarketData\Models\MarketData::class;
                            $records = [];

                            foreach ($candles as $candle) {
                                // Check if candle already exists to avoid duplicates
                                $exists = $marketData::where('symbol', $symbol)
                                    ->where('timeframe', $timeframe)
                                    ->where('timestamp', $candle['timestamp'])
                                    ->exists();

                                if (!$exists) {
                                    $records[] = [
                                        'data_connection_id' => $dataConnection->id,
                                        'symbol' => $symbol,
                                        'timeframe' => $timeframe,
                                        'timestamp' => $candle['timestamp'],
                                        'open' => $candle['open'],
                                        'high' => $candle['high'],
                                        'low' => $candle['low'],
                                        'close' => $candle['close'],
                                        'volume' => $candle['volume'],
                                        'source_type' => $dataConnection->connection_type ?? $dataConnection->type ?? 'unknown',
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }

                            if (!empty($records)) {
                                $marketData::bulkInsert($records);
                                Log::info('TradingBotStrategyWorker: Stored fetched candles in database', [
                                    'bot_id' => $this->bot->id,
                                    'symbol' => $symbol,
                                    'timeframe' => $timeframe,
                                    'candles_stored' => count($records),
                                    'total_fetched' => count($candles),
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::warning('TradingBotStrategyWorker: Failed to store candles in database', [
                                'bot_id' => $this->bot->id,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        break; // Success, stop trying other symbols
                    }
                } catch (\Exception $e) {
                    Log::debug('TradingBotStrategyWorker: Failed to fetch from MetaAPI with symbol', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $trySymbol,
                        'timeframe' => $timeframe,
                        'error' => $e->getMessage(),
                    ]);
                    continue; // Try next symbol
                }
            }

            return $candles;
        } catch (\Exception $e) {
            Log::error('TradingBotStrategyWorker: Failed to fetch historical data from MetaAPI', [
                'bot_id' => $this->bot->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get alternative symbol formats to try if primary symbol fails
     */
    protected function getAlternativeSymbolFormats(string $symbol): array
    {
        $alternatives = [];

        // Common symbol format variations
        $upper = strtoupper($symbol);
        $lower = strtolower($symbol);

        // If symbol contains slash, try without slash
        if (strpos($symbol, '/') !== false) {
            $alternatives[] = str_replace('/', '', $symbol);
            $alternatives[] = str_replace('/', '', $upper);
        }

        // If symbol doesn't contain slash, try with slash
        if (strpos($symbol, '/') === false) {
            // Try common pair formats
            if (strlen($symbol) === 6) {
                // EURUSD -> EUR/USD
                $alternatives[] = substr($symbol, 0, 3) . '/' . substr($symbol, 3, 3);
            }
        }

        // XAUUSDC -> XAUUSD, XAUUSDc, XAUUSDm
        if (strpos($upper, 'XAUUSDC') !== false) {
            $alternatives[] = 'XAUUSD';
            $alternatives[] = 'XAUUSDc';
            $alternatives[] = 'XAUUSDm';
            $alternatives[] = 'GOLD';
        }

        // XAUUSD -> XAUUSDc, XAUUSDm, XAUUSDC
        if (strpos($upper, 'XAUUSD') !== false && strpos($upper, 'XAUUSDC') === false) {
            $alternatives[] = 'XAUUSDc';
            $alternatives[] = 'XAUUSDm';
            $alternatives[] = 'XAUUSDC';
            $alternatives[] = 'GOLD';
        }

        // XAGUSD -> XAGUSDc, XAGUSDm
        if (strpos($upper, 'XAGUSD') !== false && strpos($upper, 'XAGUSDC') === false) {
            $alternatives[] = 'XAGUSDc';
            $alternatives[] = 'XAGUSDm';
        }

        // Remove duplicates
        return array_unique($alternatives);
    }

    /**
     * Check if we have data for a specific symbol
     */
    protected function hasDataForSymbol(array $ohlcvData, string $symbol): bool
    {
        foreach ($ohlcvData as $candle) {
            if (($candle['symbol'] ?? '') === $symbol) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deduplicate candles by timestamp and sort by timestamp ascending
     */
    protected function deduplicateAndSortCandles(array $candles): array
    {
        if (empty($candles)) {
            Log::debug('TradingBotStrategyWorker: deduplicateAndSortCandles received empty array', [
                'bot_id' => $this->bot->id,
            ]);
            return [];
        }

        // Group by timestamp+symbol+timeframe to remove duplicates (prevent mixing different symbols/timeframes)
        $unique = [];
        $skipped = 0;
        foreach ($candles as $candle) {
            $timestamp = $candle['timestamp'] ?? 0;
            $symbol = $candle['symbol'] ?? 'unknown';
            $timeframe = $candle['timeframe'] ?? 'unknown';
            
            // Skip candles without valid timestamp
            if ($timestamp <= 0) {
                $skipped++;
                Log::debug('TradingBotStrategyWorker: Skipping candle with invalid timestamp', [
                    'bot_id' => $this->bot->id,
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'timestamp' => $timestamp,
                ]);
                continue;
            }
            
            // Use composite key: timestamp_symbol_timeframe to prevent mixing
            $key = "{$timestamp}_{$symbol}_{$timeframe}";
            
            if (!isset($unique[$key])) {
                // Validate candle has required fields
                if (isset($candle['open'], $candle['high'], $candle['low'], $candle['close'])) {
                    $unique[$key] = $candle;
                } else {
                    $skipped++;
                    Log::debug('TradingBotStrategyWorker: Skipping candle missing OHLC fields', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'has_fields' => [
                            'open' => isset($candle['open']),
                            'high' => isset($candle['high']),
                            'low' => isset($candle['low']),
                            'close' => isset($candle['close']),
                        ],
                    ]);
                }
            } else {
                // If duplicate, prefer the one with more complete data
                if (isset($candle['volume']) && !isset($unique[$key]['volume'])) {
                    $unique[$key] = $candle;
                }
            }
        }

        if ($skipped > 0) {
            Log::warning('TradingBotStrategyWorker: Skipped invalid candles during deduplication', [
                'bot_id' => $this->bot->id,
                'skipped_count' => $skipped,
                'total_input' => count($candles),
                'valid_output' => count($unique),
            ]);
        }

        // Sort by timestamp ascending (oldest first, for technical indicators)
        usort($unique, function ($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });

        $result = array_values($unique);
        
        Log::debug('TradingBotStrategyWorker: Deduplication complete', [
            'bot_id' => $this->bot->id,
            'input_count' => count($candles),
            'output_count' => count($result),
            'skipped' => $skipped,
        ]);

        return $result;
    }

    /**
     * Validate market data quality
     * 
     * @param array $ohlcvData
     * @return void
     */
    protected function validateMarketData(array $ohlcvData): void
    {
        $candleCount = count($ohlcvData);

        if ($candleCount < 20) {
            Log::warning('TradingBotStrategyWorker: Insufficient candles for reliable indicator calculation', [
                'bot_id' => $this->bot->id,
                'candle_count' => $candleCount,
                'minimum_recommended' => 20,
                'note' => 'Indicators may not be calculated accurately with less than 20 candles',
            ]);
        }

        if ($candleCount > 0) {
            // Check data freshness
            $latestCandle = end($ohlcvData);
            $latestTimestamp = $latestCandle['timestamp'] ?? 0;
            $ageMinutes = (time() * 1000 - $latestTimestamp) / 60000;

            if ($ageMinutes > 60) {
                Log::warning('TradingBotStrategyWorker: Market data is stale', [
                    'bot_id' => $this->bot->id,
                    'latest_timestamp' => $latestTimestamp,
                    'age_minutes' => round($ageMinutes, 2),
                    'note' => 'Data older than 60 minutes may not reflect current market conditions',
                ]);
            }

            // Check for gaps in timestamps
            $gaps = [];
            for ($i = 1; $i < count($ohlcvData); $i++) {
                $prevTs = $ohlcvData[$i - 1]['timestamp'] ?? 0;
                $currTs = $ohlcvData[$i]['timestamp'] ?? 0;
                $gap = $currTs - $prevTs;

                // Expected gap depends on timeframe (1h = 3600000ms, 4h = 14400000ms, 1d = 86400000ms)
                $expectedGap = $this->getExpectedGapForTimeframe($ohlcvData[$i]['timeframe'] ?? '1h');
                if ($gap > $expectedGap * 2) {
                    $gaps[] = [
                        'from' => $prevTs,
                        'to' => $currTs,
                        'gap_ms' => $gap,
                        'expected_ms' => $expectedGap,
                    ];
                }
            }

            if (!empty($gaps)) {
                Log::warning('TradingBotStrategyWorker: Gaps detected in market data', [
                    'bot_id' => $this->bot->id,
                    'gaps_count' => count($gaps),
                    'gaps' => $gaps,
                    'note' => 'Gaps in data may affect indicator accuracy',
                ]);
            }
        }
    }

    /**
     * Assess data quality
     * 
     * @param array $ohlcvData
     * @return array Quality metrics
     */
    protected function assessDataQuality(array $ohlcvData): array
    {
        $candleCount = count($ohlcvData);
        $quality = 'good';
        $issues = [];

        if ($candleCount < 20) {
            $quality = 'poor';
            $issues[] = 'insufficient_candles';
        } elseif ($candleCount < 50) {
            $quality = 'fair';
            $issues[] = 'limited_candles';
        }

        if ($candleCount > 0) {
            $latestCandle = end($ohlcvData);
            $latestTimestamp = $latestCandle['timestamp'] ?? 0;
            $ageMinutes = (time() * 1000 - $latestTimestamp) / 60000;

            if ($ageMinutes > 60) {
                $quality = $quality === 'poor' ? 'poor' : 'fair';
                $issues[] = 'stale_data';
            }
        }

        return [
            'quality' => $quality,
            'candle_count' => $candleCount,
            'issues' => $issues,
        ];
    }

    /**
     * Get expected gap in milliseconds for timeframe
     * 
     * @param string $timeframe
     * @return int Milliseconds
     */
    protected function getExpectedGapForTimeframe(string $timeframe): int
    {
        $timeframeMap = [
            '1m' => 60000,
            '5m' => 300000,
            '15m' => 900000,
            '30m' => 1800000,
            '1h' => 3600000,
            '4h' => 14400000,
            '1d' => 86400000,
        ];

        return $timeframeMap[$timeframe] ?? 3600000; // Default to 1h
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
