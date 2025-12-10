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
        Log::info('TradingBotStrategyWorker: Starting analysis', [
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name,
            'trading_mode' => $this->bot->trading_mode,
        ]);

        // 1. Ensure subscriptions to required streams
        $this->ensureSubscriptions();

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
            return; // No data available yet
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

            $analysis = $this->analysisService->analyzeSignals($indicators);

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
            
            FilterAnalysisJob::dispatch($this->bot, $decision, $marketData);
            
            Log::info('TradingBotStrategyWorker: Trading decision made, dispatched to filter analysis', [
                'bot_id' => $this->bot->id,
                'direction' => $decision['direction'],
                'confidence' => $decision['confidence'],
                'entry_price' => $decision['entry_price'] ?? null,
                'stop_loss' => $decision['stop_loss'] ?? null,
                'take_profit' => $decision['take_profit'] ?? null,
            ]);
        } else {
            Log::info('TradingBotStrategyWorker: No trade decision - not entering', [
                'bot_id' => $this->bot->id,
                'reason' => $decision['reason'] ?? 'unknown',
                'confidence' => $decision['confidence'] ?? 0,
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

        // Get latest data for each symbol/timeframe
        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                $cacheKey = sprintf('%s:%s:%s:%s', $redisPrefix, $accountId, $symbol, $timeframe);
                $cacheKeysChecked[] = $cacheKey;
                
                // Try original symbol first
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
                
                $latestCandle = null;
                
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
                
                if ($historicalCandles && $historicalCandles->isNotEmpty()) {
                    // Convert MarketData models to OHLCV array format
                    // Use original symbol in data (not the found symbol) for consistency
                    foreach ($historicalCandles as $candle) {
                        $ohlcvData[] = [
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
                    
                    // If we have latest candle from cache, ensure it's included (might be newer than DB)
                    if ($latestCandle) {
                        // Check if latest candle is newer than the most recent DB candle
                        // Note: getLatest returns DESC order, so first item is newest
                        $mostRecentDb = !empty($ohlcvData) ? $ohlcvData[0]['timestamp'] ?? 0 : 0;
                        if ($latestCandle['timestamp'] > $mostRecentDb) {
                            // Prepend latest candle (it's newer)
                            array_unshift($ohlcvData, $latestCandle);
                        } elseif ($latestCandle['timestamp'] === $mostRecentDb) {
                            // Update the first candle with latest data
                            $ohlcvData[0] = $latestCandle;
                        }
                        // If latest is older, ignore it (DB has newer data)
                    }
                    
                    Log::info('TradingBotStrategyWorker: Fetched historical data from database', [
                        'bot_id' => $this->bot->id,
                        'original_symbol' => $symbol,
                        'found_symbol' => $foundSymbol,
                        'timeframe' => $timeframe,
                        'candle_count' => count($ohlcvData),
                        'has_latest_from_cache' => !is_null($latestCandle),
                    ]);
                } elseif ($latestCandle) {
                    // No historical data, but we have latest from cache
                    $ohlcvData[] = $latestCandle;
                    
                    Log::info('TradingBotStrategyWorker: Using only latest candle from cache (no historical data in DB)', [
                        'bot_id' => $this->bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'tried_symbols' => $symbolsToTry,
                    ]);
                } else {
                    // No cache and no historical data
                    Log::debug('TradingBotStrategyWorker: No cached data and no historical data in database', [
                        'bot_id' => $this->bot->id,
                        'cache_key' => $cacheKey,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'tried_symbols' => $symbolsToTry,
                        'account_id' => $accountId,
                    ]);
                }
                    
                // If still no data after trying cache and database, log warning
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
        
        // Remove duplicates and sort by timestamp (oldest first for indicators)
        $ohlcvData = $this->deduplicateAndSortCandles($ohlcvData);

        Log::info('TradingBotStrategyWorker: Data consumption summary', [
            'bot_id' => $this->bot->id,
            'cache_keys_checked' => count($cacheKeysChecked),
            'ohlcv_data_count' => count($ohlcvData),
            'cache_keys' => $cacheKeysChecked,
            'has_data' => !empty($ohlcvData),
        ]);

        return $ohlcvData;
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
            return [];
        }
        
        // Group by timestamp to remove duplicates
        $unique = [];
        foreach ($candles as $candle) {
            $timestamp = $candle['timestamp'] ?? 0;
            if (!isset($unique[$timestamp])) {
                $unique[$timestamp] = $candle;
            } else {
                // If duplicate, prefer the one with more complete data
                if (isset($candle['volume']) && !isset($unique[$timestamp]['volume'])) {
                    $unique[$timestamp] = $candle;
                }
            }
        }
        
        // Sort by timestamp ascending (oldest first, for technical indicators)
        usort($unique, function($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });
        
        return array_values($unique);
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
