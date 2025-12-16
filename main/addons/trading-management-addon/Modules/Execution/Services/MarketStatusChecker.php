<?php

namespace Addons\TradingManagement\Modules\Execution\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * MarketStatusChecker
 * 
 * Validates market data freshness and market status before trade execution
 * Prevents trades when market is closed or data is stale
 */
class MarketStatusChecker
{
    /**
     * Maximum age of market data in minutes before considering it stale
     * Different thresholds for different timeframes
     */
    protected const MAX_DATA_AGE_MINUTES = [
        '1m' => 5,      // 1-minute chart should be < 5 minutes old
        '5m' => 15,     // 5-minute chart should be < 15 minutes old
        '15m' => 30,    // 15-minute chart should be < 30 minutes old
        '1h' => 120,    // 1-hour chart should be < 2 hours old
        '4h' => 480,    // 4-hour chart should be < 8 hours old
        '1d' => 1440,   // 1-day chart should be < 24 hours old
    ];

    /**
     * Check if market data is fresh enough for trading
     * 
     * @param string $symbol Trading symbol
     * @param string $timeframe Timeframe (1m, 5m, 1h, etc.)
     * @param string|null $accountId MetaAPI account ID (for Redis lookup)
     * @param int|null $botId Bot ID for logging
     * @return array ['is_fresh' => bool, 'age_minutes' => float, 'status' => string]
     */
    public function checkMarketDataFreshness(
        string $symbol, 
        string $timeframe, 
        ?string $accountId = null, 
        ?int $botId = null
    ): array {
        $maxAgeMinutes = $this->getMaxAgeForTimeframe($timeframe);
        
        // Try to get latest candle from Redis (for MetaAPI)
        if ($accountId) {
            $redisPrefix = config('trading-management.metaapi.stream_redis_prefix', 'metaapi:stream');
            $candlesCacheKey = sprintf('%s:%s:%s:%s:candles', $redisPrefix, $accountId, $symbol, $timeframe);
            
            $candlesList = Redis::lrange($candlesCacheKey, -1, -1);
            
            if (!empty($candlesList)) {
                try {
                    $latestCandleJson = $candlesList[0];
                    $latestCandle = json_decode($latestCandleJson, true);
                    
                    if ($latestCandle && isset($latestCandle['timestamp'])) {
                        $timestamp = $latestCandle['timestamp'];
                        $ageMinutes = (time() * 1000 - $timestamp) / 60000;
                        
                        $isFresh = $ageMinutes <= $maxAgeMinutes;
                        $status = $isFresh ? 'fresh' : 'stale';
                        
                        if (!$isFresh) {
                            Log::warning('MarketStatusChecker: Stale market data detected', [
                                'bot_id' => $botId,
                                'symbol' => $symbol,
                                'timeframe' => $timeframe,
                                'age_minutes' => round($ageMinutes, 2),
                                'max_age_minutes' => $maxAgeMinutes,
                                'last_candle_timestamp' => $timestamp,
                                'current_timestamp' => time() * 1000,
                                'likely_reason' => 'Market may be closed or data stream disconnected',
                            ]);
                        }
                        
                        return [
                            'is_fresh' => $isFresh,
                            'age_minutes' => round($ageMinutes, 2),
                            'status' => $status,
                            'last_timestamp' => $timestamp,
                            'max_age_minutes' => $maxAgeMinutes,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('MarketStatusChecker: Error parsing candle data', [
                        'bot_id' => $botId,
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // No data available
        Log::warning('MarketStatusChecker: No market data available', [
            'bot_id' => $botId,
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'account_id' => $accountId,
        ]);
        
        return [
            'is_fresh' => false,
            'age_minutes' => null,
            'status' => 'no_data',
            'last_timestamp' => null,
            'max_age_minutes' => $maxAgeMinutes,
        ];
    }

    /**
     * Validate if trade execution should proceed based on market data freshness
     * 
     * @param array $executionData Execution data containing symbol, timeframe, bot_id
     * @param string|null $accountId MetaAPI account ID
     * @param bool $isTestMode Whether bot is in test mode (skip validation)
     * @return array ['should_proceed' => bool, 'reason' => string, 'freshness_check' => array]
     */
    public function validateTradeExecution(
        array $executionData, 
        ?string $accountId = null, 
        bool $isTestMode = false
    ): array {
        $botId = $executionData['bot_id'] ?? null;
        $symbol = $executionData['symbol'] ?? null;
        $timeframe = $executionData['timeframe'] ?? '1h'; // Default timeframe
        
        // Skip validation in test mode
        if ($isTestMode) {
            Log::info('MarketStatusChecker: Test mode - skipping market validation', [
                'bot_id' => $botId,
                'symbol' => $symbol,
            ]);
            
            return [
                'should_proceed' => true,
                'reason' => 'Test mode - validation skipped',
                'freshness_check' => ['is_fresh' => true, 'status' => 'test_mode'],
            ];
        }
        
        if (!$symbol) {
            return [
                'should_proceed' => false,
                'reason' => 'Missing symbol',
                'freshness_check' => ['is_fresh' => false, 'status' => 'error'],
            ];
        }
        
        $freshnessCheck = $this->checkMarketDataFreshness($symbol, $timeframe, $accountId, $botId);
        
        if (!$freshnessCheck['is_fresh']) {
            $reason = $this->buildRejectionReason($freshnessCheck);
            
            Log::warning('MarketStatusChecker: Trade execution rejected', [
                'bot_id' => $botId,
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'reason' => $reason,
                'freshness_check' => $freshnessCheck,
            ]);
            
            return [
                'should_proceed' => false,
                'reason' => $reason,
                'freshness_check' => $freshnessCheck,
            ];
        }
        
        Log::info('MarketStatusChecker: Trade validation passed', [
            'bot_id' => $botId,
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'data_age_minutes' => $freshnessCheck['age_minutes'],
        ]);
        
        return [
            'should_proceed' => true,
            'reason' => 'Market data is fresh',
            'freshness_check' => $freshnessCheck,
        ];
    }

    /**
     * Get maximum data age for timeframe
     */
    protected function getMaxAgeForTimeframe(string $timeframe): int
    {
        return self::MAX_DATA_AGE_MINUTES[$timeframe] ?? self::MAX_DATA_AGE_MINUTES['1h'];
    }

    /**
     * Build human-readable rejection reason
     */
    protected function buildRejectionReason(array $freshnessCheck): string
    {
        if ($freshnessCheck['status'] === 'no_data') {
            return 'No market data available - market may be closed or data stream disconnected';
        }
        
        if ($freshnessCheck['status'] === 'stale') {
            $age = $freshnessCheck['age_minutes'];
            $max = $freshnessCheck['max_age_minutes'];
            
            return sprintf(
                'Market data is too old (%s minutes old, max %s minutes) - market likely closed',
                round($age, 1),
                $max
            );
        }
        
        return 'Market validation failed';
    }
}

