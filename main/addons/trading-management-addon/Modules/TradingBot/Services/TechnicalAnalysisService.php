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
            Log::warning('TechnicalAnalysisService: Empty OHLCV data provided', []);
            return [];
        }

        $candleCount = count($ohlcv);
        $indicators = [];

        // Handle Test filter type - return test indicators for immediate trade
        if ($filterStrategy && $filterStrategy->filter_type === 'test') {
            Log::info('TechnicalAnalysisService: Test filter detected, returning test signals', [
                'filter_id' => $filterStrategy->id,
                'filter_name' => $filterStrategy->name,
            ]);
            
            // Return test indicators that will trigger immediate buy
            return [
                'TEST_MODE' => true,
                'RSI' => 30, // Oversold condition
                'SIGNAL' => 'BUY',
                'STRENGTH' => 100,
            ];
        }

        // Handle None filter type - skip all calculations
        if ($filterStrategy && $filterStrategy->filter_type === 'none') {
            Log::info('TechnicalAnalysisService: None filter detected, skipping calculations', [
                'filter_id' => $filterStrategy->id,
                'filter_name' => $filterStrategy->name,
            ]);
            return [];
        }

        if ($filterStrategy && $filterStrategy->indicators) {
            foreach ($filterStrategy->indicators as $indicatorConfig) {
                $indicatorType = $indicatorConfig['type'] ?? null;
                $params = $indicatorConfig['params'] ?? [];
                
                // Check minimum data requirement for this indicator
                $minRequired = $this->getMinCandlesRequired($indicatorType, $params);
                
                if ($candleCount < $minRequired) {
                    Log::warning('TechnicalAnalysisService: Insufficient data for indicator', [
                        'indicator' => $indicatorType,
                        'candle_count' => $candleCount,
                        'min_required' => $minRequired,
                        'note' => 'Skipping indicator calculation',
                    ]);
                    continue;
                }
                
                $indicator = $this->getIndicatorValue($indicatorType, $params, $ohlcv);

                if ($indicator !== null) {
                    $indicators[$indicatorType] = $indicator;
                } else {
                    Log::debug('TechnicalAnalysisService: Indicator calculation returned null', [
                        'indicator' => $indicatorType,
                        'candle_count' => $candleCount,
                    ]);
                }
            }
        } else {
            // NO FILTER STRATEGY: Do NOT calculate any indicators
            // This fixes the bug where indicators were being calculated even when filter was set to "no filter"
            Log::info('TechnicalAnalysisService: No filter strategy provided, skipping all calculations', [
                'candle_count' => $candleCount,
                'note' => 'Indicators will only be calculated when a filter strategy is assigned',
            ]);
            
            // Return empty array - no indicators calculated
            return [];
        }
        
        if (empty($indicators)) {
            Log::warning('TechnicalAnalysisService: No indicators calculated', [
                'candle_count' => $candleCount,
                'has_filter_strategy' => !is_null($filterStrategy),
                'note' => 'Insufficient data for any indicator calculation',
            ]);
        }

        return $indicators;
    }
    
    /**
     * Get minimum candles required for indicator
     * 
     * @param string|null $indicatorType
     * @param array $params
     * @return int
     */
    protected function getMinCandlesRequired(?string $indicatorType, array $params): int
    {
        if (!$indicatorType) {
            return 1;
        }
        
        switch ($indicatorType) {
            case 'SMA':
            case 'EMA':
                return $params['period'] ?? 20;
            case 'RSI':
                return ($params['period'] ?? 14) + 1;
            case 'MACD':
                $fast = $params['fast'] ?? 12;
                $slow = $params['slow'] ?? 26;
                $signal = $params['signal'] ?? 9;
                return max($fast, $slow) + $signal;
            case 'BB':
                return $params['period'] ?? 20;
            case 'STOCH':
                return $params['period'] ?? 14;
            default:
                return 20; // Default minimum
        }
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
            switch ($indicatorType) {
                case 'SMA':
                    return $this->calculateSMA($ohlcv, $params['period'] ?? 20);
                case 'EMA':
                    return $this->calculateEMA($ohlcv, $params['period'] ?? 20);
                case 'RSI':
                    return $this->calculateRSI($ohlcv, $params['period'] ?? 14);
                case 'MACD':
                    return $this->calculateMACD($ohlcv, $params);
                case 'BB':
                    return $this->calculateBollingerBands($ohlcv, $params); 
                case 'STOCH':
                    return $this->calculateStochastic($ohlcv, $params);
                default:
                    return null;
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
     * @param array|null $ohlcv Optional OHLCV data for fallback price action signals
     * @return array ['signal' => 'buy|sell|hold', 'strength' => float, 'reason' => string]
     */
    public function analyzeSignals(array $indicators, ?array $ohlcv = null): array
    {
        // Handle Test Mode - return immediate buy signal
        if (isset($indicators['TEST_MODE']) && $indicators['TEST_MODE'] === true) {
            Log::info('TechnicalAnalysisService: Test mode active, returning immediate buy signal', [
                'signal' => $indicators['SIGNAL'] ?? 'BUY',
            ]);
            
            return [
                'signal' => strtolower($indicators['SIGNAL'] ?? 'buy'),
                'strength' => ($indicators['STRENGTH'] ?? 100) / 100,
                'reason' => 'Test mode: Immediate trade for testing bot functionality',
                'test_mode' => true,
            ];
        }

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

        // Fallback: If no indicators but we have price data, use simple price action
        if (empty($indicators) && !empty($ohlcv) && count($ohlcv) >= 2) {
            return $this->generatePriceActionSignal($ohlcv);
        }

        return [
            'signal' => 'hold',
            'strength' => 0,
            'reason' => 'No clear signal',
        ];
    }
    
    /**
     * Generate simple price action signal when indicators are not available
     * 
     * @param array $ohlcv
     * @return array ['signal' => 'buy|sell|hold', 'strength' => float, 'reason' => string]
     */
    protected function generatePriceActionSignal(array $ohlcv): array
    {
        if (count($ohlcv) < 2) {
            return [
                'signal' => 'hold',
                'strength' => 0,
                'reason' => 'Insufficient data for price action signal',
            ];
        }
        
        // Get last 2 candles
        $current = end($ohlcv);
        $previous = $ohlcv[count($ohlcv) - 2];
        
        $currentClose = $current['close'] ?? 0;
        $previousClose = $previous['close'] ?? 0;
        $currentHigh = $current['high'] ?? 0;
        $currentLow = $current['low'] ?? 0;
        
        if ($currentClose <= 0 || $previousClose <= 0) {
            return [
                'signal' => 'hold',
                'strength' => 0,
                'reason' => 'Invalid price data',
            ];
        }
        
        $priceChange = (($currentClose - $previousClose) / $previousClose) * 100;
        $priceRange = $currentHigh - $currentLow;
        $rangePercent = $priceRange > 0 ? ($priceRange / $currentClose) * 100 : 0;
        
        // Simple momentum-based signal
        // Buy if price increased and range is reasonable
        // Sell if price decreased and range is reasonable
        if ($priceChange > 0.1 && $rangePercent < 2) {
            // Price increased with low volatility
            return [
                'signal' => 'buy',
                'strength' => min(0.4, abs($priceChange) / 2), // Weak signal, max 0.4 strength
                'reason' => 'Price action: upward momentum',
            ];
        } elseif ($priceChange < -0.1 && $rangePercent < 2) {
            // Price decreased with low volatility
            return [
                'signal' => 'sell',
                'strength' => min(0.4, abs($priceChange) / 2), // Weak signal, max 0.4 strength
                'reason' => 'Price action: downward momentum',
            ];
        }
        
        return [
            'signal' => 'hold',
            'strength' => 0,
            'reason' => 'Price action: no clear momentum',
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

        $closes = array_column($ohlcv, 'close');
        if (count($closes) < max($fastPeriod, $slowPeriod) + $signalPeriod) {
            return null;
        }

        $fastSeries = $this->emaSeries($closes, $fastPeriod);
        $slowSeries = $this->emaSeries($closes, $slowPeriod);

        // Align series by index
        $alignStart = max($fastPeriod, $slowPeriod) - 1;
        $macdSeries = [];
        for ($i = $alignStart; $i < count($closes); $i++) {
            $fastIdx = $i - ($fastPeriod - 1);
            $slowIdx = $i - ($slowPeriod - 1);
            if (!isset($fastSeries[$fastIdx]) || !isset($slowSeries[$slowIdx])) {
                continue;
            }
            $macdSeries[] = $fastSeries[$fastIdx] - $slowSeries[$slowIdx];
        }

        if (count($macdSeries) < $signalPeriod) {
            return null;
        }

        $signalSeries = $this->emaSeries($macdSeries, $signalPeriod);
        $macd = end($macdSeries);
        $signal = end($signalSeries);

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

    /**
     * Compute EMA series for an array of closes.
     * Returns array of EMA values starting at index (period-1).
     */
    protected function emaSeries(array $values, int $period): array
    {
        $series = [];
        if (count($values) < $period) {
            return $series;
        }
        $multiplier = 2 / ($period + 1);
        $sma = array_sum(array_slice($values, 0, $period)) / $period;
        $ema = $sma;
        $series[] = $ema;
        for ($i = $period; $i < count($values); $i++) {
            $ema = ($values[$i] - $ema) * $multiplier + $ema;
            $series[] = $ema;
        }
        return $series;
    }
}
