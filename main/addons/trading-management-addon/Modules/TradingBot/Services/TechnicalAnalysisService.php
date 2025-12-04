<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Illuminate\Support\Facades\Log;

/**
 * TechnicalAnalysisService
 * 
 * Real-time technical indicator calculation from OHLCV data
 */
class TechnicalAnalysisService
{
    /**
     * Calculate all indicators from filter strategy
     * 
     * @param array $ohlcv Array of candles: [['timestamp' => int, 'open' => float, 'high' => float, 'low' => float, 'close' => float, 'volume' => float], ...]
     * @param FilterStrategy|null $filterStrategy
     * @return array Calculated indicators
     */
    public function calculateIndicators(array $ohlcv, ?FilterStrategy $filterStrategy = null): array
    {
        if (empty($ohlcv)) {
            return [];
        }

        $indicators = [];

        if ($filterStrategy && $filterStrategy->indicators) {
            foreach ($filterStrategy->indicators as $indicatorConfig) {
                $indicator = $this->getIndicatorValue(
                    $indicatorConfig['type'] ?? null,
                    $indicatorConfig['params'] ?? [],
                    $ohlcv
                );

                if ($indicator !== null) {
                    $indicators[$indicatorConfig['type']] = $indicator;
                }
            }
        }

        return $indicators;
    }

    /**
     * Calculate single indicator
     * 
     * @param string|null $indicatorType
     * @param array $params
     * @param array $ohlcv
     * @return mixed Indicator value(s)
     */
    public function getIndicatorValue(?string $indicatorType, array $params, array $ohlcv)
    {
        if (!$indicatorType || empty($ohlcv)) {
            return null;
        }

        try {
            return match ($indicatorType) {
                'SMA' => $this->calculateSMA($ohlcv, $params['period'] ?? 20),
                'EMA' => $this->calculateEMA($ohlcv, $params['period'] ?? 20),
                'RSI' => $this->calculateRSI($ohlcv, $params['period'] ?? 14),
                'MACD' => $this->calculateMACD($ohlcv, $params),
                'BB' => $this->calculateBollingerBands($ohlcv, $params),
                'STOCH' => $this->calculateStochastic($ohlcv, $params),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Failed to calculate indicator', [
                'indicator' => $indicatorType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Analyze signals from indicators
     * 
     * @param array $indicators
     * @return array ['signal' => 'buy|sell|hold', 'strength' => float, 'reason' => string]
     */
    public function analyzeSignals(array $indicators): array
    {
        $buySignals = 0;
        $sellSignals = 0;
        $reasons = [];

        // RSI analysis
        if (isset($indicators['RSI'])) {
            if ($indicators['RSI'] < 30) {
                $buySignals++;
                $reasons[] = 'RSI oversold';
            } elseif ($indicators['RSI'] > 70) {
                $sellSignals++;
                $reasons[] = 'RSI overbought';
            }
        }

        // MACD analysis
        if (isset($indicators['MACD']) && is_array($indicators['MACD'])) {
            $macd = $indicators['MACD']['macd'] ?? 0;
            $signal = $indicators['MACD']['signal'] ?? 0;
            
            if ($macd > $signal) {
                $buySignals++;
                $reasons[] = 'MACD bullish crossover';
            } elseif ($macd < $signal) {
                $sellSignals++;
                $reasons[] = 'MACD bearish crossover';
            }
        }

        // Moving average crossover
        if (isset($indicators['SMA']) && isset($indicators['EMA'])) {
            $sma = is_array($indicators['SMA']) ? end($indicators['SMA']) : $indicators['SMA'];
            $ema = is_array($indicators['EMA']) ? end($indicators['EMA']) : $indicators['EMA'];
            
            if ($ema > $sma) {
                $buySignals++;
                $reasons[] = 'EMA above SMA';
            } elseif ($ema < $sma) {
                $sellSignals++;
                $reasons[] = 'EMA below SMA';
            }
        }

        // Determine signal
        if ($buySignals > $sellSignals) {
            return [
                'signal' => 'buy',
                'strength' => $buySignals / max($buySignals + $sellSignals, 1),
                'reason' => implode(', ', $reasons),
            ];
        } elseif ($sellSignals > $buySignals) {
            return [
                'signal' => 'sell',
                'strength' => $sellSignals / max($buySignals + $sellSignals, 1),
                'reason' => implode(', ', $reasons),
            ];
        }

        return [
            'signal' => 'hold',
            'strength' => 0,
            'reason' => 'No clear signal',
        ];
    }

    /**
     * Calculate Simple Moving Average (SMA)
     */
    protected function calculateSMA(array $ohlcv, int $period): ?float
    {
        if (count($ohlcv) < $period) {
            return null;
        }

        $closes = array_column(array_slice($ohlcv, -$period), 'close');
        return array_sum($closes) / $period;
    }

    /**
     * Calculate Exponential Moving Average (EMA)
     */
    protected function calculateEMA(array $ohlcv, int $period): ?float
    {
        if (count($ohlcv) < $period) {
            return null;
        }

        $multiplier = 2 / ($period + 1);
        $closes = array_column($ohlcv, 'close');
        
        // Start with SMA
        $sma = array_sum(array_slice($closes, 0, $period)) / $period;
        $ema = $sma;

        // Calculate EMA for remaining values
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] - $ema) * $multiplier + $ema;
        }

        return $ema;
    }

    /**
     * Calculate Relative Strength Index (RSI)
     */
    protected function calculateRSI(array $ohlcv, int $period = 14): ?float
    {
        if (count($ohlcv) < $period + 1) {
            return null;
        }

        $closes = array_column($ohlcv, 'close');
        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    /**
     * Calculate MACD
     */
    protected function calculateMACD(array $ohlcv, array $params): ?array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;

        $fastEMA = $this->calculateEMA($ohlcv, $fastPeriod);
        $slowEMA = $this->calculateEMA($ohlcv, $slowPeriod);

        if ($fastEMA === null || $slowEMA === null) {
            return null;
        }

        $macd = $fastEMA - $slowEMA;

        // Signal line (EMA of MACD) - simplified
        $signal = $macd; // TODO: Calculate proper signal line

        return [
            'macd' => $macd,
            'signal' => $signal,
            'histogram' => $macd - $signal,
        ];
    }

    /**
     * Calculate Bollinger Bands
     */
    protected function calculateBollingerBands(array $ohlcv, array $params): ?array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;

        $sma = $this->calculateSMA($ohlcv, $period);
        if ($sma === null) {
            return null;
        }

        $closes = array_column(array_slice($ohlcv, -$period), 'close');
        $variance = 0;
        foreach ($closes as $close) {
            $variance += pow($close - $sma, 2);
        }
        $variance = $variance / $period;
        $stdDeviation = sqrt($variance);

        return [
            'upper' => $sma + ($stdDeviation * $stdDev),
            'middle' => $sma,
            'lower' => $sma - ($stdDeviation * $stdDev),
        ];
    }

    /**
     * Calculate Stochastic Oscillator
     */
    protected function calculateStochastic(array $ohlcv, array $params): ?array
    {
        $period = $params['period'] ?? 14;
        
        if (count($ohlcv) < $period) {
            return null;
        }

        $recent = array_slice($ohlcv, -$period);
        $highs = array_column($recent, 'high');
        $lows = array_column($recent, 'low');
        $closes = array_column($recent, 'close');

        $highestHigh = max($highs);
        $lowestLow = min($lows);
        $currentClose = end($closes);

        if ($highestHigh == $lowestLow) {
            return null;
        }

        $k = (($currentClose - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
        $d = $k; // Simplified - should be SMA of %K

        return [
            'k' => $k,
            'd' => $d,
        ];
    }
}
