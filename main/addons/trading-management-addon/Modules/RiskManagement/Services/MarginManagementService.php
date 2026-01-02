<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Illuminate\Support\Facades\Log;

/**
 * MarginManagementService
 * 
 * Handles leverage, margin requirements, margin calls, and liquidation protection
 */
class MarginManagementService
{
    /**
     * Calculate required margin for a trade
     * 
     * @param float $lotSize Lot size
     * @param float $entryPrice Entry price
     * @param int $leverage Leverage (e.g., 100 for 1:100)
     * @param string $symbol Trading symbol
     * @param float|null $contractSize Contract size (optional, will be calculated if not provided)
     * @return float Required margin in account currency
     */
    public function calculateRequiredMargin(
        float $lotSize, 
        float $entryPrice, 
        int $leverage, 
        string $symbol, 
        ?float $contractSize = null
    ): float {
        if ($leverage <= 0) {
            Log::warning('MarginManagementService: Invalid leverage', ['leverage' => $leverage]);
            $leverage = 100; // Default to 1:100
        }

        // Get contract size if not provided
        if ($contractSize === null) {
            $symbolSpecService = app(SymbolSpecService::class);
            $contractSize = $symbolSpecService->getContractSize($symbol);
        }

        // Calculate notional value (position value)
        $notionalValue = $lotSize * $contractSize * $entryPrice;

        // Required margin = Notional Value / Leverage
        $requiredMargin = $notionalValue / $leverage;

        return round($requiredMargin, 2);
    }

    /**
     * Check margin level
     * 
     * @param array $accountInfo Account information [equity, margin, free_margin, etc.]
     * @return array ['margin_level' => float, 'status' => string, 'is_healthy' => bool]
     */
    public function checkMarginLevel(array $accountInfo): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        $margin = (float) ($accountInfo['margin'] ?? 0);

        if ($margin <= 0) {
            return [
                'margin_level' => null,
                'status' => 'no_margin_used',
                'is_healthy' => true,
                'message' => 'No margin currently used',
            ];
        }

        // Margin Level = (Equity / Margin) * 100
        $marginLevel = ($equity / $margin) * 100;

        $status = 'healthy';
        $isHealthy = true;

        if ($marginLevel < 50) {
            $status = 'liquidation_risk';
            $isHealthy = false;
        } elseif ($marginLevel < 100) {
            $status = 'margin_call';
            $isHealthy = false;
        } elseif ($marginLevel < 150) {
            $status = 'warning';
            $isHealthy = true;
        }

        return [
            'margin_level' => round($marginLevel, 2),
            'status' => $status,
            'is_healthy' => $isHealthy,
            'equity' => $equity,
            'margin' => $margin,
            'free_margin' => (float) ($accountInfo['free_margin'] ?? ($equity - $margin)),
        ];
    }

    /**
     * Check if margin call should be triggered
     * 
     * @param array $accountInfo Account information
     * @param float $threshold Margin level threshold (default 100%)
     * @return bool True if margin call should be triggered
     */
    public function shouldTriggerMarginCall(array $accountInfo, float $threshold = 100.0): bool
    {
        $marginCheck = $this->checkMarginLevel($accountInfo);
        
        if ($marginCheck['margin_level'] === null) {
            return false; // No margin used, no margin call
        }

        return $marginCheck['margin_level'] < $threshold;
    }

    /**
     * Check if trade should be prevented due to insufficient margin
     * 
     * @param array $accountInfo Account information
     * @param float $requiredMargin Required margin for the trade
     * @param array $config Configuration [max_margin_usage_pct, margin_call_threshold]
     * @return array ['should_prevent' => bool, 'reason' => string|null]
     */
    public function shouldPreventTrade(array $accountInfo, float $requiredMargin, array $config = []): array
    {
        $freeMargin = (float) ($accountInfo['free_margin'] ?? 0);
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        $margin = (float) ($accountInfo['margin'] ?? 0);
        $maxMarginUsagePct = (float) ($config['max_margin_usage_pct'] ?? 80.0);

        // Check if sufficient free margin
        if ($freeMargin < $requiredMargin) {
            return [
                'should_prevent' => true,
                'reason' => "Insufficient free margin. Required: {$requiredMargin}, Available: {$freeMargin}",
            ];
        }

        // Check margin level after trade
        $newMargin = $margin + $requiredMargin;
        $newMarginLevel = $newMargin > 0 ? ($equity / $newMargin) * 100 : null;

        if ($newMarginLevel !== null && $newMarginLevel < 100) {
            return [
                'should_prevent' => true,
                'reason' => "Trade would trigger margin call. Projected margin level: {$newMarginLevel}%",
            ];
        }

        // Check maximum margin usage percentage
        $marginUsagePct = $equity > 0 ? (($newMargin / $equity) * 100) : 0;
        if ($marginUsagePct > $maxMarginUsagePct) {
            return [
                'should_prevent' => true,
                'reason' => "Trade would exceed maximum margin usage ({$maxMarginUsagePct}%). Projected usage: {$marginUsagePct}%",
            ];
        }

        return [
            'should_prevent' => false,
            'reason' => null,
        ];
    }

    /**
     * Calculate liquidation price for a position
     * 
     * @param array $position Position data [direction, entry_price, quantity, sl_price]
     * @param array $accountInfo Account information
     * @param int $leverage Leverage
     * @return float|null Liquidation price, or null if cannot be calculated
     */
    public function calculateLiquidationPrice(array $position, array $accountInfo, int $leverage): ?float
    {
        $direction = $position['direction'] ?? 'buy';
        $entryPrice = (float) ($position['entry_price'] ?? 0);
        $quantity = (float) ($position['quantity'] ?? 0);
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);
        $margin = (float) ($accountInfo['margin'] ?? 0);

        if ($entryPrice <= 0 || $quantity <= 0 || $leverage <= 0) {
            return null;
        }

        // Liquidation typically occurs when margin level drops below 50% (broker-dependent)
        // For simplicity, we'll calculate when equity = margin * 0.5
        $liquidationMarginLevel = 50.0; // 50% margin level

        // Calculate position value
        $positionValue = $quantity * $entryPrice;

        // Calculate required equity at liquidation
        $requiredEquityAtLiquidation = $margin * ($liquidationMarginLevel / 100);

        // Calculate P&L at liquidation
        $pnlAtLiquidation = $requiredEquityAtLiquidation - $equity;

        // Calculate price change needed
        $priceChange = $pnlAtLiquidation / $quantity;

        // Calculate liquidation price
        if ($direction === 'buy' || $direction === 'long') {
            $liquidationPrice = $entryPrice - $priceChange;
        } else {
            $liquidationPrice = $entryPrice + $priceChange;
        }

        // Ensure liquidation price is positive
        if ($liquidationPrice <= 0) {
            return null;
        }

        return round($liquidationPrice, 5);
    }

    /**
     * Get margin call threshold from config
     * 
     * @param array $config Configuration
     * @return float Margin call threshold percentage
     */
    public function getMarginCallThreshold(array $config = []): float
    {
        return (float) ($config['margin_call_threshold'] ?? 100.0);
    }

    /**
     * Get liquidation threshold from config
     * 
     * @param array $config Configuration
     * @return float Liquidation threshold percentage
     */
    public function getLiquidationThreshold(array $config = []): float
    {
        return (float) ($config['liquidation_threshold'] ?? 50.0);
    }
}

