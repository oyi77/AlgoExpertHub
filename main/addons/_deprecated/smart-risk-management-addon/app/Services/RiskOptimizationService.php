<?php

namespace Addons\SmartRiskManagement\App\Services;

use Illuminate\Support\Facades\Log;

class RiskOptimizationService
{
    /**
     * Calculate optimal lot size based on SRM logic
     * 
     * Formula: Base Lot * Performance Score Multiplier * Slippage Factor
     * 
     * @param float $equity User equity
     * @param float $riskTolerance Risk tolerance percentage (e.g., 1.0 for 1%)
     * @param float $slDistance Stop loss distance in price units
     * @param float $predictedSlippage Predicted slippage in pips
     * @param float $performanceScore Performance score (0-100)
     * @param array $constraints Additional constraints ['min_lot' => float, 'max_lot' => float, 'max_position_size' => float]
     * @return float Optimal lot size
     */
    public function calculateOptimalLot(
        float $equity,
        float $riskTolerance,
        float $slDistance,
        float $predictedSlippage,
        float $performanceScore,
        array $constraints = []
    ): float {
        try {
            // Base lot calculation: (Equity * Risk%) / SL_Distance
            if ($slDistance <= 0) {
                Log::warning("RiskOptimizationService: Invalid SL distance", [
                    'sl_distance' => $slDistance,
                ]);
                $slDistance = 0.0001; // Prevent division by zero
            }
            
            $baseLot = ($equity * $riskTolerance / 100) / $slDistance;
            
            // Performance Score Multiplier (0.5x to 1.5x)
            // Score 0 = 0.5x, Score 100 = 1.5x
            $scoreMultiplier = 0.5 + ($performanceScore / 100) * 1.0;
            
            // Slippage Buffer Factor (reduce lot if high slippage)
            // Max 30% reduction if slippage >= 10 pips
            $slippageFactor = 1.0 - min($predictedSlippage / 10, 0.3);
            
            // Calculate adjusted lot
            $adjustedLot = $baseLot * $scoreMultiplier * $slippageFactor;
            
            // Apply constraints
            $minLot = $constraints['min_lot'] ?? 0.01;
            $maxLot = $constraints['max_lot'] ?? null;
            $maxPositionSize = $constraints['max_position_size'] ?? null;
            
            // Apply min lot
            $adjustedLot = max($minLot, $adjustedLot);
            
            // Apply max lot
            if ($maxLot !== null) {
                $adjustedLot = min($maxLot, $adjustedLot);
            }
            
            // Apply max position size (in currency units)
            if ($maxPositionSize !== null && isset($constraints['entry_price'])) {
                $positionValue = $adjustedLot * $constraints['entry_price'];
                if ($positionValue > $maxPositionSize) {
                    $adjustedLot = $maxPositionSize / $constraints['entry_price'];
                }
            }
            
            return round($adjustedLot, 4);
        } catch (\Exception $e) {
            Log::error("RiskOptimizationService: Failed to calculate optimal lot", [
                'error' => $e->getMessage(),
            ]);
            
            // Fallback: return base lot
            return $slDistance > 0 ? round(($equity * $riskTolerance / 100) / $slDistance, 4) : 0.01;
        }
    }

    /**
     * Get adjustment reason for transparency
     * 
     * @param float $baseLot Base lot size
     * @param float $adjustedLot Adjusted lot size
     * @param float $performanceScore Performance score
     * @param float $predictedSlippage Predicted slippage
     * @return array Adjustment reason
     */
    public function getAdjustmentReason(
        float $baseLot,
        float $adjustedLot,
        float $performanceScore,
        float $predictedSlippage
    ): array {
        $reasons = [];
        $adjustment = $adjustedLot - $baseLot;
        $adjustmentPercent = $baseLot > 0 ? ($adjustment / $baseLot) * 100 : 0;
        
        // Performance score adjustment
        if ($performanceScore < 60) {
            $reasons[] = [
                'type' => 'performance_score',
                'message' => "Performance score is {$performanceScore} (below threshold of 60)",
                'impact' => 'Reduced lot size',
            ];
        } elseif ($performanceScore > 80) {
            $reasons[] = [
                'type' => 'performance_score',
                'message' => "Performance score is {$performanceScore} (above threshold of 80)",
                'impact' => 'Increased lot size',
            ];
        }
        
        // Slippage adjustment
        if ($predictedSlippage > 5) {
            $reasons[] = [
                'type' => 'slippage',
                'message' => "Predicted slippage is {$predictedSlippage} pips (high)",
                'impact' => 'Reduced lot size to account for slippage',
            ];
        }
        
        // Overall adjustment summary
        $summary = [
            'base_lot' => $baseLot,
            'adjusted_lot' => $adjustedLot,
            'adjustment' => $adjustment,
            'adjustment_percent' => round($adjustmentPercent, 2),
            'reasons' => $reasons,
            'performance_score' => $performanceScore,
            'predicted_slippage' => $predictedSlippage,
        ];
        
        return $summary;
    }
}

