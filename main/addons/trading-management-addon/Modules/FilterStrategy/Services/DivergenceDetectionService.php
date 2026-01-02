<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Services;

use Illuminate\Support\Facades\Log;

/**
 * Divergence Detection Service
 * 
 * Detects bullish and bearish divergences between price and indicators
 * 
 * Divergence types:
 * - Bullish: Price makes lower low, indicator makes higher low
 * - Bearish: Price makes higher high, indicator makes lower high
 */
class DivergenceDetectionService
{
    /**
     * Detect divergence between price and indicator
     * 
     * @param array $priceData Array of price values (highs/lows/closes)
     * @param array $indicatorData Array of indicator values
     * @param string $priceType 'high', 'low', or 'close' (default: 'close')
     * @param int $lookback Number of periods to look back for peaks/troughs
     * @return array ['type' => 'bullish|bearish|null', 'strength' => float, 'details' => array]
     */
    public function detectDivergence(
        array $priceData,
        array $indicatorData,
        string $priceType = 'close',
        int $lookback = 20
    ): array {
        if (count($priceData) < $lookback || count($indicatorData) < $lookback) {
            return [
                'type' => null,
                'strength' => 0,
                'reason' => 'Insufficient data for divergence detection',
            ];
        }

        // Extract price values based on type
        $prices = $this->extractPriceValues($priceData, $priceType);
        
        // Find peaks and troughs in price
        $pricePeaks = $this->findPeaks($prices, $lookback);
        $priceTroughs = $this->findTroughs($prices, $lookback);
        
        // Find peaks and troughs in indicator
        $indicatorPeaks = $this->findPeaks($indicatorData, $lookback);
        $indicatorTroughs = $this->findTroughs($indicatorData, $lookback);
        
        // Detect bullish divergence (price lower low, indicator higher low)
        $bullishDivergence = $this->detectBullishDivergence(
            $priceTroughs,
            $indicatorTroughs,
            $prices,
            $indicatorData
        );
        
        // Detect bearish divergence (price higher high, indicator lower high)
        $bearishDivergence = $this->detectBearishDivergence(
            $pricePeaks,
            $indicatorPeaks,
            $prices,
            $indicatorData
        );
        
        // Return strongest divergence
        if ($bullishDivergence['strength'] > $bearishDivergence['strength']) {
            return $bullishDivergence;
        } elseif ($bearishDivergence['strength'] > 0) {
            return $bearishDivergence;
        }
        
        return [
            'type' => null,
            'strength' => 0,
            'reason' => 'No divergence detected',
        ];
    }

    /**
     * Extract price values based on type
     */
    protected function extractPriceValues(array $priceData, string $priceType): array
    {
        $prices = [];
        
        foreach ($priceData as $candle) {
            if (is_array($candle)) {
                $prices[] = (float) ($candle[$priceType] ?? $candle['close'] ?? 0);
            } else {
                $prices[] = (float) $candle;
            }
        }
        
        return $prices;
    }

    /**
     * Find peaks (local maxima) in data
     */
    protected function findPeaks(array $data, int $lookback): array
    {
        $peaks = [];
        
        // Need at least 3 points to identify a peak
        if (count($data) < 3) {
            return $peaks;
        }
        
        // Look for local maxima
        for ($i = 1; $i < count($data) - 1; $i++) {
            // Check if current point is higher than neighbors
            if ($data[$i] > $data[$i - 1] && $data[$i] > $data[$i + 1]) {
                // Verify it's a significant peak (higher than surrounding points)
                $isSignificant = true;
                $window = min(3, $i, count($data) - $i - 1);
                
                for ($j = max(0, $i - $window); $j <= min(count($data) - 1, $i + $window); $j++) {
                    if ($j !== $i && $data[$j] >= $data[$i]) {
                        $isSignificant = false;
                        break;
                    }
                }
                
                if ($isSignificant) {
                    $peaks[] = [
                        'index' => $i,
                        'value' => $data[$i],
                    ];
                }
            }
        }
        
        // Sort by index (most recent first)
        usort($peaks, fn($a, $b) => $b['index'] <=> $a['index']);
        
        return array_slice($peaks, 0, 5); // Return last 5 peaks
    }

    /**
     * Find troughs (local minima) in data
     */
    protected function findTroughs(array $data, int $lookback): array
    {
        $troughs = [];
        
        // Need at least 3 points to identify a trough
        if (count($data) < 3) {
            return $troughs;
        }
        
        // Look for local minima
        for ($i = 1; $i < count($data) - 1; $i++) {
            // Check if current point is lower than neighbors
            if ($data[$i] < $data[$i - 1] && $data[$i] < $data[$i + 1]) {
                // Verify it's a significant trough (lower than surrounding points)
                $isSignificant = true;
                $window = min(3, $i, count($data) - $i - 1);
                
                for ($j = max(0, $i - $window); $j <= min(count($data) - 1, $i + $window); $j++) {
                    if ($j !== $i && $data[$j] <= $data[$i]) {
                        $isSignificant = false;
                        break;
                    }
                }
                
                if ($isSignificant) {
                    $troughs[] = [
                        'index' => $i,
                        'value' => $data[$i],
                    ];
                }
            }
        }
        
        // Sort by index (most recent first)
        usort($troughs, fn($a, $b) => $b['index'] <=> $a['index']);
        
        return array_slice($troughs, 0, 5); // Return last 5 troughs
    }

    /**
     * Detect bullish divergence
     * 
     * Bullish divergence: Price makes lower low, indicator makes higher low
     */
    protected function detectBullishDivergence(
        array $priceTroughs,
        array $indicatorTroughs,
        array $prices,
        array $indicatorData
    ): array {
        if (count($priceTroughs) < 2 || count($indicatorTroughs) < 2) {
            return ['type' => null, 'strength' => 0, 'reason' => 'Insufficient troughs'];
        }
        
        // Compare last two troughs
        $latestPriceTrough = $priceTroughs[0];
        $previousPriceTrough = $priceTroughs[1] ?? null;
        $latestIndicatorTrough = $indicatorTroughs[0];
        $previousIndicatorTrough = $indicatorTroughs[1] ?? null;
        
        if (!$previousPriceTrough || !$previousIndicatorTrough) {
            return ['type' => null, 'strength' => 0, 'reason' => 'Need at least 2 troughs'];
        }
        
        // Check if price made lower low
        $priceLowerLow = $latestPriceTrough['value'] < $previousPriceTrough['value'];
        
        // Check if indicator made higher low
        $indicatorHigherLow = $latestIndicatorTrough['value'] > $previousIndicatorTrough['value'];
        
        if ($priceLowerLow && $indicatorHigherLow) {
            // Calculate strength based on divergence magnitude
            $priceChange = abs($latestPriceTrough['value'] - $previousPriceTrough['value']) / max($previousPriceTrough['value'], 0.0001);
            $indicatorChange = abs($latestIndicatorTrough['value'] - $previousIndicatorTrough['value']) / max(abs($previousIndicatorTrough['value']), 0.0001);
            
            // Strength is combination of both changes (normalized 0-1)
            $strength = min(1.0, ($priceChange + $indicatorChange) * 10);
            
            return [
                'type' => 'bullish',
                'strength' => $strength,
                'reason' => 'Price lower low, indicator higher low',
                'details' => [
                    'price_trough_1' => $previousPriceTrough['value'],
                    'price_trough_2' => $latestPriceTrough['value'],
                    'indicator_trough_1' => $previousIndicatorTrough['value'],
                    'indicator_trough_2' => $latestIndicatorTrough['value'],
                ],
            ];
        }
        
        return ['type' => null, 'strength' => 0, 'reason' => 'No bullish divergence'];
    }

    /**
     * Detect bearish divergence
     * 
     * Bearish divergence: Price makes higher high, indicator makes lower high
     */
    protected function detectBearishDivergence(
        array $pricePeaks,
        array $indicatorPeaks,
        array $prices,
        array $indicatorData
    ): array {
        if (count($pricePeaks) < 2 || count($indicatorPeaks) < 2) {
            return ['type' => null, 'strength' => 0, 'reason' => 'Insufficient peaks'];
        }
        
        // Compare last two peaks
        $latestPricePeak = $pricePeaks[0];
        $previousPricePeak = $pricePeaks[1] ?? null;
        $latestIndicatorPeak = $indicatorPeaks[0];
        $previousIndicatorPeak = $indicatorPeaks[1] ?? null;
        
        if (!$previousPricePeak || !$previousIndicatorPeak) {
            return ['type' => null, 'strength' => 0, 'reason' => 'Need at least 2 peaks'];
        }
        
        // Check if price made higher high
        $priceHigherHigh = $latestPricePeak['value'] > $previousPricePeak['value'];
        
        // Check if indicator made lower high
        $indicatorLowerHigh = $latestIndicatorPeak['value'] < $previousIndicatorPeak['value'];
        
        if ($priceHigherHigh && $indicatorLowerHigh) {
            // Calculate strength based on divergence magnitude
            $priceChange = abs($latestPricePeak['value'] - $previousPricePeak['value']) / max($previousPricePeak['value'], 0.0001);
            $indicatorChange = abs($latestIndicatorPeak['value'] - $previousIndicatorPeak['value']) / max(abs($previousIndicatorPeak['value']), 0.0001);
            
            // Strength is combination of both changes (normalized 0-1)
            $strength = min(1.0, ($priceChange + $indicatorChange) * 10);
            
            return [
                'type' => 'bearish',
                'strength' => $strength,
                'reason' => 'Price higher high, indicator lower high',
                'details' => [
                    'price_peak_1' => $previousPricePeak['value'],
                    'price_peak_2' => $latestPricePeak['value'],
                    'indicator_peak_1' => $previousIndicatorPeak['value'],
                    'indicator_peak_2' => $latestIndicatorPeak['value'],
                ],
            ];
        }
        
        return ['type' => null, 'strength' => 0, 'reason' => 'No bearish divergence'];
    }

    /**
     * Detect multiple divergences in a dataset
     * 
     * @param array $priceData
     * @param array $indicatorData
     * @param string $priceType
     * @param int $lookback
     * @return array Array of divergence detections
     */
    public function detectMultipleDivergences(
        array $priceData,
        array $indicatorData,
        string $priceType = 'close',
        int $lookback = 20
    ): array {
        $divergences = [];
        
        // Use sliding window to detect multiple divergences
        $windowSize = $lookback * 2;
        
        for ($i = $windowSize; $i < count($priceData); $i += $lookback) {
            $windowPrice = array_slice($priceData, $i - $windowSize, $windowSize);
            $windowIndicator = array_slice($indicatorData, $i - $windowSize, $windowSize);
            
            $divergence = $this->detectDivergence($windowPrice, $windowIndicator, $priceType, $lookback);
            
            if ($divergence['type'] !== null && $divergence['strength'] > 0.3) {
                $divergence['window_index'] = $i;
                $divergences[] = $divergence;
            }
        }
        
        return $divergences;
    }
}

