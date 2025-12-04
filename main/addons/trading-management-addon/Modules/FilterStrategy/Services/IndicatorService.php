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
}

