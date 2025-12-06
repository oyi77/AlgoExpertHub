<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Addons\TradingPresetAddon\App\Models\TradingPreset;
use Illuminate\Support\Arr;

class PresetApplicatorService
{
    /**
     * Apply preset to execution context
     * Returns merged configuration with preset as baseline and connection settings as override (for non-preset fields only)
     *
     * @param TradingPreset|null $preset
     * @param array $connectionSettings Legacy connection settings (for non-preset fields)
     * @return array Applied configuration
     */
    public function apply(?TradingPreset $preset, array $connectionSettings = []): array
    {
        if (!$preset) {
            // No preset, return connection settings as-is
            return $connectionSettings;
        }

        // Convert preset to array
        $presetConfig = $preset->toArray();

        // Remove meta fields that shouldn't be in execution config
        $metaFields = ['id', 'created_by_user_id', 'is_default_template', 'clonable', 'visibility', 'created_at', 'updated_at', 'deleted_at'];
        $presetConfig = Arr::except($presetConfig, $metaFields);

        // Merge: Preset as baseline, connection settings only for non-preset fields
        $applied = array_merge($presetConfig, $this->getNonPresetFields($connectionSettings));

        return $applied;
    }

    /**
     * Apply preset and return as DTO
     *
     * @param TradingPreset|null $preset
     * @param array $connectionSettings
     * @return PresetConfigurationDTO
     */
    public function applyAsDTO(?TradingPreset $preset, array $connectionSettings = []): PresetConfigurationDTO
    {
        $applied = $this->apply($preset, $connectionSettings);
        return PresetConfigurationDTO::fromArray($applied);
    }

    /**
     * Get position size based on preset
     *
     * @param PresetConfigurationDTO|array $config
     * @param float $equity Current equity
     * @param float $entryPrice Entry price
     * @param float|null $slPrice Stop loss price (for risk calculation)
     * @param ExecutionConnection|null $connection Connection for dynamic equity (optional)
     * @return float Position size (quantity)
     */
    public function calculatePositionSize($config, float $equity, float $entryPrice, ?float $slPrice = null, ?ExecutionConnection $connection = null): float
    {
        $configArray = $config instanceof PresetConfigurationDTO ? $config->toArray() : $config;

        // Apply dynamic equity if enabled and connection provided
        if ($connection && isset($configArray['equity_dynamic_mode']) && $configArray['equity_dynamic_mode'] !== 'NONE') {
            $advancedService = app(AdvancedTradingService::class);
            $configDTO = $config instanceof PresetConfigurationDTO ? $config : PresetConfigurationDTO::fromArray($configArray);
            $equity = $advancedService->calculateDynamicEquity($configDTO, $connection, $equity);
        }

        if ($configArray['position_size_mode'] === 'FIXED') {
            return $configArray['fixed_lot'] ?? 0.01;
        }

        // RISK_PERCENT mode
        $riskPct = $configArray['risk_per_trade_pct'] ?? 1.0;
        $riskAmount = ($equity * $riskPct) / 100;

        // If SL price is provided, calculate position size based on risk
        if ($slPrice && $slPrice > 0) {
            $riskPerUnit = abs($entryPrice - $slPrice);
            if ($riskPerUnit > 0) {
                return $riskAmount / $riskPerUnit;
            }
        }

        // Fallback: simple percentage of equity
        return ($equity * $riskPct / 100) / $entryPrice;
    }

    /**
     * Calculate SL price based on preset
     *
     * @param PresetConfigurationDTO|array $config
     * @param float $entryPrice Entry price
     * @param string $direction 'buy' or 'sell'
     * @param float|null $structureSlPrice Structure SL price (if sl_mode = STRUCTURE)
     * @return float|null SL price
     */
    public function calculateSlPrice($config, float $entryPrice, string $direction, ?float $structureSlPrice = null): ?float
    {
        $configArray = $config instanceof PresetConfigurationDTO ? $config->toArray() : $config;

        if ($configArray['sl_mode'] === 'STRUCTURE') {
            return $structureSlPrice;
        }

        if ($configArray['sl_mode'] === 'PIPS' && !empty($configArray['sl_pips'])) {
            $pips = $configArray['sl_pips'];
            // For simplicity, assuming 1 pip = 0.0001 for most pairs
            // In production, should use proper pip calculation based on symbol
            $pipValue = 0.0001;
            
            if ($direction === 'buy') {
                return $entryPrice - ($pips * $pipValue);
            } else {
                return $entryPrice + ($pips * $pipValue);
            }
        }

        // R_MULTIPLE mode would need entry price and risk amount
        // This is typically calculated during position sizing
        return null;
    }

    /**
     * Calculate TP prices based on preset
     *
     * @param PresetConfigurationDTO|array $config
     * @param float $entryPrice Entry price
     * @param float $slPrice Stop loss price
     * @param string $direction 'buy' or 'sell'
     * @return array Array with tp1_price, tp2_price, tp3_price (nullable)
     */
    public function calculateTpPrices($config, float $entryPrice, float $slPrice, string $direction): array
    {
        $configArray = $config instanceof PresetConfigurationDTO ? $config->toArray() : $config;

        $result = [
            'tp1_price' => null,
            'tp2_price' => null,
            'tp3_price' => null,
        ];

        if ($configArray['tp_mode'] === 'DISABLED') {
            return $result;
        }

        // Calculate risk amount (distance from entry to SL)
        $riskAmount = abs($entryPrice - $slPrice);

        // Calculate TP1
        if (!empty($configArray['tp1_enabled']) && !empty($configArray['tp1_rr'])) {
            $tp1Distance = $riskAmount * $configArray['tp1_rr'];
            if ($direction === 'buy') {
                $result['tp1_price'] = $entryPrice + $tp1Distance;
            } else {
                $result['tp1_price'] = $entryPrice - $tp1Distance;
            }
        }

        // Calculate TP2
        if ($configArray['tp_mode'] === 'MULTI' && !empty($configArray['tp2_enabled']) && !empty($configArray['tp2_rr'])) {
            $tp2Distance = $riskAmount * $configArray['tp2_rr'];
            if ($direction === 'buy') {
                $result['tp2_price'] = $entryPrice + $tp2Distance;
            } else {
                $result['tp2_price'] = $entryPrice - $tp2Distance;
            }
        }

        // Calculate TP3
        if ($configArray['tp_mode'] === 'MULTI' && !empty($configArray['tp3_enabled']) && !empty($configArray['tp3_rr'])) {
            $tp3Distance = $riskAmount * $configArray['tp3_rr'];
            if ($direction === 'buy') {
                $result['tp3_price'] = $entryPrice + $tp3Distance;
            } else {
                $result['tp3_price'] = $entryPrice - $tp3Distance;
            }
        }

        return $result;
    }

    /**
     * Get non-preset fields from connection settings
     * These are legacy fields that are not part of preset system
     *
     * @param array $connectionSettings
     * @return array
     */
    protected function getNonPresetFields(array $connectionSettings): array
    {
        // List of preset fields (should not be overridden)
        $presetFields = [
            'position_size_mode', 'fixed_lot', 'risk_per_trade_pct', 'max_positions', 'max_positions_per_symbol',
            'equity_dynamic_mode', 'equity_base', 'equity_step_factor', 'risk_min_pct', 'risk_max_pct',
            'sl_mode', 'sl_pips', 'sl_r_multiple',
            'tp_mode', 'tp1_enabled', 'tp1_rr', 'tp1_close_pct', 'tp2_enabled', 'tp2_rr', 'tp2_close_pct',
            'tp3_enabled', 'tp3_rr', 'tp3_close_pct', 'close_remaining_at_tp3',
            'be_enabled', 'be_trigger_rr', 'be_offset_pips',
            'ts_enabled', 'ts_mode', 'ts_trigger_rr', 'ts_step_pips', 'ts_atr_period', 'ts_atr_multiplier', 'ts_update_interval_sec',
            'layering_enabled', 'max_layers_per_symbol', 'layer_distance_pips', 'layer_martingale_mode', 'layer_martingale_factor', 'layer_max_total_risk_pct',
            'hedging_enabled', 'hedge_trigger_drawdown_pct', 'hedge_distance_pips', 'hedge_lot_factor',
            'auto_close_on_candle_close', 'auto_close_timeframe', 'hold_max_candles',
            'trading_hours_start', 'trading_hours_end', 'trading_timezone', 'trading_days_mask', 'session_profile', 'only_trade_in_session',
            'weekly_target_enabled', 'weekly_target_profit_pct', 'weekly_reset_day', 'auto_stop_on_weekly_target',
        ];

        // Return only fields that are NOT in preset fields
        return array_diff_key($connectionSettings, array_flip($presetFields));
    }
}

