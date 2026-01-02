<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Illuminate\Support\Facades\Log;

/**
 * SlippageProtectionService
 * 
 * Tracks and validates execution slippage
 */
class SlippageProtectionService
{
    /**
     * Default maximum allowed slippage in pips
     */
    protected const DEFAULT_MAX_SLIPPAGE_PIPS = 5.0;

    /**
     * Calculate slippage in pips
     * 
     * @param float $expectedPrice Expected/trigger price
     * @param float $executedPrice Actual execution price from exchange
     * @param string $direction Trade direction ('buy', 'sell', 'long', 'short')
     * @param string $symbol Trading symbol
     * @return float Slippage in pips (positive = bad slippage, negative = good slippage)
     */
    public function calculateSlippage(float $expectedPrice, float $executedPrice, string $direction, string $symbol): float
    {
        if ($expectedPrice <= 0 || $executedPrice <= 0) {
            Log::warning('SlippageProtectionService: Invalid prices for slippage calculation', [
                'expected_price' => $expectedPrice,
                'executed_price' => $executedPrice,
            ]);
            return 0.0;
        }

        $symbolSpecService = app(SymbolSpecService::class);
        $pipSize = $symbolSpecService->getPipSize($symbol);
        
        // Calculate price difference
        $priceDiff = abs($executedPrice - $expectedPrice);
        $slippagePips = $priceDiff / $pipSize;

        // Determine if slippage is good or bad based on direction
        $direction = strtolower($direction);
        $isBuy = in_array($direction, ['buy', 'long']);

        if ($isBuy) {
            // For BUY: executed higher = positive slippage (bad), executed lower = negative slippage (good)
            $slippagePips = ($executedPrice > $expectedPrice) ? $slippagePips : -$slippagePips;
        } else {
            // For SELL: executed lower = positive slippage (bad), executed higher = negative slippage (good)
            $slippagePips = ($executedPrice < $expectedPrice) ? $slippagePips : -$slippagePips;
        }

        return round($slippagePips, 4);
    }

    /**
     * Validate if slippage is acceptable
     * 
     * @param float $slippagePips Slippage in pips
     * @param float|null $maxAllowedSlippage Maximum allowed slippage in pips
     * @return array ['acceptable' => bool, 'reason' => string|null]
     */
    public function validateSlippage(float $slippagePips, ?float $maxAllowedSlippage = null): array
    {
        $maxAllowedSlippage = $maxAllowedSlippage ?? self::DEFAULT_MAX_SLIPPAGE_PIPS;

        // Negative slippage is always acceptable (good slippage)
        if ($slippagePips < 0) {
            return [
                'acceptable' => true,
                'reason' => null,
            ];
        }

        // Check if slippage exceeds maximum
        if ($slippagePips > $maxAllowedSlippage) {
            return [
                'acceptable' => false,
                'reason' => "Slippage ({$slippagePips} pips) exceeds maximum allowed ({$maxAllowedSlippage} pips)",
            ];
        }

        return [
            'acceptable' => true,
            'reason' => null,
        ];
    }

    /**
     * Predict expected slippage based on market conditions
     * 
     * @param string $symbol Trading symbol
     * @param float $lotSize Lot size
     * @param float|null $volatility Volatility measure (optional)
     * @param float|null $spread Current spread in pips (optional)
     * @return float Predicted slippage in pips
     */
    public function predictSlippage(string $symbol, float $lotSize, ?float $volatility = null, ?float $spread = null): float
    {
        // Base slippage estimate: 0.5-2 pips depending on lot size
        $baseSlippage = 0.5;
        
        // Larger lots = more slippage
        if ($lotSize > 1.0) {
            $baseSlippage = 1.0 + (($lotSize - 1.0) * 0.5);
        }
        
        // Add volatility component if provided
        if ($volatility !== null && $volatility > 0) {
            // High volatility = more slippage
            $volatilityMultiplier = 1.0 + ($volatility / 100);
            $baseSlippage *= $volatilityMultiplier;
        }
        
        // Add spread component if provided
        if ($spread !== null && $spread > 0) {
            // Slippage is typically at least half the spread
            $baseSlippage = max($baseSlippage, $spread * 0.5);
        }
        
        return round($baseSlippage, 2);
    }

    /**
     * Adjust stop loss to account for slippage
     * 
     * @param float $slPrice Original stop loss price
     * @param float $slippagePips Predicted or actual slippage in pips
     * @param string $direction Trade direction
     * @param string $symbol Trading symbol
     * @return float Adjusted stop loss price
     */
    public function adjustStopLossForSlippage(float $slPrice, float $slippagePips, string $direction, string $symbol): float
    {
        $symbolSpecService = app(SymbolSpecService::class);
        $pipSize = $symbolSpecService->getPipSize($symbol);
        
        $direction = strtolower($direction);
        $isBuy = in_array($direction, ['buy', 'long']);
        
        // Adjust SL to account for slippage
        // For BUY: move SL further down (subtract slippage)
        // For SELL: move SL further up (add slippage)
        if ($isBuy) {
            return $slPrice - ($slippagePips * $pipSize);
        } else {
            return $slPrice + ($slippagePips * $pipSize);
        }
    }

    /**
     * Get maximum allowed slippage from config
     * 
     * @param array $config Configuration
     * @return float Maximum allowed slippage in pips
     */
    public function getMaxAllowedSlippage(array $config = []): float
    {
        return (float) ($config['max_slippage_pips'] ?? self::DEFAULT_MAX_SLIPPAGE_PIPS);
    }
}

