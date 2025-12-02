<?php

namespace Addons\TradingPresetAddon\App\DTOs;

/**
 * Data Transfer Object for Preset Configuration
 * Used to transfer preset data between layers
 */
class PresetConfigurationDTO
{
    public ?int $id = null;
    
    // Identity & Market
    public string $name;
    public ?string $description = null;
    public ?string $symbol = null;
    public ?string $timeframe = null;
    public bool $enabled = true;
    public ?array $tags = null;
    
    // Position & Risk
    public string $position_size_mode = 'RISK_PERCENT';
    public ?float $fixed_lot = null;
    public ?float $risk_per_trade_pct = null;
    public int $max_positions = 1;
    public int $max_positions_per_symbol = 1;
    
    // Dynamic Equity
    public string $equity_dynamic_mode = 'NONE';
    public ?float $equity_base = null;
    public ?float $equity_step_factor = null;
    public ?float $risk_min_pct = null;
    public ?float $risk_max_pct = null;
    
    // Stop Loss
    public string $sl_mode = 'PIPS';
    public ?int $sl_pips = null;
    public ?float $sl_r_multiple = null;
    
    // Take Profit
    public string $tp_mode = 'SINGLE';
    public bool $tp1_enabled = true;
    public ?float $tp1_rr = null;
    public ?float $tp1_close_pct = null;
    public bool $tp2_enabled = false;
    public ?float $tp2_rr = null;
    public ?float $tp2_close_pct = null;
    public bool $tp3_enabled = false;
    public ?float $tp3_rr = null;
    public ?float $tp3_close_pct = null;
    public bool $close_remaining_at_tp3 = false;
    
    // Break Even
    public bool $be_enabled = false;
    public ?float $be_trigger_rr = null;
    public ?int $be_offset_pips = null;
    
    // Trailing Stop
    public bool $ts_enabled = false;
    public string $ts_mode = 'STEP_PIPS';
    public ?float $ts_trigger_rr = null;
    public ?int $ts_step_pips = null;
    public ?int $ts_atr_period = null;
    public ?float $ts_atr_multiplier = null;
    public ?int $ts_update_interval_sec = null;
    
    // Layering / Grid
    public bool $layering_enabled = false;
    public int $max_layers_per_symbol = 3;
    public ?int $layer_distance_pips = null;
    public string $layer_martingale_mode = 'NONE';
    public ?float $layer_martingale_factor = null;
    public ?float $layer_max_total_risk_pct = null;
    
    // Hedging
    public bool $hedging_enabled = false;
    public ?float $hedge_trigger_drawdown_pct = null;
    public ?int $hedge_distance_pips = null;
    public ?float $hedge_lot_factor = null;
    
    // Exit Per Candle
    public bool $auto_close_on_candle_close = false;
    public ?string $auto_close_timeframe = null;
    public ?int $hold_max_candles = null;
    
    // Trading Schedule
    public ?string $trading_hours_start = null;
    public ?string $trading_hours_end = null;
    public string $trading_timezone = 'SERVER';
    public int $trading_days_mask = 127;
    public string $session_profile = 'CUSTOM';
    public bool $only_trade_in_session = false;
    
    // Weekly Target
    public bool $weekly_target_enabled = false;
    public ?float $weekly_target_profit_pct = null;
    public ?int $weekly_reset_day = null;
    public bool $auto_stop_on_weekly_target = false;
    
    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        
        return $dto;
    }
    
    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    
    /**
     * Create DTO from TradingPreset model
     */
    public static function fromModel(\Addons\TradingPresetAddon\App\Models\TradingPreset $preset): self
    {
        return self::fromArray($preset->toArray());
    }
}

