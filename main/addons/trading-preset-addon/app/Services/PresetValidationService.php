<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PresetValidationService
{
    /**
     * Validate preset configuration
     *
     * @param array|PresetConfigurationDTO $data
     * @return array Validation result with 'valid' and 'errors' keys
     */
    public function validate($data): array
    {
        $dataArray = $data instanceof PresetConfigurationDTO ? $data->toArray() : $data;
        
        $validator = Validator::make($dataArray, $this->getValidationRules(), $this->getValidationMessages());
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }
        
        // Additional custom validation
        $customErrors = $this->validateCustomRules($dataArray);
        
        if (!empty($customErrors)) {
            return [
                'valid' => false,
                'errors' => $customErrors,
            ];
        }
        
        return [
            'valid' => true,
            'errors' => [],
        ];
    }
    
    /**
     * Validate and throw exception if invalid
     *
     * @param array|PresetConfigurationDTO $data
     * @throws ValidationException
     */
    public function validateOrFail($data): void
    {
        $result = $this->validate($data);
        
        if (!$result['valid']) {
            throw ValidationException::withMessages($result['errors']);
        }
    }
    
    /**
     * Get validation rules
     */
    protected function getValidationRules(): array
    {
        return [
            // Identity & Market
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'symbol' => 'nullable|string|max:50',
            'timeframe' => 'nullable|string|max:10',
            'enabled' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            
            // Position & Risk
            'position_size_mode' => 'required|in:FIXED,RISK_PERCENT',
            'fixed_lot' => 'nullable|numeric|min:0.01|max:1000',
            'risk_per_trade_pct' => 'nullable|numeric|min:0.01|max:100',
            'max_positions' => 'required|integer|min:1|max:100',
            'max_positions_per_symbol' => 'required|integer|min:1|max:50',
            
            // Dynamic Equity
            'equity_dynamic_mode' => 'required|in:NONE,LINEAR,STEP',
            'equity_base' => 'nullable|numeric|min:0',
            'equity_step_factor' => 'nullable|numeric|min:0.1|max:10',
            'risk_min_pct' => 'nullable|numeric|min:0.01|max:100',
            'risk_max_pct' => 'nullable|numeric|min:0.01|max:100',
            
            // Stop Loss
            'sl_mode' => 'required|in:PIPS,R_MULTIPLE,STRUCTURE',
            'sl_pips' => 'nullable|integer|min:1|max:10000',
            'sl_r_multiple' => 'nullable|numeric|min:0.1|max:10',
            
            // Take Profit
            'tp_mode' => 'required|in:DISABLED,SINGLE,MULTI',
            'tp1_enabled' => 'boolean',
            'tp1_rr' => 'nullable|numeric|min:0.1|max:100',
            'tp1_close_pct' => 'nullable|numeric|min:0|max:100',
            'tp2_enabled' => 'boolean',
            'tp2_rr' => 'nullable|numeric|min:0.1|max:100',
            'tp2_close_pct' => 'nullable|numeric|min:0|max:100',
            'tp3_enabled' => 'boolean',
            'tp3_rr' => 'nullable|numeric|min:0.1|max:100',
            'tp3_close_pct' => 'nullable|numeric|min:0|max:100',
            'close_remaining_at_tp3' => 'boolean',
            
            // Break Even
            'be_enabled' => 'boolean',
            'be_trigger_rr' => 'nullable|numeric|min:0.1|max:10',
            'be_offset_pips' => 'nullable|integer|min:-1000|max:1000',
            
            // Trailing Stop
            'ts_enabled' => 'boolean',
            'ts_mode' => 'required|in:STEP_PIPS,STEP_ATR,CHANDELIER',
            'ts_trigger_rr' => 'nullable|numeric|min:0.1|max:10',
            'ts_step_pips' => 'nullable|integer|min:1|max:1000',
            'ts_atr_period' => 'nullable|integer|min:1|max:200',
            'ts_atr_multiplier' => 'nullable|numeric|min:0.1|max:10',
            'ts_update_interval_sec' => 'nullable|integer|min:1|max:3600',
            
            // Layering / Grid
            'layering_enabled' => 'boolean',
            'max_layers_per_symbol' => 'required|integer|min:1|max:20',
            'layer_distance_pips' => 'nullable|integer|min:1|max:1000',
            'layer_martingale_mode' => 'required|in:NONE,MULTIPLY,ADD',
            'layer_martingale_factor' => 'nullable|numeric|min:0.1|max:10',
            'layer_max_total_risk_pct' => 'nullable|numeric|min:0.01|max:100',
            
            // Hedging
            'hedging_enabled' => 'boolean',
            'hedge_trigger_drawdown_pct' => 'nullable|numeric|min:0.01|max:100',
            'hedge_distance_pips' => 'nullable|integer|min:1|max:1000',
            'hedge_lot_factor' => 'nullable|numeric|min:0.1|max:10',
            
            // Exit Per Candle
            'auto_close_on_candle_close' => 'boolean',
            'auto_close_timeframe' => 'nullable|string|max:10',
            'hold_max_candles' => 'nullable|integer|min:1|max:10000',
            
            // Trading Schedule
            'trading_hours_start' => 'nullable|date_format:H:i',
            'trading_hours_end' => 'nullable|date_format:H:i',
            'trading_timezone' => 'nullable|string|max:50',
            'trading_days_mask' => 'required|integer|min:1|max:127',
            'session_profile' => 'required|in:ASIA,LONDON,NY,CUSTOM',
            'only_trade_in_session' => 'boolean',
            
            // Weekly Target
            'weekly_target_enabled' => 'boolean',
            'weekly_target_profit_pct' => 'nullable|numeric|min:0.01|max:1000',
            'weekly_reset_day' => 'nullable|integer|min:1|max:7',
            'auto_stop_on_weekly_target' => 'boolean',
        ];
    }
    
    /**
     * Get validation messages
     */
    protected function getValidationMessages(): array
    {
        return [
            'risk_per_trade_pct.max' => 'Risk per trade cannot exceed 100%. For higher risk, please review your strategy.',
            'risk_per_trade_pct.min' => 'Risk per trade must be at least 0.01%.',
            'risk_max_pct.gte' => 'Maximum risk must be greater than or equal to minimum risk.',
        ];
    }
    
    /**
     * Custom validation rules
     */
    protected function validateCustomRules(array $data): array
    {
        $errors = [];
        
        // Validate position size mode requirements
        if ($data['position_size_mode'] === 'FIXED' && empty($data['fixed_lot'])) {
            $errors['fixed_lot'] = ['Fixed lot size is required when position size mode is FIXED.'];
        }
        
        if ($data['position_size_mode'] === 'RISK_PERCENT' && empty($data['risk_per_trade_pct'])) {
            $errors['risk_per_trade_pct'] = ['Risk per trade percentage is required when position size mode is RISK_PERCENT.'];
        }
        
        // Validate SL mode requirements
        if ($data['sl_mode'] === 'PIPS' && empty($data['sl_pips'])) {
            $errors['sl_pips'] = ['SL pips is required when SL mode is PIPS.'];
        }
        
        if ($data['sl_mode'] === 'R_MULTIPLE' && empty($data['sl_r_multiple'])) {
            $errors['sl_r_multiple'] = ['SL R multiple is required when SL mode is R_MULTIPLE.'];
        }
        
        // Validate TP mode requirements
        if ($data['tp_mode'] === 'SINGLE' && !empty($data['tp1_enabled']) && empty($data['tp1_rr'])) {
            $errors['tp1_rr'] = ['TP1 R:R ratio is required when TP1 is enabled.'];
        }
        
        if ($data['tp_mode'] === 'MULTI') {
            if (!empty($data['tp1_enabled']) && empty($data['tp1_rr'])) {
                $errors['tp1_rr'] = ['TP1 R:R ratio is required when TP1 is enabled.'];
            }
            if (!empty($data['tp2_enabled']) && empty($data['tp2_rr'])) {
                $errors['tp2_rr'] = ['TP2 R:R ratio is required when TP2 is enabled.'];
            }
            if (!empty($data['tp3_enabled']) && empty($data['tp3_rr'])) {
                $errors['tp3_rr'] = ['TP3 R:R ratio is required when TP3 is enabled.'];
            }
            
            // Validate close percentages sum
            $totalClosePct = 0;
            if (!empty($data['tp1_enabled'])) $totalClosePct += ($data['tp1_close_pct'] ?? 0);
            if (!empty($data['tp2_enabled'])) $totalClosePct += ($data['tp2_close_pct'] ?? 0);
            if (!empty($data['tp3_enabled'])) $totalClosePct += ($data['tp3_close_pct'] ?? 0);
            
            if ($totalClosePct > 100) {
                $errors['tp_close_pct'] = ['Total close percentage cannot exceed 100%.'];
            }
        }
        
        // Validate dynamic equity
        if ($data['equity_dynamic_mode'] !== 'NONE') {
            if (empty($data['equity_base'])) {
                $errors['equity_base'] = ['Equity base is required when dynamic equity mode is enabled.'];
            }
            if (!empty($data['risk_min_pct']) && !empty($data['risk_max_pct']) && $data['risk_max_pct'] < $data['risk_min_pct']) {
                $errors['risk_max_pct'] = ['Maximum risk must be greater than or equal to minimum risk.'];
            }
        }
        
        // Validate trailing stop
        if (!empty($data['ts_enabled'])) {
            if (empty($data['ts_trigger_rr'])) {
                $errors['ts_trigger_rr'] = ['Trailing stop trigger R:R is required when trailing stop is enabled.'];
            }
            if ($data['ts_mode'] === 'STEP_PIPS' && empty($data['ts_step_pips'])) {
                $errors['ts_step_pips'] = ['Trailing stop step pips is required for STEP_PIPS mode.'];
            }
            if ($data['ts_mode'] === 'STEP_ATR') {
                if (empty($data['ts_atr_period'])) {
                    $errors['ts_atr_period'] = ['ATR period is required for STEP_ATR mode.'];
                }
                if (empty($data['ts_atr_multiplier'])) {
                    $errors['ts_atr_multiplier'] = ['ATR multiplier is required for STEP_ATR mode.'];
                }
            }
        }
        
        // Validate layering
        if (!empty($data['layering_enabled'])) {
            if (empty($data['layer_distance_pips'])) {
                $errors['layer_distance_pips'] = ['Layer distance pips is required when layering is enabled.'];
            }
            if ($data['layer_martingale_mode'] !== 'NONE' && empty($data['layer_martingale_factor'])) {
                $errors['layer_martingale_factor'] = ['Martingale factor is required when martingale mode is not NONE.'];
            }
        }
        
        // Validate hedging
        if (!empty($data['hedging_enabled'])) {
            if (empty($data['hedge_trigger_drawdown_pct'])) {
                $errors['hedge_trigger_drawdown_pct'] = ['Hedge trigger drawdown percentage is required when hedging is enabled.'];
            }
            if (empty($data['hedge_distance_pips'])) {
                $errors['hedge_distance_pips'] = ['Hedge distance pips is required when hedging is enabled.'];
            }
            if (empty($data['hedge_lot_factor'])) {
                $errors['hedge_lot_factor'] = ['Hedge lot factor is required when hedging is enabled.'];
            }
        }
        
        // Validate weekly target
        if (!empty($data['weekly_target_enabled'])) {
            if (empty($data['weekly_target_profit_pct'])) {
                $errors['weekly_target_profit_pct'] = ['Weekly target profit percentage is required when weekly target is enabled.'];
            }
            if (empty($data['weekly_reset_day'])) {
                $errors['weekly_reset_day'] = ['Weekly reset day is required when weekly target is enabled.'];
            }
        }
        
        // Validate trading hours
        if (!empty($data['trading_hours_start']) && !empty($data['trading_hours_end'])) {
            $start = strtotime($data['trading_hours_start']);
            $end = strtotime($data['trading_hours_end']);
            if ($end <= $start) {
                $errors['trading_hours_end'] = ['Trading hours end must be after trading hours start.'];
            }
        }
        
        return $errors;
    }
    
    /**
     * Get validation warnings (non-blocking)
     */
    public function getWarnings(array $data): array
    {
        $warnings = [];
        
        // High risk warning
        if (!empty($data['risk_per_trade_pct']) && $data['risk_per_trade_pct'] > 10) {
            $warnings[] = 'Risk per trade exceeds 10%. This is considered high risk.';
        }
        
        // Multiple positions with high risk
        if (!empty($data['max_positions']) && $data['max_positions'] > 5 && !empty($data['risk_per_trade_pct']) && $data['risk_per_trade_pct'] > 5) {
            $warnings[] = 'High number of positions combined with high risk per trade may lead to significant drawdown.';
        }
        
        // Layering with high risk
        if (!empty($data['layering_enabled']) && !empty($data['risk_per_trade_pct']) && $data['risk_per_trade_pct'] > 3) {
            $warnings[] = 'Layering enabled with high risk per trade. Total risk may exceed expectations.';
        }
        
        return $warnings;
    }
}

