# Advanced Trading Features

This document describes the advanced trading features implemented in the Trading Preset addon.

## Dynamic Equity

Dynamic equity allows position sizing to adjust based on account performance.

### Modes

1. **NONE**: Use base equity as-is (default)
2. **LINEAR**: Linear adjustment based on current equity vs base equity
   - Formula: `adjusted_equity = base_equity * (current_equity / base_equity)`
3. **STEP**: Step-based adjustment with multiplier
   - Formula: `adjusted_equity = base_equity * (step_factor ^ steps)`
   - Steps = floor(current_equity / base_equity)

### Configuration

- `equity_dynamic_mode`: Mode (NONE, LINEAR, STEP)
- `equity_base`: Base equity amount for calculation
- `equity_step_factor`: Multiplier for STEP mode
- `risk_min_pct`: Minimum risk percentage (optional)
- `risk_max_pct`: Maximum risk percentage (optional)

### Usage

```php
use Addons\TradingPresetAddon\App\Services\AdvancedTradingService;

$advancedService = app(AdvancedTradingService::class);
$adjustedEquity = $advancedService->calculateDynamicEquity(
    $config,
    $connection,
    $baseEquity
);
```

## ATR-Based Calculations

ATR (Average True Range) is a volatility indicator used for position sizing and stop loss calculations.

### ATR Calculation

```php
$atr = $advancedService->calculateATR($candles, $period);
```

- `$candles`: Array of candles with ['high', 'low', 'close'] keys
- `$period`: ATR period (default 14)

### ATR-Based Trailing Stop

Uses ATR to determine trailing stop distance:

```php
$newSl = $advancedService->calculateATRTrailingStop(
    $currentPrice,
    $atr,
    $atrMultiplier, // e.g., 2.0 for 2x ATR
    $direction,
    $currentSl
);
```

### Configuration

- `ts_mode`: Set to 'STEP_ATR'
- `ts_atr_period`: ATR period (default 14)
- `ts_atr_multiplier`: ATR multiplier (e.g., 2.0)

## Chandelier Stop Loss

Chandelier stop loss uses the highest high (for buy) or lowest low (for sell) over a lookback period, adjusted by ATR.

### Calculation

- **For Buy**: `Chandelier = Highest High - (ATR * Multiplier)`
- **For Sell**: `Chandelier = Lowest Low + (ATR * Multiplier)`

### Usage

```php
$chandelierSl = $advancedService->calculateChandelierStop(
    $candles,
    $lookbackPeriod, // e.g., 22
    $atrMultiplier, // e.g., 3.0
    $direction
);
```

### Configuration

- `ts_mode`: Set to 'CHANDELIER'
- `ts_atr_period`: Lookback period for highest/lowest (default 22)
- `ts_atr_multiplier`: ATR multiplier (e.g., 3.0)

## Candle-Based Exit Logic

Automatically close positions based on candle close events.

### Configuration

- `auto_close_on_candle_close`: Enable/disable (boolean)
- `auto_close_timeframe`: Timeframe to monitor (e.g., '1h', '4h', '1d')
- `hold_max_candles`: Maximum candles to hold position (optional)

### Usage

```php
$check = $advancedService->checkCandleCloseLogic(
    $config,
    $positionOpenedAt,
    $timeframe
);

if ($check['should_close']) {
    // Close position
}
```

## Price History

The service can fetch price history from connection adapters:

```php
$candles = $advancedService->getPriceHistory(
    $connection,
    $symbol,
    $timeframe,
    $limit // Number of candles
);
```

### Supported Timeframes

- Minutes: `1m`, `5m`, `15m`, `30m`
- Hours: `1h`, `4h`, `12h`
- Days: `1d`, `1w`

### Adapter Requirements

Adapters should implement `fetchOHLCV()` method:

```php
public function fetchOHLCV(string $symbol, string $timeframe, int $limit): array
{
    // Return array of candles:
    // [
    //   [timestamp, open, high, low, close, volume],
    //   ...
    // ]
}
```

## Integration with PresetPositionService

The `PresetPositionService` automatically uses these advanced features when processing positions:

1. **Trailing Stop**: Automatically calculates ATR or Chandelier stops when `ts_mode` is set
2. **Dynamic Equity**: Applied during position size calculation
3. **Candle Exit**: Checked during position monitoring

## Example: Complete Advanced Setup

```php
// Create preset with advanced features
$preset = TradingPreset::create([
    'name' => 'ATR-Based Scalper',
    'position_size_mode' => 'RISK_PERCENT',
    'risk_per_trade_pct' => 1.0,
    
    // Dynamic equity
    'equity_dynamic_mode' => 'LINEAR',
    'equity_base' => 10000,
    
    // ATR trailing stop
    'ts_enabled' => true,
    'ts_mode' => 'STEP_ATR',
    'ts_trigger_rr' => 1.5,
    'ts_atr_period' => 14,
    'ts_atr_multiplier' => 2.0,
    'ts_update_interval_sec' => 60,
    
    // Candle exit
    'auto_close_on_candle_close' => true,
    'auto_close_timeframe' => '1h',
    'hold_max_candles' => 24,
]);
```

## Notes

- ATR and Chandelier calculations require price history data
- If price history is unavailable, these features will gracefully fall back
- Dynamic equity requires connection adapter to provide balance/equity data
- All advanced features are optional and can be enabled/disabled per preset

