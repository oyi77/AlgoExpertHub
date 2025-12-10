<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Services;

/**
 * Indicator Service
 * 
 * Migrated from filter-strategy-addon
 * Calculates technical indicators (EMA, Stochastic, PSAR)
 */
class IndicatorService
{
    /**
     * Calculate EMA (Exponential Moving Average)
     */
    public function calculateEMA(array $candles, int $period): array
    {
        if (count($candles) < $period) {
            return array_fill(0, count($candles), null);
        }

        $multiplier = 2 / ($period + 1);
        $ema = [];
        $sum = 0;

        // First EMA value is SMA
        for ($i = 0; $i < $period; $i++) {
            $sum += (float) $candles[$i]['close'];
            $ema[$i] = null;
        }
        $ema[$period - 1] = $sum / $period;

        // Calculate EMA for remaining candles
        for ($i = $period; $i < count($candles); $i++) {
            $close = (float) $candles[$i]['close'];
            $ema[$i] = ($close - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }

        return $ema;
    }

    /**
     * Calculate Stochastic Oscillator
     */
    public function calculateStochastic(
        array $candles,
        int $kPeriod = 14,
        int $dPeriod = 3,
        int $smooth = 3
    ): array {
        $k = [];
        $d = [];

        if (count($candles) < $kPeriod) {
            return [
                'k' => array_fill(0, count($candles), null),
                'd' => array_fill(0, count($candles), null),
            ];
        }

        // Calculate raw %K values
        $rawK = [];
        for ($i = $kPeriod - 1; $i < count($candles); $i++) {
            $highs = [];
            $lows = [];
            
            for ($j = $i - $kPeriod + 1; $j <= $i; $j++) {
                $highs[] = (float) $candles[$j]['high'];
                $lows[] = (float) $candles[$j]['low'];
            }

            $highestHigh = max($highs);
            $lowestLow = min($lows);
            $close = (float) $candles[$i]['close'];

            if ($highestHigh - $lowestLow == 0) {
                $rawK[$i] = 50;
            } else {
                $rawK[$i] = (($close - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            }
        }

        // Smooth %K
        for ($i = 0; $i < $kPeriod - 1; $i++) {
            $k[$i] = null;
        }

        for ($i = $kPeriod - 1; $i < count($candles); $i++) {
            if ($i < $kPeriod - 1 + $smooth - 1) {
                $k[$i] = $rawK[$i];
            } else {
                $sum = 0;
                for ($j = $i - $smooth + 1; $j <= $i; $j++) {
                    $sum += $rawK[$j];
                }
                $k[$i] = $sum / $smooth;
            }
        }

        // Calculate %D (SMA of %K)
        for ($i = 0; $i < $kPeriod - 1 + $smooth - 1 + $dPeriod - 1; $i++) {
            $d[$i] = null;
        }

        for ($i = $kPeriod - 1 + $smooth - 1 + $dPeriod - 1; $i < count($candles); $i++) {
            $sum = 0;
            for ($j = $i - $dPeriod + 1; $j <= $i; $j++) {
                $sum += $k[$j];
            }
            $d[$i] = $sum / $dPeriod;
        }

        return ['k' => $k, 'd' => $d];
    }

    /**
     * Calculate Parabolic SAR
     */
    public function calculatePSAR(
        array $candles,
        float $step = 0.02,
        float $max = 0.2
    ): array {
        if (count($candles) < 2) {
            return array_fill(0, count($candles), null);
        }

        $psar = [];
        $af = $step;
        $ep = (float) $candles[0]['low'];
        $trend = 1; // 1 = uptrend, -1 = downtrend
        $sar = (float) $candles[0]['low'];

        $psar[0] = $sar;

        for ($i = 1; $i < count($candles); $i++) {
            $high = (float) $candles[$i]['high'];
            $low = (float) $candles[$i]['low'];
            $prevHigh = (float) $candles[$i - 1]['high'];
            $prevLow = (float) $candles[$i - 1]['low'];

            if ($trend == 1) {
                // Uptrend
                $sar = $sar + $af * ($ep - $sar);
                
                if ($i >= 2) {
                    $sar = min($sar, $prevLow, (float) $candles[$i - 2]['low']);
                } else {
                    $sar = min($sar, $prevLow);
                }

                if ($low < $sar) {
                    $trend = -1;
                    $sar = $ep;
                    $ep = $low;
                    $af = $step;
                } else {
                    if ($high > $ep) {
                        $ep = $high;
                        $af = min($af + $step, $max);
                    }
                }
            } else {
                // Downtrend
                $sar = $sar + $af * ($ep - $sar);
                
                if ($i >= 2) {
                    $sar = max($sar, $prevHigh, (float) $candles[$i - 2]['high']);
                } else {
                    $sar = max($sar, $prevHigh);
                }

                if ($high > $sar) {
                    $trend = 1;
                    $sar = $ep;
                    $ep = $high;
                    $af = $step;
                } else {
                    if ($low < $ep) {
                        $ep = $low;
                        $af = min($af + $step, $max);
                    }
                }
            }

            $psar[$i] = $sar;
        }

        return $psar;
    }

    /**
     * Calculate Fibonacci Retracement Levels
     * 
     * @param array $candles Array of candles [['open' => float, 'high' => float, 'low' => float, 'close' => float], ...]
     * @param array $levels Retracement levels (e.g., [0.236, 0.382, 0.5, 0.618, 0.11, 0.61])
     * @param int $lookback Number of candles to look back for swing high/low
     * @return array ['swing_high' => float, 'swing_low' => float, 'range' => float, 'levels' => array, 'zones' => array]
     */
    public function calculateFibonacciRetracement(
        array $candles,
        array $levels = [0.236, 0.382, 0.5, 0.618],
        int $lookback = 20
    ): array {
        if (count($candles) < 2) {
            return [
                'swing_high' => null,
                'swing_low' => null,
                'range' => null,
                'levels' => [],
                'zones' => [],
            ];
        }

        // Use last N candles for swing detection
        $lookbackCandles = array_slice($candles, -min($lookback, count($candles)));
        
        // Find swing high and swing low
        $highs = array_column($lookbackCandles, 'high');
        $lows = array_column($lookbackCandles, 'low');
        
        $swingHigh = max($highs);
        $swingLow = min($lows);
        $range = $swingHigh - $swingLow;

        if ($range <= 0) {
            return [
                'swing_high' => $swingHigh,
                'swing_low' => $swingLow,
                'range' => 0,
                'levels' => [],
                'zones' => [],
            ];
        }

        // Calculate retracement levels
        $retracementLevels = [];
        $zones = [];
        $tolerance = $range * 0.001; // 0.1% tolerance for zone matching

        foreach ($levels as $level) {
            $retracementPrice = $swingHigh - ($range * $level);
            $retracementLevels[$level] = $retracementPrice;
            
            // Create zone (upper and lower bounds)
            $zones[] = [
                'level' => $level,
                'price' => $retracementPrice,
                'upper' => $retracementPrice + $tolerance,
                'lower' => $retracementPrice - $tolerance,
            ];
        }

        return [
            'swing_high' => $swingHigh,
            'swing_low' => $swingLow,
            'range' => $range,
            'levels' => $retracementLevels,
            'zones' => $zones,
        ];
    }

    /**
     * Calculate Support and Resistance Levels
     * 
     * @param array $candles Array of candles [['open' => float, 'high' => float, 'low' => float, 'close' => float], ...]
     * @param int $lookback Number of candles to analyze
     * @param float $minStrength Minimum strength (0-1) for S/R level to be considered
     * @return array ['support' => array, 'resistance' => array, 'breakouts' => array]
     */
    public function calculateSupportResistance(
        array $candles,
        int $lookback = 20,
        float $minStrength = 0.5
    ): array {
        if (count($candles) < 5) {
            return [
                'support' => [],
                'resistance' => [],
                'breakouts' => [],
            ];
        }

        // Use last N candles
        $analysisCandles = array_slice($candles, -min($lookback, count($candles)));
        
        // Identify swing highs (resistance) and swing lows (support)
        $swingHighs = [];
        $swingLows = [];
        
        // Look for local maxima (swing highs) and minima (swing lows)
        // A swing high is a high that is higher than its neighbors
        // A swing low is a low that is lower than its neighbors
        for ($i = 2; $i < count($analysisCandles) - 2; $i++) {
            $high = (float) $analysisCandles[$i]['high'];
            $low = (float) $analysisCandles[$i]['low'];
            
            // Check if it's a swing high
            $prevHigh = (float) $analysisCandles[$i - 1]['high'];
            $nextHigh = (float) $analysisCandles[$i + 1]['high'];
            $prev2High = (float) $analysisCandles[$i - 2]['high'];
            $next2High = (float) $analysisCandles[$i + 2]['high'];
            
            if ($high >= $prevHigh && $high >= $nextHigh && 
                $high >= $prev2High && $high >= $next2High) {
                $swingHighs[] = [
                    'price' => $high,
                    'index' => $i,
                    'timestamp' => $analysisCandles[$i]['timestamp'] ?? null,
                ];
            }
            
            // Check if it's a swing low
            $prevLow = (float) $analysisCandles[$i - 1]['low'];
            $nextLow = (float) $analysisCandles[$i + 1]['low'];
            $prev2Low = (float) $analysisCandles[$i - 2]['low'];
            $next2Low = (float) $analysisCandles[$i + 2]['low'];
            
            if ($low <= $prevLow && $low <= $nextLow && 
                $low <= $prev2Low && $low <= $next2Low) {
                $swingLows[] = [
                    'price' => $low,
                    'index' => $i,
                    'timestamp' => $analysisCandles[$i]['timestamp'] ?? null,
                ];
            }
        }

        // Group similar levels (within 0.1% tolerance)
        $tolerance = 0.001; // 0.1%
        
        $resistanceLevels = $this->groupSimilarLevels($swingHighs, $tolerance, $analysisCandles);
        $supportLevels = $this->groupSimilarLevels($swingLows, $tolerance, $analysisCandles);
        
        // Filter by minimum strength
        $resistanceLevels = array_filter($resistanceLevels, function($level) use ($minStrength) {
            return $level['strength'] >= $minStrength;
        });
        $supportLevels = array_filter($supportLevels, function($level) use ($minStrength) {
            return $level['strength'] >= $minStrength;
        });

        // Detect breakouts
        $breakouts = $this->detectBreakouts($analysisCandles, $resistanceLevels, $supportLevels);

        return [
            'support' => array_values($supportLevels),
            'resistance' => array_values($resistanceLevels),
            'breakouts' => $breakouts,
        ];
    }

    /**
     * Group similar price levels together
     * 
     * @param array $levels Array of ['price' => float, 'index' => int, ...]
     * @param float $tolerance Price tolerance for grouping
     * @param array $candles All candles for calculating rejection patterns
     * @return array Grouped levels with strength
     */
    protected function groupSimilarLevels(array $levels, float $tolerance, array $candles): array
    {
        if (empty($levels)) {
            return [];
        }

        $grouped = [];
        
        foreach ($levels as $level) {
            $price = $level['price'];
            $found = false;
            
            // Check if this price is close to an existing group
            foreach ($grouped as $key => $group) {
                $groupPrice = $group['price'];
                $priceDiff = abs($price - $groupPrice) / $groupPrice;
                
                if ($priceDiff <= $tolerance) {
                    // Add to existing group
                    $grouped[$key]['touches']++;
                    $grouped[$key]['last_touch'] = $level['timestamp'] ?? $level['index'];
                    $grouped[$key]['strength'] = min(1.0, $grouped[$key]['touches'] / 5.0); // Max strength at 5 touches
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Create new group
                $grouped[] = [
                    'price' => $price,
                    'strength' => 0.2, // Initial strength
                    'touches' => 1,
                    'last_touch' => $level['timestamp'] ?? $level['index'],
                ];
            }
        }

        // Calculate rejection patterns (wicks touching levels)
        foreach ($grouped as $key => $group) {
            $rejections = $this->countRejections($candles, $group['price'], $tolerance);
            $grouped[$key]['rejections'] = $rejections;
            $grouped[$key]['strength'] = min(1.0, ($grouped[$key]['touches'] + $rejections) / 5.0);
        }

        return $grouped;
    }

    /**
     * Count rejection patterns (wicks touching a level)
     * 
     * @param array $candles
     * @param float $level
     * @param float $tolerance
     * @return int
     */
    protected function countRejections(array $candles, float $level, float $tolerance): int
    {
        $rejections = 0;
        $tolerancePrice = $level * $tolerance;
        
        foreach ($candles as $candle) {
            $high = (float) $candle['high'];
            $low = (float) $candle['low'];
            $close = (float) $candle['close'];
            
            // Check if wick touched the level
            // For resistance: high touched level but close is below
            if (abs($high - $level) <= $tolerancePrice && $close < $level) {
                $rejections++;
            }
            // For support: low touched level but close is above
            if (abs($low - $level) <= $tolerancePrice && $close > $level) {
                $rejections++;
            }
        }
        
        return $rejections;
    }

    /**
     * Detect breakouts and retests
     * 
     * @param array $candles
     * @param array $resistanceLevels
     * @param array $supportLevels
     * @return array
     */
    protected function detectBreakouts(array $candles, array $resistanceLevels, array $supportLevels): array
    {
        $breakouts = [];
        
        if (empty($candles)) {
            return $breakouts;
        }

        $latestCandle = $candles[count($candles) - 1];
        $latestClose = (float) $latestCandle['close'];
        $latestHigh = (float) $latestCandle['high'];
        $latestLow = (float) $latestCandle['low'];
        
        // Check resistance breakouts
        foreach ($resistanceLevels as $level) {
            $resistancePrice = $level['price'];
            
            // Check if price broke above resistance
            if ($latestClose > $resistancePrice || $latestHigh > $resistancePrice) {
                $breakouts[] = [
                    'level' => $resistancePrice,
                    'type' => 'resistance',
                    'breakout_price' => $latestClose,
                    'breakout_timestamp' => $latestCandle['timestamp'] ?? null,
                    'retested' => $this->checkRetest($candles, $resistancePrice, 'above'),
                ];
            }
        }
        
        // Check support breakouts
        foreach ($supportLevels as $level) {
            $supportPrice = $level['price'];
            
            // Check if price broke below support
            if ($latestClose < $supportPrice || $latestLow < $supportPrice) {
                $breakouts[] = [
                    'level' => $supportPrice,
                    'type' => 'support',
                    'breakout_price' => $latestClose,
                    'breakout_timestamp' => $latestCandle['timestamp'] ?? null,
                    'retested' => $this->checkRetest($candles, $supportPrice, 'below'),
                ];
            }
        }
        
        return $breakouts;
    }

    /**
     * Check if a level was retested after breakout
     * 
     * @param array $candles
     * @param float $level
     * @param string $direction 'above' or 'below'
     * @return bool
     */
    protected function checkRetest(array $candles, float $level, string $direction): bool
    {
        if (count($candles) < 3) {
            return false;
        }

        // Check last 3 candles for retest
        $recentCandles = array_slice($candles, -3);
        
        foreach ($recentCandles as $candle) {
            $low = (float) $candle['low'];
            $high = (float) $candle['high'];
            
            if ($direction === 'above') {
                // Resistance breakout: check if price retested from above
                if ($low <= $level && $high >= $level) {
                    return true;
                }
            } else {
                // Support breakout: check if price retested from below
                if ($high >= $level && $low <= $level) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

