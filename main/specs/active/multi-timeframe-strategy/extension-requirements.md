# Extension Requirements: Multi-Timeframe Strategy Support

## Overview
This document outlines all required extensions to the FilterStrategyEvaluator and IndicatorService to support RULEBOOK EA multi-timeframe strategy.

## Current Limitations

### 1. Single Timeframe Evaluation
- **Current**: Evaluator only uses signal's timeframe
- **Required**: Support multiple timeframes (H4 primary, D1 S/R, M15/H1 confirmation)
- **Impact**: Cannot analyze trend on H4 while checking S/R on D1

### 2. No Fibonacci Retracement
- **Current**: No Fibonacci calculation available
- **Required**: Calculate retracement levels (23.6%, 38.2%, 50%, 61.8%, custom 11%, 61%)
- **Impact**: Cannot validate entry at Fibonacci retracement zones

### 3. No 3-Bar Candle Validation
- **Current**: No validation for signal confirmation across multiple candles
- **Required**: Validate signals appear in n-1, n, n+1 candles
- **Impact**: Cannot ensure signal is confirmed before entry

### 4. No Support/Resistance Mapping
- **Current**: No S/R calculation from higher timeframes
- **Required**: Calculate S/R from D1 timeframe (swing highs/lows, rejection patterns)
- **Impact**: Cannot validate price action against major S/R levels

### 5. No Cross Detection
- **Current**: Only basic comparison operators (>, <, ==)
- **Required**: Cross detection operators (crosses_above, crosses_below, stoch_cross_up, stoch_cross_down)
- **Impact**: Cannot detect EMA cross or Stochastic cross events

---

## Extension Specifications

### 1. Multi-Timeframe Config Structure

```json
{
  "timeframes": {
    "primary": "H4",
    "sr_mapping": "D1",
    "confirmation": ["M15", "H1"]
  },
  "indicators": {
    "h4": {
      "ema_fast": {"period": 10},
      "ema_slow": {"period": 100},
      "stoch": {"k": 14, "d": 3, "smooth": 3},
      "psar": {"step": 0.02, "max": 0.2}
    },
    "confirmation": {
      "ema_fast": {"period": 10},
      "ema_slow": {"period": 30},
      "stoch": {"k": 14, "d": 3, "smooth": 3},
      "psar": {"step": 0.02, "max": 0.2}
    }
  },
  "rules": {
    "h4_trend": {
      "logic": "AND",
      "conditions": [
        {"left": "h4.ema_fast", "operator": "crosses_above", "right": "h4.ema_slow", "direction": "BUY"}
      ]
    }
  }
}
```

**Key Points**:
- `timeframes.primary`: Main trend timeframe (H4)
- `timeframes.sr_mapping`: S/R analysis timeframe (D1)
- `timeframes.confirmation`: Array of confirmation timeframes (M15, H1)
- Indicators grouped by timeframe prefix (e.g., `h4.ema_fast`)
- Rules reference indicators with timeframe prefix

---

### 2. Fibonacci Retracement Calculation

**Method Signature**:
```php
public function calculateFibonacciRetracement(
    array $candles,
    array $levels = [0.236, 0.382, 0.5, 0.618],
    int $lookback = 20
): array
```

**Algorithm**:
1. Identify swing high/low from last N candles (default 20)
2. Calculate range: `range = swing_high - swing_low`
3. For each level: `retracement_price = swing_high - (range * level)`
4. Return array of retracement zones

**Return Format**:
```php
[
    'swing_high' => 1.2500,
    'swing_low' => 1.2000,
    'range' => 0.0500,
    'levels' => [
        0.236 => 1.2382,
        0.382 => 1.2309,
        0.5 => 1.2250,
        0.618 => 1.2191,
        0.11 => 1.2445,  // Custom level
        0.61 => 1.2195   // Custom level
    ],
    'zones' => [
        ['level' => 0.236, 'price' => 1.2382, 'upper' => 1.2400, 'lower' => 1.2360],
        // ... more zones
    ]
]
```

**Usage in Rules**:
```json
{
  "fibonacci": {
    "enabled": true,
    "levels": [0.236, 0.382, 0.11, 0.61],
    "direction": "BUY",
    "tolerance": 0.001  // Price tolerance for zone matching
  }
}
```

---

### 3. 3-Bar Candle Validation

**Method Signature**:
```php
protected function validateCandleSignal(
    array $candles,
    string $signalType,  // 'ema_cross', 'stoch_cross', 'psar_break'
    array $indicators,
    int $bars = 3
): array
```

**Validation Logic**:
- Check last 3 candles (n-1, n, n+1 where n = current)
- For EMA cross: Check if cross occurred in any of the 3 candles
- For Stochastic cross: Check if cross occurred in any of the 3 candles
- For PSAR break: Check if price closed above/below PSAR in any of the 3 candles
- Signal must be confirmed (present in at least 2 of 3 candles)

**Return Format**:
```php
[
    'valid' => true,
    'reason' => 'EMA cross confirmed in candles n-1 and n',
    'candle_index' => count($candles) - 2,  // Index where signal first appeared
    'confirmed_in' => ['n-1', 'n']  // Which candles confirmed the signal
]
```

**Usage in Rules**:
```json
{
  "candle_validation": {
    "enabled": true,
    "bars": 3,
    "min_confirmations": 2  // At least 2 of 3 candles must confirm
  }
}
```

---

### 4. Support/Resistance Mapping

**Method Signature**:
```php
public function calculateSupportResistance(
    array $candles,
    int $lookback = 20,
    float $minStrength = 0.5
): array
```

**Algorithm**:
1. Identify swing highs (local maxima) and swing lows (local minima)
2. Calculate rejection patterns (wicks touching S/R levels)
3. Count touches to determine strength
4. Detect breakout and retest patterns

**Return Format**:
```php
[
    'support' => [
        [
            'price' => 1.2000,
            'strength' => 0.8,  // 0-1 scale
            'touches' => 3,
            'last_touch' => 1234567890,
            'type' => 'swing_low'  // or 'rejection'
        ],
        // ... more support levels
    ],
    'resistance' => [
        [
            'price' => 1.2500,
            'strength' => 0.9,
            'touches' => 4,
            'last_touch' => 1234567890,
            'type' => 'swing_high'
        ],
        // ... more resistance levels
    ],
    'breakouts' => [
        [
            'level' => 1.2500,
            'type' => 'resistance',
            'breakout_price' => 1.2510,
            'breakout_timestamp' => 1234567890,
            'retested' => false
        ]
    ]
]
```

**Usage in Rules**:
```json
{
  "sr_mapping": {
    "enabled": true,
    "timeframe": "D1",
    "lookback": 20,
    "min_strength": 0.5,
    "validate_break": true,
    "direction": "BUY"  // Check if price is above support for BUY
  }
}
```

---

### 5. Cross Detection Operators

**New Operators**:

1. **`crosses_above`**: Left indicator crosses above right indicator
   - Check last 3 candles
   - Previous: left < right
   - Current: left > right
   - Returns true if cross detected

2. **`crosses_below`**: Left indicator crosses below right indicator
   - Check last 3 candles
   - Previous: left > right
   - Current: left < right
   - Returns true if cross detected

3. **`stoch_cross_up`**: Stochastic K crosses above D (from oversold)
   - Check if K was below threshold (e.g., 20) before cross
   - K crosses above D
   - Returns true if conditions met

4. **`stoch_cross_down`**: Stochastic K crosses below D (from overbought)
   - Check if K was above threshold (e.g., 80) before cross
   - K crosses below D
   - Returns true if conditions met

**Usage in Rules**:
```json
{
  "conditions": [
    {
      "left": "h4.ema_fast",
      "operator": "crosses_above",
      "right": "h4.ema_slow",
      "direction": "BUY"
    },
    {
      "left": "h4.stoch_k",
      "operator": "stoch_cross_up",
      "right": "h4.stoch_d",
      "level": 80,
      "direction": "BUY"
    }
  ]
}
```

---

## Integration Points

### FilterStrategyEvaluator Changes

1. **`evaluate()` method**:
   - Check if config has `timeframes` section
   - If yes, use multi-timeframe evaluation
   - If no, use single timeframe (backward compatible)

2. **New method: `evaluateMultiTimeframe()`**:
   - Parse timeframes config
   - Fetch market data for each timeframe
   - Calculate indicators per timeframe
   - Evaluate rules per timeframe
   - Combine results (all must pass)

3. **New method: `validateCandleSignal()`**:
   - Implement 3-bar validation logic
   - Support multiple signal types

4. **Extended `evaluateCondition()`**:
   - Add cross detection operators
   - Support timeframe-prefixed indicators (e.g., `h4.ema_fast`)

### IndicatorService Changes

1. **New method: `calculateFibonacciRetracement()`**:
   - Calculate swing high/low
   - Calculate retracement levels
   - Return zones

2. **New method: `calculateSupportResistance()`**:
   - Identify swing points
   - Calculate rejection patterns
   - Return S/R levels with strength

---

## Backward Compatibility

- Single timeframe strategies (without `timeframes` config) must continue to work
- Existing operators (>, <, ==, etc.) must continue to work
- Existing indicator calculations must not change
- Config structure should be backward compatible

---

## Testing Requirements

1. **Unit Tests**:
   - Test multi-timeframe evaluation
   - Test Fibonacci calculation
   - Test 3-bar validation
   - Test S/R mapping
   - Test cross detection operators

2. **Integration Tests**:
   - Test complete RULEBOOK EA strategy evaluation
   - Test with real market data
   - Test edge cases (missing data, insufficient candles)

3. **Performance Tests**:
   - Multi-timeframe evaluation should not significantly slow down
   - Cache market data appropriately
   - Optimize indicator calculations

---

## Implementation Priority

1. **Phase 1**: Multi-timeframe support (BLOCKER)
2. **Phase 2**: Cross detection operators
3. **Phase 3**: 3-bar validation
4. **Phase 4**: Fibonacci retracement
5. **Phase 5**: S/R mapping

---

## Notes

- All extensions must maintain backward compatibility
- Config structure should be flexible for future extensions
- Error handling must be robust (missing data, invalid config, etc.)
- Logging should be comprehensive for debugging

