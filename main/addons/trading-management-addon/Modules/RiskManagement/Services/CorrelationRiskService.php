<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CorrelationRiskService
 * 
 * Manages correlation risk across positions to prevent over-concentration
 */
class CorrelationRiskService
{
    /**
     * Default correlation threshold (0.7 = 70% correlation)
     */
    protected const DEFAULT_CORRELATION_THRESHOLD = 0.7;

    /**
     * Get correlation matrix for major trading pairs
     * 
     * @return array Correlation matrix [symbol1 => [symbol2 => correlation, ...], ...]
     */
    public function getCorrelationMatrix(): array
    {
        return Cache::remember('correlation_matrix', 86400, function () {
            // Major FX correlations (based on historical data)
            // These are approximate values - in production, calculate from historical price data
            return [
                // EUR pairs
                'EURUSD' => [
                    'GBPUSD' => 0.85,
                    'EURGBP' => 0.75,
                    'AUDUSD' => 0.70,
                    'NZDUSD' => 0.65,
                    'USDCHF' => -0.90, // Inverse correlation
                    'USDJPY' => 0.60,
                ],
                // GBP pairs
                'GBPUSD' => [
                    'EURUSD' => 0.85,
                    'EURGBP' => 0.80,
                    'AUDUSD' => 0.75,
                    'NZDUSD' => 0.70,
                    'GBPJPY' => 0.70,
                ],
                // USD pairs
                'USDJPY' => [
                    'EURUSD' => 0.60,
                    'GBPUSD' => 0.65,
                    'AUDUSD' => 0.55,
                    'USDCHF' => -0.80,
                ],
                'USDCHF' => [
                    'EURUSD' => -0.90, // Strong inverse
                    'GBPUSD' => -0.85,
                    'USDJPY' => -0.80,
                ],
                // AUD pairs
                'AUDUSD' => [
                    'EURUSD' => 0.70,
                    'GBPUSD' => 0.75,
                    'NZDUSD' => 0.90, // Very high correlation
                    'AUDJPY' => 0.85,
                ],
                // NZD pairs
                'NZDUSD' => [
                    'EURUSD' => 0.65,
                    'GBPUSD' => 0.70,
                    'AUDUSD' => 0.90, // Very high correlation
                ],
                // Commodities
                'XAUUSD' => [
                    'XAGUSD' => 0.85, // Gold and Silver
                    'USDCHF' => -0.70, // Inverse with USD
                ],
                'XAGUSD' => [
                    'XAUUSD' => 0.85,
                ],
                // Crypto correlations (approximate)
                'BTC/USDT' => [
                    'ETH/USDT' => 0.75,
                    'BNB/USDT' => 0.65,
                ],
                'ETH/USDT' => [
                    'BTC/USDT' => 0.75,
                    'BNB/USDT' => 0.60,
                ],
            ];
        });
    }

    /**
     * Get correlated symbols for a given symbol
     * 
     * @param string $symbol Trading symbol
     * @param float $threshold Correlation threshold (default 0.7)
     * @return array Array of correlated symbols with their correlation values
     */
    public function getCorrelatedSymbols(string $symbol, float $threshold = null): array
    {
        $threshold = $threshold ?? self::DEFAULT_CORRELATION_THRESHOLD;
        $matrix = $this->getCorrelationMatrix();
        $symbol = strtoupper($symbol);
        
        $correlated = [];
        
        if (isset($matrix[$symbol])) {
            foreach ($matrix[$symbol] as $correlatedSymbol => $correlation) {
                if (abs($correlation) >= $threshold) {
                    $correlated[$correlatedSymbol] = $correlation;
                }
            }
        }
        
        // Also check reverse correlations (if EURUSD correlates with GBPUSD, GBPUSD also correlates with EURUSD)
        foreach ($matrix as $key => $correlations) {
            if ($key !== $symbol && isset($correlations[$symbol])) {
                $correlation = $correlations[$symbol];
                if (abs($correlation) >= $threshold) {
                    $correlated[$key] = $correlation;
                }
            }
        }
        
        return $correlated;
    }

    /**
     * Calculate total exposure to correlated pairs
     * 
     * @param string $symbol New symbol to trade
     * @param array $existingPositions Existing open positions [['symbol' => 'EURUSD', 'quantity' => 1.0, 'entry_price' => 1.1000, 'direction' => 'buy'], ...]
     * @param float $newPositionValue Value of new position in account currency
     * @return array ['total_exposure' => float, 'correlated_positions' => array, 'exposure_pct' => float]
     */
    public function calculateExposure(string $symbol, array $existingPositions, float $newPositionValue, float $equity): array
    {
        $correlatedSymbols = $this->getCorrelatedSymbols($symbol);
        $symbol = strtoupper($symbol);
        
        $totalExposure = $newPositionValue; // Start with new position
        $correlatedPositions = [];
        
        foreach ($existingPositions as $position) {
            $positionSymbol = strtoupper($position['symbol'] ?? '');
            
            // Check if this position is correlated with the new symbol
            if (isset($correlatedSymbols[$positionSymbol])) {
                $correlation = $correlatedSymbols[$positionSymbol];
                
                // Calculate position value
                $positionValue = ($position['quantity'] ?? 0) * ($position['entry_price'] ?? 0);
                
                // For inverse correlations, we still count exposure (risk is still concentrated)
                // But we could weight it differently if needed
                $weightedExposure = $positionValue * abs($correlation);
                $totalExposure += $weightedExposure;
                
                $correlatedPositions[] = [
                    'symbol' => $positionSymbol,
                    'correlation' => $correlation,
                    'position_value' => $positionValue,
                    'weighted_exposure' => $weightedExposure,
                ];
            }
        }
        
        $exposurePct = $equity > 0 ? ($totalExposure / $equity) * 100 : 0;
        
        return [
            'total_exposure' => $totalExposure,
            'correlated_positions' => $correlatedPositions,
            'exposure_pct' => $exposurePct,
            'correlation_count' => count($correlatedPositions),
        ];
    }

    /**
     * Check if trade should be prevented due to correlation risk
     * 
     * @param string $newSymbol New symbol to trade
     * @param array $existingPositions Existing open positions
     * @param float $newPositionValue Value of new position
     * @param float $equity Account equity
     * @param float $maxCorrelationExposurePct Maximum % of equity allowed in correlated pairs
     * @return array ['should_prevent' => bool, 'reason' => string|null, 'exposure_data' => array]
     */
    public function shouldPreventTrade(
        string $newSymbol, 
        array $existingPositions, 
        float $newPositionValue, 
        float $equity,
        float $maxCorrelationExposurePct = 50.0
    ): array {
        $exposureData = $this->calculateExposure($newSymbol, $existingPositions, $newPositionValue, $equity);
        
        if ($exposureData['exposure_pct'] > $maxCorrelationExposurePct) {
            return [
                'should_prevent' => true,
                'reason' => sprintf(
                    'Correlation exposure (%.2f%%) exceeds maximum (%.2f%%). %d correlated positions found.',
                    $exposureData['exposure_pct'],
                    $maxCorrelationExposurePct,
                    $exposureData['correlation_count']
                ),
                'exposure_data' => $exposureData,
            ];
        }
        
        return [
            'should_prevent' => false,
            'reason' => null,
            'exposure_data' => $exposureData,
        ];
    }

    /**
     * Get correlation between two symbols
     * 
     * @param string $symbol1 First symbol
     * @param string $symbol2 Second symbol
     * @return float|null Correlation coefficient (-1 to 1), or null if not found
     */
    public function getCorrelation(string $symbol1, string $symbol2): ?float
    {
        $matrix = $this->getCorrelationMatrix();
        $symbol1 = strtoupper($symbol1);
        $symbol2 = strtoupper($symbol2);
        
        // Check direct correlation
        if (isset($matrix[$symbol1][$symbol2])) {
            return $matrix[$symbol1][$symbol2];
        }
        
        // Check reverse correlation
        if (isset($matrix[$symbol2][$symbol1])) {
            return $matrix[$symbol2][$symbol1];
        }
        
        return null;
    }
}

