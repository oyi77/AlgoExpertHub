<?php

namespace App\Services\Backtesting;

use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;

/**
 * BacktestSlippageModel
 * 
 * Models slippage in backtests for realistic results
 */
class BacktestSlippageModel
{
    /**
     * Calculate expected slippage for a trade
     * 
     * @param string $symbol Trading symbol
     * @param float $lotSize Lot size
     * @param float|null $volatility Volatility measure (optional)
     * @param float|null $spread Current spread in pips (optional)
     * @return float Expected slippage in pips
     */
    public function calculateSlippage(string $symbol, float $lotSize, ?float $volatility = null, ?float $spread = null): float
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
     * Apply slippage to entry/exit price
     * 
     * @param float $price Original price
     * @param string $direction Trade direction ('buy', 'sell', 'long', 'short')
     * @param float $slippagePips Slippage in pips
     * @param string $symbol Trading symbol
     * @return float Price with slippage applied
     */
    public function applySlippage(float $price, string $direction, float $slippagePips, string $symbol): float
    {
        if ($slippagePips <= 0) {
            return $price;
        }
        
        $symbolSpecService = app(SymbolSpecService::class);
        $pipSize = $symbolSpecService->getPipSize($symbol);
        
        $direction = strtolower($direction);
        $isBuy = in_array($direction, ['buy', 'long']);
        
        // For BUY: slippage increases price (bad slippage)
        // For SELL: slippage decreases price (bad slippage)
        if ($isBuy) {
            return $price + ($slippagePips * $pipSize);
        } else {
            return $price - ($slippagePips * $pipSize);
        }
    }

    /**
     * Calculate spread cost
     * 
     * @param string $symbol Trading symbol
     * @param float $lotSize Lot size
     * @param float|null $spreadPips Spread in pips (optional, will estimate if not provided)
     * @return float Spread cost in account currency
     */
    public function calculateSpreadCost(string $symbol, float $lotSize, ?float $spreadPips = null): float
    {
        // Default spread estimates by symbol type
        if ($spreadPips === null) {
            $spreadPips = $this->estimateSpread($symbol);
        }
        
        $symbolSpecService = app(SymbolSpecService::class);
        $pipValue = $symbolSpecService->getPipValue($symbol, $lotSize, 'USD', 1.0); // Use USD and price 1.0 as default
        
        // Spread cost = spread in pips * pip value * lot size
        return $spreadPips * $pipValue;
    }

    /**
     * Estimate spread for a symbol
     * 
     * @param string $symbol Trading symbol
     * @return float Estimated spread in pips
     */
    protected function estimateSpread(string $symbol): float
    {
        $symbol = strtoupper($symbol);
        
        // Major FX pairs: 1-3 pips
        if (str_contains($symbol, 'EURUSD') || str_contains($symbol, 'GBPUSD') || 
            str_contains($symbol, 'USDJPY') || str_contains($symbol, 'AUDUSD')) {
            return 1.5;
        }
        
        // Minor FX pairs: 2-5 pips
        if (str_contains($symbol, 'JPY') || str_contains($symbol, 'CHF')) {
            return 3.0;
        }
        
        // Gold/XAU: 20-50 pips
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) {
            return 30.0;
        }
        
        // Crypto: varies widely, estimate 0.1% of price
        if (str_contains($symbol, 'BTC') || str_contains($symbol, 'ETH') || 
            str_contains($symbol, 'USDT') || str_contains($symbol, 'USDC')) {
            return 5.0; // Approximate
        }
        
        // Default: 2 pips
        return 2.0;
    }
}

