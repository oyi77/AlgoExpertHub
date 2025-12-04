<?php

namespace Addons\TradingManagement\Modules\MarketData\Services;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\MarketData\Models\MarketData;
use Addons\TradingManagement\Shared\DTOs\MarketDataDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Market Data Service
 * 
 * Centralized service for storing and retrieving market data
 * with caching support to reduce database queries
 */
class MarketDataService
{
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('trading-management.data_provider.cache_ttl', 300); // 5 min default
    }

    /**
     * Store market data (batch insert)
     * 
     * @param DataConnection $connection
     * @param string $symbol
     * @param string $timeframe
     * @param array $ohlcvData Array of candles [[timestamp, open, high, low, close, volume], ...]
     * @return int Number of rows inserted
     */
    public function store(DataConnection $connection, string $symbol, string $timeframe, array $ohlcvData): int
    {
        if (empty($ohlcvData)) {
            return 0;
        }

        $records = [];
        
        foreach ($ohlcvData as $candle) {
            $records[] = [
                'data_connection_id' => $connection->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'timestamp' => $candle['timestamp'] ?? $candle[0],
                'open' => $candle['open'] ?? $candle[1],
                'high' => $candle['high'] ?? $candle[2],
                'low' => $candle['low'] ?? $candle[3],
                'close' => $candle['close'] ?? $candle[4],
                'volume' => $candle['volume'] ?? $candle[5] ?? null,
                'source_type' => $connection->type,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        try {
            $inserted = MarketData::bulkInsert($records);
            
            // Clear cache for this symbol/timeframe
            $this->clearCache($symbol, $timeframe);
            
            return $inserted;
        } catch (\Exception $e) {
            \Log::error('Failed to store market data', [
                'connection_id' => $connection->id,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'count' => count($records),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get latest candles (with caching)
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatest(string $symbol, string $timeframe, int $limit = 100)
    {
        $cacheKey = $this->getCacheKey($symbol, $timeframe, 'latest', $limit);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol, $timeframe, $limit) {
            return MarketData::where('symbol', $symbol)
                ->where('timeframe', $timeframe)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get candles in date range
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return \Illuminate\Support\Collection
     */
    public function getRange(string $symbol, string $timeframe, int $startTimestamp, int $endTimestamp)
    {
        return MarketData::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->whereBetween('timestamp', [$startTimestamp, $endTimestamp])
            ->orderBy('timestamp', 'asc')
            ->get();
    }

    /**
     * Get single candle by timestamp
     * 
     * @param string $symbol
     * @param string $timeframe
     * @param int $timestamp
     * @return MarketData|null
     */
    public function getByTimestamp(string $symbol, string $timeframe, int $timestamp): ?MarketData
    {
        return MarketData::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->where('timestamp', $timestamp)
            ->first();
    }

    /**
     * Check if candle exists
     * 
     * @param DataConnection $connection
     * @param string $symbol
     * @param string $timeframe
     * @param int $timestamp
     * @return bool
     */
    public function exists(DataConnection $connection, string $symbol, string $timeframe, int $timestamp): bool
    {
        return MarketData::where('data_connection_id', $connection->id)
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->where('timestamp', $timestamp)
            ->exists();
    }

    /**
     * Get latest timestamp for a symbol/timeframe
     * 
     * @param string $symbol
     * @param string $timeframe
     * @return int|null Latest timestamp or null if no data
     */
    public function getLatestTimestamp(string $symbol, string $timeframe): ?int
    {
        $latest = MarketData::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('timestamp', 'desc')
            ->value('timestamp');

        return $latest;
    }

    /**
     * Cleanup old data based on retention policy
     * 
     * @param int $retentionDays Number of days to keep
     * @return int Number of rows deleted
     */
    public function cleanup(int $retentionDays): int
    {
        $cutoffTimestamp = now()->subDays($retentionDays)->timestamp;

        try {
            $deleted = MarketData::where('timestamp', '<', $cutoffTimestamp)->delete();
            
            \Log::info('Market data cleanup completed', [
                'retention_days' => $retentionDays,
                'rows_deleted' => $deleted,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            \Log::error('Market data cleanup failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get data statistics
     * 
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_candles' => MarketData::count(),
            'symbols_count' => MarketData::distinct('symbol')->count('symbol'),
            'timeframes' => MarketData::distinct('timeframe')->pluck('timeframe')->toArray(),
            'oldest_data' => MarketData::min('timestamp'),
            'newest_data' => MarketData::max('timestamp'),
            'storage_size_mb' => $this->getTableSizeMB(),
        ];
    }

    /**
     * Clear cache for symbol/timeframe
     */
    protected function clearCache(string $symbol, string $timeframe): void
    {
        $patterns = [
            $this->getCacheKey($symbol, $timeframe, '*'),
        ];

        foreach ($patterns as $pattern) {
            Cache::flush(); // Simple flush for now, can be optimized with tags
        }
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(string $symbol, string $timeframe, string $type = 'latest', ?int $param = null): string
    {
        $key = "market_data:{$symbol}:{$timeframe}:{$type}";
        
        if ($param !== null) {
            $key .= ":{$param}";
        }

        return $key;
    }

    /**
     * Get table size in MB
     */
    protected function getTableSizeMB(): float
    {
        try {
            $result = DB::select("
                SELECT 
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'market_data'
            ");

            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

