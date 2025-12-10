# Tasks: Multi-Timeframe Strategy Seeder (RULEBOOK EA)

## Overview
Create a comprehensive seeder for RULEBOOK EA strategy that implements multi-timeframe analysis (H4 primary, D1 S/R mapping, M5/M15/H1 confirmation), Fibonacci retracement, 3-bar candle validation, and support/resistance mapping.

## Cross-Check Results

### ✅ Current Capabilities
- ✅ Single timeframe evaluation (from signal)
- ✅ EMA calculation (fast/slow)
- ✅ Stochastic calculation (K, D)
- ✅ Parabolic SAR calculation
- ✅ Basic rule evaluation (AND/OR logic)
- ✅ Market data fetching (MarketDataService)

### ❌ Missing Capabilities (Need Extension)
1. **Multi-timeframe analysis**: Evaluator only uses signal timeframe
2. **Fibonacci retracement**: No implementation found
3. **3-bar candle validation**: No validation for n-1, n, n+1 candles
4. **S/R mapping from D1**: No support/resistance calculation from higher timeframe
5. **Cross detection**: No explicit cross detection for EMA and Stochastic

---

## Task Breakdown

### Phase 1: Analysis & Design (1 day)

#### Task 1.1: Document Extension Requirements
**Estimate**: 2 hours  
**Dependencies**: None  
**Status**: Pending

**Description**:
- Document all required extensions for FilterStrategyEvaluator
- Define config structure for multi-timeframe strategy
- Specify Fibonacci retracement calculation method
- Define 3-bar validation logic
- Specify S/R mapping algorithm

**Acceptance Criteria**:
- [ ] Extension requirements document created
- [ ] Config structure defined with examples
- [ ] All calculation methods specified
- [ ] Integration points identified

**Files to Create**:
- `specs/active/multi-timeframe-strategy/extension-requirements.md`

---

### Phase 2: Extend Evaluator (2-3 days)

#### Task 2.1: Add Multi-Timeframe Support to Evaluator
**Estimate**: 1 day  
**Dependencies**: Task 1.1  
**Status**: Pending

**Description**:
- Extend `FilterStrategyEvaluator` to support multiple timeframes
- Add config parsing for `timeframes` section (primary, sr_mapping, confirmation)
- Fetch market data for each required timeframe
- Calculate indicators per timeframe
- Evaluate rules per timeframe

**Acceptance Criteria**:
- [ ] Evaluator can parse multi-timeframe config
- [ ] Can fetch data for H4, D1, M15, H1 simultaneously
- [ ] Indicators calculated per timeframe
- [ ] Rules evaluated per timeframe
- [ ] All timeframes must pass for strategy to pass

**Files to Modify**:
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/FilterStrategyEvaluator.php`

**Implementation Notes**:
```php
// Config structure:
{
  "timeframes": {
    "primary": "H4",
    "sr_mapping": "D1",
    "confirmation": ["M15", "H1"]
  },
  "indicators": {
    "h4": { ... },
    "confirmation": { ... }
  },
  "rules": {
    "h4_trend": { ... },
    "confirmation": { ... }
  }
}
```

---

#### Task 2.2: Implement Fibonacci Retracement Calculation
**Estimate**: 4 hours  
**Dependencies**: Task 2.1  
**Status**: Pending

**Description**:
- Add `calculateFibonacciRetracement()` method to `IndicatorService`
- Calculate swing high/low from recent candles
- Calculate retracement levels (23.6%, 38.2%, 50%, 61.8%, custom 11%, 61%)
- Check if current price is within retracement zone
- Support both standard and custom levels

**Acceptance Criteria**:
- [ ] Fibonacci calculation method added to IndicatorService
- [ ] Can identify swing high/low (last 10-20 candles)
- [ ] Calculates all standard retracement levels
- [ ] Supports custom levels (11%, 61%)
- [ ] Returns price zones for entry validation

**Files to Modify**:
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/IndicatorService.php`

**Implementation Notes**:
```php
public function calculateFibonacciRetracement(
    array $candles,
    array $levels = [0.236, 0.382, 0.5, 0.618]
): array {
    // Find swing high/low
    // Calculate retracement levels
    // Return zones
}
```

---

#### Task 2.3: Implement 3-Bar Candle Validation
**Estimate**: 3 hours  
**Dependencies**: Task 2.1  
**Status**: Pending

**Description**:
- Add `validateCandleSignal()` method to evaluator
- Check if signal appears in candle n-1, n, or n+1
- Validate that signal is confirmed across 3 candles
- Support for cross detection (EMA cross, Stochastic cross)
- Support for PSAR break detection

**Acceptance Criteria**:
- [ ] 3-bar validation method implemented
- [ ] Can detect signals in n-1, n, n+1 candles
- [ ] Validates cross events across 3 candles
- [ ] Validates PSAR break across 3 candles
- [ ] Returns validation result with reason

**Files to Modify**:
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/FilterStrategyEvaluator.php`

**Implementation Notes**:
```php
protected function validateCandleSignal(
    array $candles,
    string $signalType, // 'ema_cross', 'stoch_cross', 'psar_break'
    array $indicators
): array {
    // Check last 3 candles (n-1, n, n+1)
    // Validate signal appears and is confirmed
    // Return ['valid' => bool, 'reason' => string]
}
```

---

#### Task 2.4: Implement Support/Resistance Mapping from D1
**Estimate**: 1 day  
**Dependencies**: Task 2.1  
**Status**: Pending

**Description**:
- Add `calculateSupportResistance()` method to `IndicatorService`
- Fetch D1 timeframe data (10-20 candles)
- Identify swing highs (resistance) and swing lows (support)
- Map rejection patterns
- Detect breakout and retest
- Return S/R levels with strength

**Acceptance Criteria**:
- [ ] S/R calculation method added
- [ ] Can fetch D1 data
- [ ] Identifies swing highs/lows
- [ ] Detects rejection patterns
- [ ] Returns S/R levels array
- [ ] Validates price action against S/R

**Files to Modify**:
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/IndicatorService.php`
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/FilterStrategyEvaluator.php`

**Implementation Notes**:
```php
public function calculateSupportResistance(
    array $candles,
    int $lookback = 20
): array {
    // Identify swing highs/lows
    // Calculate rejection patterns
    // Return ['support' => [...], 'resistance' => [...]]
}
```

---

#### Task 2.5: Add Cross Detection Operators
**Estimate**: 2 hours  
**Dependencies**: Task 2.1  
**Status**: Pending

**Description**:
- Extend `evaluateCondition()` to support cross operators
- Add `crosses_above` operator (EMA 10 crosses above EMA 100)
- Add `crosses_below` operator (EMA 10 crosses below EMA 100)
- Add `stoch_cross_up` operator (Stoch K crosses above D, from below 20)
- Add `stoch_cross_down` operator (Stoch K crosses below D, from above 80)

**Acceptance Criteria**:
- [ ] Cross detection operators added
- [ ] Can detect EMA cross up/down
- [ ] Can detect Stochastic cross with level context
- [ ] Works with 3-bar validation
- [ ] Returns clear reason for cross events

**Files to Modify**:
- `main/addons/trading-management-addon/Modules/FilterStrategy/Services/FilterStrategyEvaluator.php`

**Implementation Notes**:
```php
case 'crosses_above':
    // Check if left crosses above right in last 3 candles
    break;
case 'crosses_below':
    // Check if left crosses below right in last 3 candles
    break;
```

---

### Phase 3: Create Seeder (1 day)

#### Task 3.1: Create RULEBOOK EA Strategy Seeder
**Estimate**: 4 hours  
**Dependencies**: Task 2.1, 2.2, 2.3, 2.4, 2.5  
**Status**: Pending

**Description**:
- Create seeder class `RulebookEaStrategySeeder`
- Define complete config structure for RULEBOOK EA
- Include all H4 indicators (EMA 10/100, Stochastic 14/3/3, PSAR 0.02)
- Include confirmation timeframe indicators (M15, H1)
- Include Fibonacci retracement config (23.6-38.2, custom 11-61)
- Include 3-bar validation rule
- Include S/R mapping from D1
- Include all entry rules (BUY and SELL)

**Acceptance Criteria**:
- [ ] Seeder class created
- [ ] Complete config structure matches RULEBOOK EA rules
- [ ] All indicators configured correctly
- [ ] All rules defined (H4 trend, H4 Stoch, H4 PSAR, confirmation, Fibonacci, S/R)
- [ ] 3-bar validation enabled
- [ ] Strategy name: "RULEBOOK EA - Multi-Timeframe Strategy"
- [ ] Description includes all features

**Files to Create**:
- `main/database/seeders/RulebookEaStrategySeeder.php`

**Config Structure**:
```json
{
  "name": "RULEBOOK EA - Multi-Timeframe Strategy",
  "description": "Professional multi-timeframe strategy using H4 trend, D1 S/R mapping, M15/H1 confirmation, Fibonacci retracement, and 3-bar candle validation.",
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
        {"left": "h4.ema_fast", "operator": "crosses_above", "right": "h4.ema_slow", "direction": "BUY"},
        {"left": "h4.ema_fast", "operator": "crosses_below", "right": "h4.ema_slow", "direction": "SELL"}
      ]
    },
    "h4_stoch": {
      "logic": "AND",
      "conditions": [
        {"left": "h4.stoch_k", "operator": "stoch_cross_up", "right": "h4.stoch_d", "level": 80, "direction": "BUY"},
        {"left": "h4.stoch_k", "operator": "stoch_cross_down", "right": "h4.stoch_d", "level": 20, "direction": "SELL"}
      ]
    },
    "h4_psar": {
      "logic": "AND",
      "conditions": [
        {"left": "h4.psar", "operator": "below_price", "right": null, "direction": "BUY"},
        {"left": "h4.psar", "operator": "above_price", "right": null, "direction": "SELL"}
      ]
    },
    "confirmation": {
      "logic": "AND",
      "conditions": [
        {"left": "confirmation.ema_fast", "operator": ">", "right": "confirmation.ema_slow", "direction": "BUY"},
        {"left": "confirmation.stoch_k", "operator": "stoch_cross_up", "right": "confirmation.stoch_d", "level": 20, "direction": "BUY"},
        {"left": "confirmation.psar", "operator": "below_price", "right": null, "direction": "BUY"}
      ]
    },
    "fibonacci": {
      "enabled": true,
      "levels": [0.236, 0.382, 0.11, 0.61],
      "direction": "BUY" // or "SELL"
    },
    "sr_mapping": {
      "enabled": true,
      "timeframe": "D1",
      "lookback": 20,
      "validate_break": true
    },
    "candle_validation": {
      "enabled": true,
      "bars": 3
    }
  }
}
```

---

#### Task 3.2: Register Seeder in DatabaseSeeder
**Estimate**: 30 minutes  
**Dependencies**: Task 3.1  
**Status**: Pending

**Description**:
- Add `RulebookEaStrategySeeder` to `DatabaseSeeder`
- Ensure it runs after `FilterStrategySeeder` (if exists)
- Add conditional check for FilterStrategy model existence

**Acceptance Criteria**:
- [ ] Seeder registered in DatabaseSeeder
- [ ] Runs in correct order
- [ ] Handles missing model gracefully
- [ ] Can run standalone: `php artisan db:seed --class=RulebookEaStrategySeeder`

**Files to Modify**:
- `main/database/seeders/DatabaseSeeder.php`

---

### Phase 4: Testing & Documentation (1 day)

#### Task 4.1: Create Unit Tests for Extensions
**Estimate**: 4 hours  
**Dependencies**: Phase 2, Phase 3  
**Status**: Pending

**Description**:
- Create tests for multi-timeframe evaluation
- Test Fibonacci retracement calculation
- Test 3-bar validation
- Test S/R mapping
- Test cross detection operators

**Acceptance Criteria**:
- [ ] Unit tests created for all new methods
- [ ] Tests cover edge cases
- [ ] Tests use mock market data
- [ ] All tests pass

**Files to Create**:
- `main/tests/Unit/FilterStrategy/MultiTimeframeEvaluatorTest.php`
- `main/tests/Unit/FilterStrategy/FibonacciRetracementTest.php`
- `main/tests/Unit/FilterStrategy/CandleValidationTest.php`
- `main/tests/Unit/FilterStrategy/SupportResistanceTest.php`

---

#### Task 4.2: Create Extension Documentation
**Estimate**: 2 hours  
**Dependencies**: Phase 2  
**Status**: Pending

**Description**:
- Document all extensions made to FilterStrategyEvaluator
- Document new config structure
- Provide examples for multi-timeframe strategies
- Document Fibonacci retracement usage
- Document 3-bar validation
- Document S/R mapping

**Acceptance Criteria**:
- [ ] Extension documentation created
- [ ] Config examples provided
- [ ] Usage examples included
- [ ] Integration guide included

**Files to Create**:
- `main/specs/active/multi-timeframe-strategy/extension-requirements.md` (from Task 1.1)
- `main/docs/filter-strategy-multi-timeframe.md`

---

#### Task 4.3: Test Seeder Execution
**Estimate**: 1 hour  
**Dependencies**: Task 3.1, 3.2  
**Status**: Pending

**Description**:
- Run seeder: `php artisan db:seed --class=RulebookEaStrategySeeder`
- Verify strategy created in database
- Verify config structure is valid JSON
- Verify all required fields present
- Test strategy can be loaded by evaluator (if evaluator extended)

**Acceptance Criteria**:
- [ ] Seeder runs without errors
- [ ] Strategy record created in `filter_strategies` table
- [ ] Config is valid JSON
- [ ] All fields match expected structure
- [ ] Strategy can be retrieved from database

---

## Summary

### Total Estimate
- **Phase 1**: 2 hours
- **Phase 2**: 2-3 days (14-18 hours)
- **Phase 3**: 4.5 hours
- **Phase 4**: 7 hours
- **Total**: ~3-4 days (27-31 hours)

### Dependencies
```
Task 1.1 → Task 2.1 → Task 2.2, 2.3, 2.4, 2.5
Task 2.1, 2.2, 2.3, 2.4, 2.5 → Task 3.1 → Task 3.2
Phase 2, Phase 3 → Task 4.1, 4.2, 4.3
```

### Critical Path
1. Task 1.1 (Document requirements)
2. Task 2.1 (Multi-timeframe support) - **BLOCKER**
3. Task 2.2, 2.3, 2.4, 2.5 (Extensions) - Can be parallel
4. Task 3.1 (Seeder)
5. Task 3.2 (Register)
6. Task 4.1, 4.2, 4.3 (Testing & Docs)

### Notes
- **Option C Selected**: Create complete seeder + extension documentation
- All extensions must be implemented before seeder can work fully
- Seeder can be created with extended config structure even if evaluator not fully extended (for future use)
- Documentation should clearly mark which features require evaluator extension

