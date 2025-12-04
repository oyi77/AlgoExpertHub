<?php

namespace Addons\TradingManagement\Shared\Contracts;

use App\Models\Signal;

/**
 * Interface for risk calculators (Preset-based, Smart Risk)
 * 
 * All risk calculators must implement this interface to provide
 * consistent position sizing and risk management.
 */
interface RiskCalculatorInterface
{
    /**
     * Calculate position size for a trade
     * 
     * @param Signal $signal The signal to calculate for
     * @param array $accountInfo Account information [balance, equity, etc.]
     * @param array $config Risk configuration
     * @return array ['lot_size' => float, 'risk_amount' => float, 'risk_percent' => float]
     */
    public function calculatePositionSize(Signal $signal, array $accountInfo, array $config): array;

    /**
     * Calculate stop loss price
     * 
     * @param Signal $signal The signal
     * @param float $lotSize Calculated lot size
     * @param array $config Risk configuration
     * @return float Stop loss price
     */
    public function calculateStopLoss(Signal $signal, float $lotSize, array $config): float;

    /**
     * Calculate take profit prices (supports multiple TPs)
     * 
     * @param Signal $signal The signal
     * @param float $lotSize Calculated lot size
     * @param array $config Risk configuration
     * @return array Array of TP prices [TP1, TP2, TP3, ...]
     */
    public function calculateTakeProfits(Signal $signal, float $lotSize, array $config): array;

    /**
     * Validate if trade meets risk criteria
     * 
     * @param Signal $signal The signal
     * @param array $accountInfo Account information
     * @param array $config Risk configuration
     * @return array ['valid' => bool, 'reason' => string|null]
     */
    public function validateTrade(Signal $signal, array $accountInfo, array $config): array;

    /**
     * Get calculator name (e.g., 'preset', 'smart_risk')
     * 
     * @return string Calculator identifier
     */
    public function getCalculatorName(): string;
}

