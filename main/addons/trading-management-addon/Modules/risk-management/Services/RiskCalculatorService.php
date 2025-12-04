<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Services;

use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\RiskManagement\Services\Calculators\PresetRiskCalculator;
use Addons\TradingManagement\Modules\RiskManagement\Services\Calculators\SmartRiskCalculator;
use Addons\TradingManagement\Shared\Contracts\RiskCalculatorInterface;
use App\Models\Signal;

/**
 * Unified Risk Calculator Service
 * 
 * KEY INNOVATION: Merges preset-based and smart risk into one service
 * 
 * Mode Selection:
 * - If preset.smart_risk_enabled = false → Use PresetRiskCalculator (manual)
 * - If preset.smart_risk_enabled = true → Use SmartRiskCalculator (AI adaptive)
 * 
 * This eliminates the need for 2 separate addons!
 */
class RiskCalculatorService
{
    protected PresetRiskCalculator $presetCalculator;
    protected SmartRiskCalculator $smartRiskCalculator;

    public function __construct(
        PresetRiskCalculator $presetCalculator,
        SmartRiskCalculator $smartRiskCalculator
    ) {
        $this->presetCalculator = $presetCalculator;
        $this->smartRiskCalculator = $smartRiskCalculator;
    }

    /**
     * Calculate position size for a signal using a preset
     * 
     * Automatically selects calculator based on preset configuration
     * 
     * @param Signal $signal
     * @param TradingPreset $preset
     * @param array $accountInfo [balance, equity, margin, etc.]
     * @return array ['lot_size' => float, 'risk_amount' => float, 'risk_percent' => float, 'calculator' => string]
     */
    public function calculateForSignal(Signal $signal, TradingPreset $preset, array $accountInfo): array
    {
        // Select calculator based on preset configuration
        $calculator = $this->selectCalculator($preset);

        $config = $this->buildConfig($preset);

        // Calculate position size
        $result = $calculator->calculatePositionSize($signal, $accountInfo, $config);

        // Add metadata
        $result['calculator'] = $calculator->getCalculatorName();
        $result['preset_id'] = $preset->id;
        $result['preset_name'] = $preset->name;

        return $result;
    }

    /**
     * Calculate stop loss for a signal
     * 
     * @param Signal $signal
     * @param TradingPreset $preset
     * @param float $lotSize
     * @return float Stop loss price
     */
    public function calculateStopLoss(Signal $signal, TradingPreset $preset, float $lotSize): float
    {
        $calculator = $this->selectCalculator($preset);
        $config = $this->buildConfig($preset);

        return $calculator->calculateStopLoss($signal, $lotSize, $config);
    }

    /**
     * Calculate take profit prices
     * 
     * @param Signal $signal
     * @param TradingPreset $preset
     * @param float $lotSize
     * @return array [TP1, TP2, TP3, ...]
     */
    public function calculateTakeProfits(Signal $signal, TradingPreset $preset, float $lotSize): array
    {
        $calculator = $this->selectCalculator($preset);
        $config = $this->buildConfig($preset);

        return $calculator->calculateTakeProfits($signal, $lotSize, $config);
    }

    /**
     * Validate if trade meets risk criteria
     * 
     * @param Signal $signal
     * @param TradingPreset $preset
     * @param array $accountInfo
     * @return array ['valid' => bool, 'reason' => string|null]
     */
    public function validateTrade(Signal $signal, TradingPreset $preset, array $accountInfo): array
    {
        $calculator = $this->selectCalculator($preset);
        $config = $this->buildConfig($preset);

        return $calculator->validateTrade($signal, $accountInfo, $config);
    }

    /**
     * Select appropriate calculator based on preset configuration
     * 
     * @param TradingPreset $preset
     * @return RiskCalculatorInterface
     */
    protected function selectCalculator(TradingPreset $preset): RiskCalculatorInterface
    {
        // NEW: If smart risk enabled, use smart risk calculator
        if ($preset->hasSmartRisk()) {
            return $this->smartRiskCalculator;
        }

        // Otherwise, use preset-based calculator
        return $this->presetCalculator;
    }

    /**
     * Build configuration array from preset
     * 
     * @param TradingPreset $preset
     * @return array
     */
    protected function buildConfig(TradingPreset $preset): array
    {
        return [
            // Position sizing
            'position_size_mode' => $preset->position_size_mode,
            'fixed_lot' => $preset->fixed_lot,
            'risk_per_trade_pct' => $preset->risk_per_trade_pct,
            'max_positions' => $preset->max_positions,
            
            // Stop loss
            'sl_mode' => $preset->sl_mode,
            'sl_pips' => $preset->sl_pips,
            'sl_r_multiple' => $preset->sl_r_multiple,
            
            // Take profit
            'tp_mode' => $preset->tp_mode,
            'tp1_enabled' => $preset->tp1_enabled,
            'tp1_rr' => $preset->tp1_rr,
            'tp1_close_pct' => $preset->tp1_close_pct,
            'tp2_enabled' => $preset->tp2_enabled,
            'tp2_rr' => $preset->tp2_rr,
            'tp2_close_pct' => $preset->tp2_close_pct,
            'tp3_enabled' => $preset->tp3_enabled,
            'tp3_rr' => $preset->tp3_rr,
            'tp3_close_pct' => $preset->tp3_close_pct,
            
            // Break-even
            'be_enabled' => $preset->be_enabled,
            'be_trigger_rr' => $preset->be_trigger_rr,
            'be_offset_pips' => $preset->be_offset_pips,
            
            // Trailing stop
            'ts_enabled' => $preset->ts_enabled,
            'ts_mode' => $preset->ts_mode,
            'ts_trigger_rr' => $preset->ts_trigger_rr,
            
            // Smart Risk (NEW)
            'smart_risk_enabled' => $preset->smart_risk_enabled,
            'smart_risk_min_score' => $preset->smart_risk_min_score,
            'smart_risk_slippage_buffer' => $preset->smart_risk_slippage_buffer,
            'smart_risk_dynamic_lot' => $preset->smart_risk_dynamic_lot,
            
            // Full preset for advanced features
            'preset' => $preset,
        ];
    }

    /**
     * Get calculator for testing (useful for debugging)
     * 
     * @param string $type 'preset' or 'smart_risk'
     * @return RiskCalculatorInterface
     */
    public function getCalculator(string $type): RiskCalculatorInterface
    {
        return match($type) {
            'smart_risk' => $this->smartRiskCalculator,
            default => $this->presetCalculator,
        };
    }
}

