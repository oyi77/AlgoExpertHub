<?php

namespace Addons\SmartRiskManagement\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarketContextService
{
    /**
     * Get Average True Range (ATR) for a symbol
     * 
     * @param string $symbol Trading symbol
     * @param Carbon|null $timestamp Timestamp (default: now)
     * @return float ATR value
     */
    public function getATR(string $symbol, ?Carbon $timestamp = null): float
    {
        $timestamp = $timestamp ?? now();
        
        try {
            // Try to get ATR from Execution Engine adapter if available
            if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
                // This would require a connection, so we'll use a simplified approach
                // In production, you might want to fetch from exchange API or calculate from price history
                return $this->calculateATRFromHistory($symbol, $timestamp);
            }
            
            // Fallback: return default ATR based on symbol type
            return $this->getDefaultATR($symbol);
        } catch (\Exception $e) {
            Log::warning("MarketContextService: Failed to get ATR for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
            return $this->getDefaultATR($symbol);
        }
    }

    /**
     * Calculate ATR from price history (simplified)
     * In production, implement proper ATR calculation from historical candles
     */
    protected function calculateATRFromHistory(string $symbol, Carbon $timestamp): float
    {
        // Simple ATR approximation using last N closes if available via cache/API
        try {
            if (function_exists('app') && app()->bound('market.price.history')) {
                $history = app('market.price.history')->get($symbol, 14);
                if (is_array($history) && count($history) >= 2) {
                    $trs = [];
                    for ($i = 1; $i < count($history); $i++) {
                        $prev = $history[$i - 1];
                        $curr = $history[$i];
                        $tr = max(
                            $curr['high'] - $curr['low'],
                            abs($curr['high'] - $prev['close']),
                            abs($curr['low'] - $prev['close'])
                        );
                        $trs[] = $tr;
                    }
                    if (!empty($trs)) {
                        return array_sum($trs) / count($trs);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('ATR history approximation failed', ['error' => $e->getMessage()]);
        }

        return $this->getDefaultATR($symbol);
    }

    /**
     * Get default ATR based on symbol type
     */
    protected function getDefaultATR(string $symbol): float
    {
        // Default ATR values in pips (simplified)
        $defaults = [
            'EURUSD' => 0.8,
            'GBPUSD' => 1.0,
            'USDJPY' => 0.7,
            'AUDUSD' => 0.9,
            'USDCAD' => 0.8,
            'XAUUSD' => 15.0, // Gold
            'BTCUSD' => 200.0, // Bitcoin
        ];

        foreach ($defaults as $pair => $atr) {
            if (stripos($symbol, $pair) !== false) {
                return $atr;
            }
        }

        // Default ATR for unknown symbols
        return 1.0;
    }

    /**
     * Get trading session based on timestamp
     * 
     * @param Carbon|null $timestamp Timestamp (default: now)
     * @return string Trading session
     */
    public function getTradingSession(?Carbon $timestamp = null): string
    {
        $timestamp = $timestamp ?? now();
        $hour = (int) $timestamp->utc()->format('H');

        // Trading sessions in UTC
        // Tokyo: 00:00 - 09:00 UTC
        // London: 08:00 - 17:00 UTC
        // New York: 13:00 - 22:00 UTC
        // Asian: 22:00 - 00:00 UTC
        // Overlap: London-New York (13:00 - 17:00 UTC)

        if ($hour >= 13 && $hour < 17) {
            return 'OVERLAP'; // London-New York overlap
        } elseif ($hour >= 0 && $hour < 9) {
            return 'TOKYO';
        } elseif ($hour >= 8 && $hour < 17) {
            return 'LONDON';
        } elseif ($hour >= 13 && $hour < 22) {
            return 'NEW_YORK';
        } else {
            return 'ASIAN'; // 22:00 - 00:00 UTC
        }
    }

    /**
     * Get day of week (1-7, Monday = 1)
     * 
     * @param Carbon|null $timestamp Timestamp (default: now)
     * @return int Day of week
     */
    public function getDayOfWeek(?Carbon $timestamp = null): int
    {
        $timestamp = $timestamp ?? now();
        // Carbon: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        // We want: 1 = Monday, 7 = Sunday
        $day = (int) $timestamp->format('w'); // 0-6
        return $day === 0 ? 7 : $day;
    }

    /**
     * Calculate volatility index
     * 
     * @param string $symbol Trading symbol
     * @param Carbon|null $timestamp Timestamp (default: now)
     * @return float Volatility index (0-100)
     */
    public function calculateVolatilityIndex(string $symbol, ?Carbon $timestamp = null): float
    {
        $timestamp = $timestamp ?? now();
        
        try {
            $atr = $this->getATR($symbol, $timestamp);
            
            // Normalize ATR to volatility index (0-100)
            // Higher ATR = higher volatility
            // This is a simplified calculation
            $maxATR = 50.0; // Maximum expected ATR
            $volatilityIndex = min(100, ($atr / $maxATR) * 100);
            
            return round($volatilityIndex, 2);
        } catch (\Exception $e) {
            Log::warning("MarketContextService: Failed to calculate volatility index for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
            return 50.0; // Default medium volatility
        }
    }

    /**
     * Get complete market context for a symbol
     * 
     * @param string $symbol Trading symbol
     * @param Carbon|null $timestamp Timestamp (default: now)
     * @return array Market context data
     */
    public function getMarketContext(string $symbol, ?Carbon $timestamp = null): array
    {
        $timestamp = $timestamp ?? now();
        
        return [
            'symbol' => $symbol,
            'atr' => $this->getATR($symbol, $timestamp),
            'trading_session' => $this->getTradingSession($timestamp),
            'day_of_week' => $this->getDayOfWeek($timestamp),
            'volatility_index' => $this->calculateVolatilityIndex($symbol, $timestamp),
            'timestamp' => $timestamp->toDateTimeString(),
        ];
    }
}
