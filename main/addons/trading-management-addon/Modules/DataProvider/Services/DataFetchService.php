<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * DataFetchService
 * 
 * Reliable market data fetching with caching, retry logic, and rate limiting
 */
class DataFetchService
{
    protected MarketDataService $marketDataService;
    protected DataConnectionService $dataConnectionService;
    protected int $defaultRetries = 3;
    protected int $defaultBackoffSeconds = 2;
    protected int $rateLimitWindow = 60; // seconds
    protected int $maxRequestsPerWindow = 100;

    public function __construct(
        MarketDataService $marketDataService,
        DataConnectionService $dataConnectionService
    ) {
        $this->marketDataService = $marketDataService;
        $this->dataConnectionService = $dataConnectionService;
    }

    /**
     * Fetch market data for a bot
     * 
     * @param TradingBot $bot
     * @param array $symbols
     * @return array
     */
    public function fetchMarketData(TradingBot $bot, array $symbols): array
    {
        $results = [];
        $connection = $bot->dataConnection ?? $bot->exchangeConnection;

        if (!$connection) {
            Log::warning('No data connection available for bot', ['bot_id' => $bot->id]);
            return [];
        }

        foreach ($symbols as $symbol) {
            try {
                // Check rate limit
                if (!$this->checkRateLimit($connection->id)) {
                    Log::warning('Rate limit exceeded, using cached data', [
                        'bot_id' => $bot->id,
                        'symbol' => $symbol,
                    ]);
                    $results[$symbol] = $this->getCachedData($symbol, $bot->streaming_timeframes ?? ['H1']);
                    continue;
                }

                // Fetch with retry
                $data = $this->fetchWithRetry(
                    $connection,
                    $symbol,
                    $bot->streaming_timeframes ?? ['H1'],
                    $this->defaultRetries
                );

                $results[$symbol] = $data;
            } catch (\Exception $e) {
                Log::error('Failed to fetch market data', [
                    'bot_id' => $bot->id,
                    'symbol' => $symbol,
                    'error' => $e->getMessage(),
                ]);

                // Fallback to cached data
                $results[$symbol] = $this->getCachedData($symbol, $bot->streaming_timeframes ?? ['H1']);
            }
        }

        // Update last fetch timestamp
        $bot->update(['last_data_fetch_at' => now()]);

        return $results;
    }

    /**
     * Fetch data with exponential backoff retry
     * 
     * @param DataConnection $connection
     * @param string $symbol
     * @param array $timeframes
     * @param int $maxRetries
     * @return array
     */
    public function fetchWithRetry(
        DataConnection $connection,
        string $symbol,
        array $timeframes,
        int $maxRetries = 3
    ): array {
        $lastException = null;
        $backoff = $this->defaultBackoffSeconds;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $adapter = $this->dataConnectionService->getAdapter($connection);
                if (!$adapter) {
                    throw new \Exception('Adapter not available');
                }

                $data = [];
                foreach ($timeframes as $timeframe) {
                    // Try to fetch from adapter
                    if (method_exists($adapter, 'fetchOHLCV')) {
                        $ohlcv = $adapter->fetchOHLCV($symbol, $timeframe, 200);
                        if (!empty($ohlcv)) {
                            // Store in MarketDataService
                            $this->marketDataService->store($connection, $symbol, $timeframe, $ohlcv);
                            $data[$timeframe] = $ohlcv;
                        } else {
                            // Fallback to cached
                            $cached = $this->marketDataService->getLatest($symbol, $timeframe, 200);
                            $data[$timeframe] = $cached->map(function ($item) {
                                return [
                                    'timestamp' => $item->timestamp,
                                    'open' => $item->open,
                                    'high' => $item->high,
                                    'low' => $item->low,
                                    'close' => $item->close,
                                    'volume' => $item->volume,
                                ];
                            })->toArray();
                        }
                    } else {
                        // Fallback to cached
                        $cached = $this->marketDataService->getLatest($symbol, $timeframe, 200);
                        $data[$timeframe] = $cached->map(function ($item) {
                            return [
                                'timestamp' => $item->timestamp,
                                'open' => $item->open,
                                'high' => $item->high,
                                'low' => $item->low,
                                'close' => $item->close,
                                'volume' => $item->volume,
                            ];
                        })->toArray();
                    }
                }

                return $data;
            } catch (\Exception $e) {
                $lastException = $e;
                
                if ($attempt < $maxRetries) {
                    Log::warning('Data fetch attempt failed, retrying', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'symbol' => $symbol,
                        'backoff_seconds' => $backoff,
                        'error' => $e->getMessage(),
                    ]);

                    // Exponential backoff
                    sleep($backoff);
                    $backoff *= 2;
                }
            }
        }

        // All retries failed, throw last exception
        throw new \Exception(
            "Failed to fetch data after {$maxRetries} attempts: " . $lastException->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Get cached data
     * 
     * @param string $symbol
     * @param array $timeframes
     * @param int $ttl Cache TTL in seconds
     * @return array|null
     */
    public function getCachedData(string $symbol, array $timeframes, int $ttl = 300): ?array
    {
        $data = [];
        $hasData = false;

        foreach ($timeframes as $timeframe) {
            $cached = $this->marketDataService->getLatest($symbol, $timeframe, 200);
            if ($cached->isNotEmpty()) {
                $hasData = true;
                $data[$timeframe] = $cached->map(function ($item) {
                    return [
                        'timestamp' => $item->timestamp,
                        'open' => $item->open,
                        'high' => $item->high,
                        'low' => $item->low,
                        'close' => $item->close,
                        'volume' => $item->volume,
                    ];
                })->toArray();
            }
        }

        return $hasData ? $data : null;
    }

    /**
     * Sync historical data for a bot
     * 
     * @param TradingBot $bot
     * @param int $days Number of days to sync
     * @return array
     */
    public function syncHistoricalData(TradingBot $bot, int $days = 30): array
    {
        $connection = $bot->dataConnection ?? $bot->exchangeConnection;
        if (!$connection) {
            return ['success' => false, 'message' => 'No data connection available'];
        }

        $symbols = $bot->streaming_symbols ?? [];
        $timeframes = $bot->streaming_timeframes ?? ['H1'];
        $results = [];

        foreach ($symbols as $symbol) {
            foreach ($timeframes as $timeframe) {
                try {
                    $adapter = $this->dataProviderService->getAdapter($connection);
                    if (!$adapter || !method_exists($adapter, 'fetchOHLCV')) {
                        continue;
                    }

                    // Calculate limit based on timeframe and days
                    $candlesPerDay = $this->getCandlesPerDay($timeframe);
                    $limit = $candlesPerDay * $days;

                    $ohlcv = $adapter->fetchOHLCV($symbol, $timeframe, $limit);
                    if (!empty($ohlcv)) {
                        $this->marketDataService->store($connection, $symbol, $timeframe, $ohlcv);
                        $results[] = [
                            'symbol' => $symbol,
                            'timeframe' => $timeframe,
                            'candles' => count($ohlcv),
                            'success' => true,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to sync historical data', [
                        'bot_id' => $bot->id,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = [
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * Check rate limit for connection
     * 
     * @param int $connectionId
     * @return bool
     */
    protected function checkRateLimit(int $connectionId): bool
    {
        $key = "data_fetch_rate_limit_{$connectionId}";
        $current = Cache::get($key, 0);
        
        if ($current >= $this->maxRequestsPerWindow) {
            return false;
        }

        Cache::put($key, $current + 1, $this->rateLimitWindow);
        return true;
    }

    /**
     * Get candles per day for a timeframe
     * 
     * @param string $timeframe
     * @return int
     */
    protected function getCandlesPerDay(string $timeframe): int
    {
        $mapping = [
            'M1' => 1440,
            'M5' => 288,
            'M15' => 96,
            'M30' => 48,
            'H1' => 24,
            'H4' => 6,
            'D1' => 1,
            'W1' => 1,
            'MN' => 1,
        ];

        return $mapping[$timeframe] ?? 24;
    }
}

