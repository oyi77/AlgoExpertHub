<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services\Calculators;

use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use App\Models\Signal;

/**
 * Preset Risk Calculator
 * 
 * Manual preset-based position sizing
 * Implements traditional risk management rules
 */
class PresetRiskCalculator implements RiskCalculatorInterface
{
    /**
     * Calculate position size
     */
    public function calculatePositionSize(Signal $signal, array $accountInfo, array $config): array
    {
        $mode = $config['position_size_mode'] ?? 'RISK_PERCENT';

        if ($mode === 'FIXED') {
            $lotSize = (float) ($config['fixed_lot'] ?? 0.01);
            
            return [
                'lot_size' => $lotSize,
                'risk_amount' => 0, // Unknown for fixed lot
                'risk_percent' => 0,
            ];
        }

        // RISK_PERCENT mode
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 10000);
        $riskPercent = (float) ($config['risk_per_trade_pct'] ?? 1.0);
        $riskAmount = $equity * ($riskPercent / 100);

        // Calculate lot size based on SL distance
        $slDistance = $this->calculateSLDistance($signal, $config);
        
        if ($slDistance <= 0) {
            // Fallback to fixed lot if SL distance invalid
            $lotSize = 0.01;
        } else {
            // Lot size = Risk Amount / (SL Distance in pips * Pip Value)
            $pipValue = $this->getPipValue($signal);
            $lotSize = $riskAmount / ($slDistance * $pipValue);
            
            // Ensure within reasonable bounds
            $lotSize = max(0.01, min($lotSize, 10.0));
        }

        return [
            'lot_size' => round($lotSize, 2),
            'risk_amount' => $riskAmount,
            'risk_percent' => $riskPercent,
        ];
    }

    /**
     * Calculate stop loss price
     */
    public function calculateStopLoss(Signal $signal, float $lotSize, array $config): float
    {
        $slMode = $config['sl_mode'] ?? 'PIPS';
        $entryPrice = (float) $signal->open_price;
        $direction = $signal->direction;

        if ($slMode === 'PIPS') {
            $slPips = (float) ($config['sl_pips'] ?? 50);
            $pipSize = $this->getPipSize($signal);
            
            if ($direction === 'buy' || $direction === 'long') {
                return $entryPrice - ($slPips * $pipSize);
            } else {
                return $entryPrice + ($slPips * $pipSize);
            }
        }

        // R_MULTIPLE or STRUCTURE mode - use signal's SL
        return (float) $signal->sl;
    }

    /**
     * Calculate take profit prices
     */
    public function calculateTakeProfits(Signal $signal, float $lotSize, array $config): array
    {
        $tpMode = $config['tp_mode'] ?? 'SINGLE';
        
        if ($tpMode === 'DISABLED') {
            return [];
        }

        $entryPrice = (float) $signal->open_price;
        $slPrice = $this->calculateStopLoss($signal, $lotSize, $config);
        $riskDistance = abs($entryPrice - $slPrice);
        $direction = $signal->direction;
        
        $takeProfits = [];

        // TP1
        if ($config['tp1_enabled'] ?? true) {
            $tp1RR = (float) ($config['tp1_rr'] ?? 2.0);
            $tp1Distance = $riskDistance * $tp1RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp1Distance
                : $entryPrice - $tp1Distance;
        }

        // TP2
        if ($tpMode === 'MULTI' && ($config['tp2_enabled'] ?? false)) {
            $tp2RR = (float) ($config['tp2_rr'] ?? 3.0);
            $tp2Distance = $riskDistance * $tp2RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp2Distance
                : $entryPrice - $tp2Distance;
        }

        // TP3
        if ($tpMode === 'MULTI' && ($config['tp3_enabled'] ?? false)) {
            $tp3RR = (float) ($config['tp3_rr'] ?? 5.0);
            $tp3Distance = $riskDistance * $tp3RR;
            
            $takeProfits[] = $direction === 'buy' || $direction === 'long'
                ? $entryPrice + $tp3Distance
                : $entryPrice - $tp3Distance;
        }

        return $takeProfits;
    }

    /**
     * Validate trade
     */
    public function validateTrade(Signal $signal, array $accountInfo, array $config): array
    {
        $equity = (float) ($accountInfo['equity'] ?? $accountInfo['balance'] ?? 0);

        if ($equity <= 0) {
            return ['valid' => false, 'reason' => 'Insufficient account balance'];
        }

        // Calculate position size
        $positionData = $this->calculatePositionSize($signal, $accountInfo, $config);

        if ($positionData['lot_size'] < 0.01) {
            return ['valid' => false, 'reason' => 'Calculated lot size too small (< 0.01)'];
        }

        if ($positionData['lot_size'] > 10.0) {
            return ['valid' => false, 'reason' => 'Calculated lot size too large (> 10.0)'];
        }

        return ['valid' => true, 'reason' => null];
    }

    public function getCalculatorName(): string
    {
        return 'preset';
    }

    /**
     * Calculate SL distance in pips
     */
    protected function calculateSLDistance(Signal $signal, array $config): float
    {
        $slMode = $config['sl_mode'] ?? 'PIPS';

        if ($slMode === 'PIPS') {
            return (float) ($config['sl_pips'] ?? 50);
        }

        // Calculate from signal's SL
        $entryPrice = (float) $signal->open_price;
        $slPrice = (float) $signal->sl;
        $slDistance = abs($entryPrice - $slPrice);
        $pipSize = $this->getPipSize($signal);

        return $slDistance / $pipSize;
    }

    /**
     * Get pip size for a symbol (0.0001 for most FX, 0.01 for JPY pairs)
     */
    protected function getPipSize(Signal $signal): float
    {
        $symbol = $signal->pair->name ?? '';
        
        // JPY pairs have different pip size
        if (str_contains($symbol, 'JPY')) {
            return 0.01;
        }

        // XAU (gold) has different pip size
        if (str_contains($symbol, 'XAU') || str_contains($symbol, 'GOLD')) {
            return 0.10;
        }

        // Most FX pairs
        return 0.0001;
    }

    /**
     * Get pip value (how much 1 pip is worth in account currency)
     */
    protected function getPipValue(Signal $signal): float
    {
        // Simplified: Assume $10 per pip for 1.0 lot
        // In production, calculate based on symbol and account currency
        return 10.0;
    }
}

