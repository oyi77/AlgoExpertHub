<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Support\Facades\Log;

/**
 * Portfolio Risk Service
 * 
 * Manages portfolio-level risk including:
 * - Portfolio heat (total risk across all positions)
 * - Sector/asset class exposure limits
 * - Correlation matrix calculation
 * - Portfolio diversification metrics
 */
class PortfolioRiskService
{
    protected CorrelationRiskService $correlationService;
    protected SymbolSpecService $symbolSpecService;

    public function __construct(
        CorrelationRiskService $correlationService,
        SymbolSpecService $symbolSpecService
    ) {
        $this->correlationService = $correlationService;
        $this->symbolSpecService = $symbolSpecService;
    }

    /**
     * Calculate portfolio heat (total risk across all positions)
     * 
     * Portfolio heat = sum of (position_value * risk_percent) for all open positions
     * 
     * @param array $positions Array of positions with risk data
     * @param float $equity Account equity
     * @return array ['portfolio_heat' => float, 'heat_percent' => float, 'breakdown' => array]
     */
    public function calculatePortfolioHeat(array $positions, float $equity): array
    {
        $totalRisk = 0;
        $breakdown = [];

        foreach ($positions as $position) {
            $positionValue = (float) ($position['position_value'] ?? 0);
            $riskPercent = (float) ($position['risk_percent'] ?? 0);
            $riskAmount = $positionValue * ($riskPercent / 100);
            
            $totalRisk += $riskAmount;
            
            $breakdown[] = [
                'symbol' => $position['symbol'] ?? '',
                'position_value' => $positionValue,
                'risk_percent' => $riskPercent,
                'risk_amount' => $riskAmount,
            ];
        }

        $heatPercent = $equity > 0 ? ($totalRisk / $equity) * 100 : 0;

        return [
            'portfolio_heat' => $totalRisk,
            'heat_percent' => round($heatPercent, 2),
            'breakdown' => $breakdown,
            'position_count' => count($positions),
        ];
    }

    /**
     * Calculate sector/asset class exposure
     * 
     * @param array $positions Array of positions
     * @param float $equity Account equity
     * @return array ['sectors' => array, 'total_exposure' => float]
     */
    public function calculateSectorExposure(array $positions, float $equity): array
    {
        $sectors = [];
        $totalExposure = 0;

        foreach ($positions as $position) {
            $symbol = strtoupper($position['symbol'] ?? '');
            $positionValue = (float) ($position['position_value'] ?? 0);
            
            $sector = $this->getAssetSector($symbol);
            $totalExposure += $positionValue;
            
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = [
                    'sector' => $sector,
                    'total_value' => 0,
                    'position_count' => 0,
                    'symbols' => [],
                ];
            }
            
            $sectors[$sector]['total_value'] += $positionValue;
            $sectors[$sector]['position_count']++;
            $sectors[$sector]['symbols'][] = $symbol;
        }

        // Calculate percentages
        foreach ($sectors as $key => $sector) {
            $sectors[$key]['exposure_percent'] = $equity > 0 ? ($sector['total_value'] / $equity) * 100 : 0;
        }

        return [
            'sectors' => $sectors,
            'total_exposure' => $totalExposure,
            'diversification_score' => $this->calculateDiversificationScore($sectors),
        ];
    }

    /**
     * Get asset sector/class for a symbol
     */
    protected function getAssetSector(string $symbol): string
    {
        $symbol = strtoupper($symbol);
        
        // Forex majors
        $majorPairs = ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'USDCAD', 'NZDUSD'];
        if (in_array($symbol, $majorPairs)) {
            return 'forex_major';
        }
        
        // Forex minors
        if (preg_match('/^[A-Z]{6}$/', $symbol) && !in_array($symbol, $majorPairs)) {
            return 'forex_minor';
        }
        
        // Commodities
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) {
            return 'commodity_gold';
        }
        if (str_contains($symbol, 'XAG') || str_contains($symbol, 'SILVER')) {
            return 'commodity_silver';
        }
        if (str_contains($symbol, 'OIL') || str_contains($symbol, 'CRUDE')) {
            return 'commodity_oil';
        }
        
        // Crypto
        if (str_contains($symbol, 'BTC') || str_contains($symbol, 'ETH') || 
            str_contains($symbol, 'USDT') || str_contains($symbol, 'USDC')) {
            return 'crypto';
        }
        
        // Stocks/Indices
        if (preg_match('/^[A-Z]{1,5}$/', $symbol) && strlen($symbol) <= 5) {
            return 'stock';
        }
        
        return 'other';
    }

    /**
     * Calculate diversification score (0-100)
     * Higher score = better diversification
     */
    protected function calculateDiversificationScore(array $sectors): float
    {
        if (empty($sectors)) {
            return 0;
        }
        
        $sectorCount = count($sectors);
        $totalValue = array_sum(array_column($sectors, 'total_value'));
        
        if ($totalValue <= 0) {
            return 0;
        }
        
        // Calculate Herfindahl-Hirschman Index (HHI) for concentration
        // Lower HHI = better diversification
        $hhi = 0;
        foreach ($sectors as $sector) {
            $share = $sector['total_value'] / $totalValue;
            $hhi += $share * $share;
        }
        
        // Convert HHI to diversification score (0-100)
        // Perfect diversification (equal weights) = 100 / sector_count
        // Maximum concentration (one sector) = 0
        $maxHHI = 1.0; // One sector has 100% = HHI of 1.0
        $minHHI = 1.0 / $sectorCount; // Equal weights = HHI of 1/n
        
        // Normalize: (maxHHI - hhi) / (maxHHI - minHHI) * 100
        $score = (($maxHHI - $hhi) / ($maxHHI - $minHHI)) * 100;
        
        return max(0, min(100, round($score, 2)));
    }

    /**
     * Check if portfolio risk limits are exceeded
     * 
     * @param array $positions Existing positions
     * @param array $newPosition New position to add
     * @param float $equity Account equity
     * @param array $limits Risk limits ['max_heat_percent' => float, 'max_sector_exposure_percent' => float, 'max_correlation_exposure_percent' => float]
     * @return array ['should_prevent' => bool, 'reasons' => array, 'metrics' => array]
     */
    public function checkPortfolioLimits(
        array $positions,
        array $newPosition,
        float $equity,
        array $limits = []
    ): array {
        $maxHeatPercent = (float) ($limits['max_heat_percent'] ?? 50.0);
        $maxSectorExposurePercent = (float) ($limits['max_sector_exposure_percent'] ?? 40.0);
        $maxCorrelationExposurePercent = (float) ($limits['max_correlation_exposure_percent'] ?? 50.0);
        
        $reasons = [];
        $metrics = [];
        
        // Add new position to positions array for calculations
        $allPositions = array_merge($positions, [$newPosition]);
        
        // Check portfolio heat
        $heatData = $this->calculatePortfolioHeat($allPositions, $equity);
        $metrics['portfolio_heat'] = $heatData;
        
        if ($heatData['heat_percent'] > $maxHeatPercent) {
            $reasons[] = sprintf(
                'Portfolio heat (%.2f%%) exceeds maximum (%.2f%%)',
                $heatData['heat_percent'],
                $maxHeatPercent
            );
        }
        
        // Check sector exposure
        $sectorData = $this->calculateSectorExposure($allPositions, $equity);
        $metrics['sector_exposure'] = $sectorData;
        
        $newSymbol = strtoupper($newPosition['symbol'] ?? '');
        $newSector = $this->getAssetSector($newSymbol);
        
        if (isset($sectorData['sectors'][$newSector])) {
            $sectorExposure = $sectorData['sectors'][$newSector]['exposure_percent'];
            if ($sectorExposure > $maxSectorExposurePercent) {
                $reasons[] = sprintf(
                    'Sector exposure (%s: %.2f%%) exceeds maximum (%.2f%%)',
                    $newSector,
                    $sectorExposure,
                    $maxSectorExposurePercent
                );
            }
        }
        
        // Check correlation exposure
        $newPositionValue = (float) ($newPosition['position_value'] ?? 0);
        $correlationCheck = $this->correlationService->shouldPreventTrade(
            $newSymbol,
            $positions,
            $newPositionValue,
            $equity,
            $maxCorrelationExposurePercent
        );
        $metrics['correlation_exposure'] = $correlationCheck['exposure_data'] ?? [];
        
        if ($correlationCheck['should_prevent']) {
            $reasons[] = $correlationCheck['reason'];
        }
        
        return [
            'should_prevent' => !empty($reasons),
            'reasons' => $reasons,
            'metrics' => $metrics,
        ];
    }

    /**
     * Calculate correlation matrix from historical price data
     * 
     * @param array $symbols Array of symbols to calculate correlations for
     * @param array $priceData Array of price data [symbol => [prices], ...]
     * @param int $period Number of periods to use
     * @return array Correlation matrix
     */
    public function calculateCorrelationMatrixFromData(array $symbols, array $priceData, int $period = 100): array
    {
        $matrix = [];
        
        foreach ($symbols as $symbol1) {
            $matrix[$symbol1] = [];
            $prices1 = $priceData[$symbol1] ?? [];
            
            if (empty($prices1) || count($prices1) < $period) {
                continue;
            }
            
            foreach ($symbols as $symbol2) {
                if ($symbol1 === $symbol2) {
                    $matrix[$symbol1][$symbol2] = 1.0; // Perfect correlation with itself
                    continue;
                }
                
                $prices2 = $priceData[$symbol2] ?? [];
                
                if (empty($prices2) || count($prices2) < $period) {
                    continue;
                }
                
                // Calculate correlation coefficient
                $correlation = $this->calculateCorrelationCoefficient($prices1, $prices2, $period);
                $matrix[$symbol1][$symbol2] = $correlation;
            }
        }
        
        return $matrix;
    }

    /**
     * Calculate Pearson correlation coefficient between two price series
     */
    protected function calculateCorrelationCoefficient(array $prices1, array $prices2, int $period): float
    {
        // Use last N prices
        $slice1 = array_slice($prices1, -$period);
        $slice2 = array_slice($prices2, -$period);
        
        if (count($slice1) !== count($slice2)) {
            return 0;
        }
        
        // Calculate returns
        $returns1 = [];
        $returns2 = [];
        
        for ($i = 1; $i < count($slice1); $i++) {
            if ($slice1[$i - 1] > 0 && $slice2[$i - 1] > 0) {
                $returns1[] = ($slice1[$i] - $slice1[$i - 1]) / $slice1[$i - 1];
                $returns2[] = ($slice2[$i] - $slice2[$i - 1]) / $slice2[$i - 1];
            }
        }
        
        if (count($returns1) < 2) {
            return 0;
        }
        
        // Calculate means
        $mean1 = array_sum($returns1) / count($returns1);
        $mean2 = array_sum($returns2) / count($returns2);
        
        // Calculate covariance and variances
        $covariance = 0;
        $variance1 = 0;
        $variance2 = 0;
        
        for ($i = 0; $i < count($returns1); $i++) {
            $diff1 = $returns1[$i] - $mean1;
            $diff2 = $returns2[$i] - $mean2;
            
            $covariance += $diff1 * $diff2;
            $variance1 += $diff1 * $diff1;
            $variance2 += $diff2 * $diff2;
        }
        
        $n = count($returns1);
        $covariance = $covariance / ($n - 1);
        $stdDev1 = sqrt($variance1 / ($n - 1));
        $stdDev2 = sqrt($variance2 / ($n - 1));
        
        // Calculate correlation coefficient
        if ($stdDev1 > 0 && $stdDev2 > 0) {
            return $covariance / ($stdDev1 * $stdDev2);
        }
        
        return 0;
    }

    /**
     * Get portfolio summary with all risk metrics
     * 
     * @param array $positions
     * @param float $equity
     * @return array
     */
    public function getPortfolioSummary(array $positions, float $equity): array
    {
        $heatData = $this->calculatePortfolioHeat($positions, $equity);
        $sectorData = $this->calculateSectorExposure($positions, $equity);
        
        return [
            'portfolio_heat' => $heatData,
            'sector_exposure' => $sectorData,
            'position_count' => count($positions),
            'total_exposure' => array_sum(array_column($positions, 'position_value')),
            'diversification_score' => $sectorData['diversification_score'],
        ];
    }
}

