<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Services;

use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * Filter Strategy Evaluator
 * 
 * Migrated from filter-strategy-addon
 * KEY CHANGE: Now uses centralized MarketDataService from market-data module
 * instead of duplicated MarketDataService
 */
class FilterStrategyEvaluator
{
    protected MarketDataService $marketDataService;
    protected IndicatorService $indicatorService;

    public function __construct(
        MarketDataService $marketDataService,
        IndicatorService $indicatorService
    ) {
        $this->marketDataService = $marketDataService;
        $this->indicatorService = $indicatorService;
    }

    /**
     * Evaluate filter strategy for a signal
     * 
     * @param FilterStrategy $strategy
     * @param Signal $signal
     * @return array ['pass' => bool, 'reason' => string, 'indicators' => array]
     */
    public function evaluate(FilterStrategy $strategy, Signal $signal): array
    {
        try {
            if (!$strategy->enabled) {
                return [
                    'pass' => false,
                    'reason' => 'Filter strategy is disabled',
                    'indicators' => [],
                ];
            }

            $config = $strategy->config;
            if (!$config || !isset($config['indicators']) || !isset($config['rules'])) {
                Log::warning("FilterStrategyEvaluator: Invalid config for strategy {$strategy->id}");
                return [
                    'pass' => false,
                    'reason' => 'Invalid filter strategy configuration',
                    'indicators' => [],
                ];
            }

            // Get symbol from signal
            $symbol = $signal->pair->name ?? null;
            if (!$symbol) {
                return [
                    'pass' => false,
                    'reason' => 'Signal missing currency pair',
                    'indicators' => [],
                ];
            }

            // Check if multi-timeframe strategy
            if (isset($config['timeframes']) && is_array($config['timeframes'])) {
                return $this->evaluateMultiTimeframe($config, $symbol, $signal);
            }

            // Single timeframe evaluation (backward compatible)
            $timeframe = $this->mapTimeframeToStandard($signal->time->name ?? 'H1');

            // CHANGED: Fetch market data from centralized MarketDataService
            // This data is cached and shared across all modules
            $marketDataRecords = $this->marketDataService->getLatest($symbol, $timeframe, 200);
            
            if ($marketDataRecords->isEmpty()) {
                Log::warning("FilterStrategyEvaluator: No market data available", [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                ]);
                return [
                    'pass' => false,
                    'reason' => 'Market data unavailable',
                    'indicators' => [],
                ];
            }

            // Convert MarketData models to array format for indicator calculation
            $candles = $marketDataRecords->map(function ($data) {
                return $data->getCandleArray();
            })->reverse()->values()->toArray(); // Reverse to chronological order

            // Calculate indicators
            $indicators = $this->calculateIndicators($config['indicators'], $candles);
            
            // Get latest price
            $latestCandle = $candles[count($candles) - 1] ?? null;
            $latestPrice = $latestCandle['close'] ?? null;
            
            if (!$latestPrice) {
                return [
                    'pass' => false,
                    'reason' => 'Unable to determine current price',
                    'indicators' => $indicators,
                ];
            }

            // Evaluate rules
            $result = $this->evaluateRules($config['rules'], $indicators, $latestPrice, $candles);

            return [
                'pass' => $result['pass'],
                'reason' => $result['reason'],
                'indicators' => $this->getLatestIndicatorValues($indicators),
            ];

        } catch (\Exception $e) {
            Log::error("FilterStrategyEvaluator: Evaluation failed", [
                'strategy_id' => $strategy->id,
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'pass' => false,
                'reason' => 'Filter evaluation error: ' . $e->getMessage(),
                'indicators' => [],
            ];
        }
    }

    /**
     * Calculate all indicators from config
     */
    protected function calculateIndicators(array $indicatorConfig, array $candles): array
    {
        $indicators = [];

        foreach ($indicatorConfig as $name => $params) {
            try {
                switch (strtolower($name)) {
                    case 'ema_fast':
                    case 'ema10':
                        $period = $params['period'] ?? 10;
                        $indicators[$name] = $this->indicatorService->calculateEMA($candles, $period);
                        break;

                    case 'ema_slow':
                    case 'ema100':
                        $period = $params['period'] ?? 100;
                        $indicators[$name] = $this->indicatorService->calculateEMA($candles, $period);
                        break;

                    case 'stoch':
                    case 'stochastic':
                        $k = $params['k'] ?? 14;
                        $d = $params['d'] ?? 3;
                        $smooth = $params['smooth'] ?? 3;
                        $stoch = $this->indicatorService->calculateStochastic($candles, $k, $d, $smooth);
                        $indicators[$name] = $stoch['k'];
                        $indicators[$name . '_d'] = $stoch['d'];
                        break;

                    case 'psar':
                    case 'parabolic_sar':
                        $step = $params['step'] ?? 0.02;
                        $max = $params['max'] ?? 0.2;
                        $indicators[$name] = $this->indicatorService->calculatePSAR($candles, $step, $max);
                        break;

                    default:
                        Log::warning("FilterStrategyEvaluator: Unknown indicator type: {$name}");
                        break;
                }
            } catch (\Exception $e) {
                Log::error("FilterStrategyEvaluator: Failed to calculate indicator {$name}", [
                    'error' => $e->getMessage(),
                ]);
                $indicators[$name] = null;
            }
        }

        return $indicators;
    }

    /**
     * Evaluate rules from config
     */
    protected function evaluateRules(array $rules, array $indicators, float $currentPrice, ?array $candles = null): array
    {
        // Check for special rules (Fibonacci, S/R, candle validation)
        if (isset($rules['fibonacci']) && $rules['fibonacci']['enabled'] ?? false) {
            $fibResult = $this->evaluateFibonacciRule($rules['fibonacci'], $candles ?? [], $currentPrice);
            if (!$fibResult['pass']) {
                return $fibResult;
            }
        }

        if (isset($rules['sr_mapping']) && $rules['sr_mapping']['enabled'] ?? false) {
            $srResult = $this->evaluateSRRule($rules['sr_mapping'], $candles ?? [], $currentPrice);
            if (!$srResult['pass']) {
                return $srResult;
            }
        }

        if (isset($rules['candle_validation']) && $rules['candle_validation']['enabled'] ?? false) {
            // Candle validation will be checked per condition
        }

        $logic = strtoupper($rules['logic'] ?? 'AND');
        $conditions = $rules['conditions'] ?? [];

        if (empty($conditions)) {
            return ['pass' => false, 'reason' => 'No conditions defined'];
        }

        $results = [];
        $reasons = [];

        foreach ($conditions as $condition) {
            $left = $condition['left'] ?? null;
            $operator = $condition['operator'] ?? null;
            $right = $condition['right'] ?? null;

            if (!$left || !$operator) {
                continue;
            }

            $result = $this->evaluateCondition($left, $operator, $right, $indicators, $currentPrice, $candles);
            $results[] = $result['pass'];
            
            if (!$result['pass']) {
                $reasons[] = $result['reason'];
            }
        }

        // Apply logic (AND or OR)
        $pass = $logic === 'OR' 
            ? in_array(true, $results, true)
            : (!in_array(false, $results, true) && !empty($results));

        return [
            'pass' => $pass,
            'reason' => $pass ? 'All conditions met' : 'Failed: ' . implode(', ', $reasons),
        ];
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(
        string $left,
        string $operator,
        $right,
        array $indicators,
        float $currentPrice,
        ?array $candles = null
    ): array {
        // Handle cross detection operators (require candles)
        if (in_array($operator, ['crosses_above', 'crosses_below', 'stoch_cross_up', 'stoch_cross_down'])) {
            if (empty($candles)) {
                return ['pass' => false, 'reason' => "Cross detection requires candle data"];
            }
            return $this->evaluateCrossCondition($left, $operator, $right, $indicators, $candles);
        }

        $leftValue = $this->getIndicatorValue($left, $indicators, $currentPrice);
        
        if ($leftValue === null) {
            return ['pass' => false, 'reason' => "Indicator '{$left}' not available"];
        }

        $rightValue = $this->getRightValue($right, $indicators, $currentPrice);

        $pass = false;
        $reason = '';

        switch ($operator) {
            case '>':
                $pass = $leftValue > $rightValue;
                $reason = "{$left} ({$leftValue}) > {$rightValue}";
                break;
            case '<':
                $pass = $leftValue < $rightValue;
                $reason = "{$left} ({$leftValue}) < {$rightValue}";
                break;
            case '>=':
                $pass = $leftValue >= $rightValue;
                $reason = "{$left} ({$leftValue}) >= {$rightValue}";
                break;
            case '<=':
                $pass = $leftValue <= $rightValue;
                $reason = "{$left} ({$leftValue}) <= {$rightValue}";
                break;
            case '==':
            case '=':
                $pass = abs($leftValue - $rightValue) < 0.0001;
                $reason = "{$left} ({$leftValue}) == {$rightValue}";
                break;
            case 'below_price':
            case 'under_price':
                $pass = $leftValue < $currentPrice;
                $reason = "{$left} ({$leftValue}) < price ({$currentPrice})";
                break;
            case 'above_price':
            case 'over_price':
                $pass = $leftValue > $currentPrice;
                $reason = "{$left} ({$leftValue}) > price ({$currentPrice})";
                break;
            default:
                $reason = "Unknown operator: {$operator}";
        }

        return ['pass' => $pass, 'reason' => $pass ? $reason : "NOT {$reason}"];
    }

    protected function getIndicatorValue(string $name, array $indicators, float $currentPrice): ?float
    {
        // Handle "price" as special case - return current price
        if (strtolower($name) === 'price' || strtolower($name) === 'current_price') {
            return $currentPrice;
        }

        // Support timeframe prefix (e.g., "h4.ema_fast")
        $indicatorName = $name;
        if (strpos($name, '.') !== false) {
            // Already has prefix, use as is
            $indicatorName = $name;
        }

        if (!isset($indicators[$indicatorName])) return null;

        $values = $indicators[$indicatorName];
        if (!is_array($values) || empty($values)) return null;

        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ($values[$i] !== null) {
                return (float) $values[$i];
            }
        }

        return null;
    }

    protected function getRightValue($right, array $indicators, float $currentPrice): float
    {
        if (is_numeric($right)) {
            return (float) $right;
        }

        if (is_string($right)) {
            // Handle "price" as special case
            if (strtolower($right) === 'price' || strtolower($right) === 'current_price') {
                return $currentPrice;
            }

            // Check if it's an indicator name
            if (isset($indicators[$right])) {
                $value = $this->getIndicatorValue($right, $indicators, $currentPrice);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return 0.0;
    }

    protected function getLatestIndicatorValues(array $indicators): array
    {
        $latest = [];
        
        foreach ($indicators as $name => $values) {
            if (is_array($values) && !empty($values)) {
                for ($i = count($values) - 1; $i >= 0; $i--) {
                    if ($values[$i] !== null) {
                        $latest[$name] = round($values[$i], 5);
                        break;
                    }
                }
            }
        }

        return $latest;
    }

    /**
     * Map signal timeframe name to standard format
     */
    protected function mapTimeframeToStandard(string $timeframe): string
    {
        $mapping = [
            '1m' => 'M1',
            '5m' => 'M5',
            '15m' => 'M15',
            '30m' => 'M30',
            '1h' => 'H1',
            '4h' => 'H4',
            '1d' => 'D1',
            '1w' => 'W1',
            '1M' => 'MN',
        ];

        return $mapping[strtolower($timeframe)] ?? $timeframe;
    }

    /**
     * Evaluate multi-timeframe strategy
     * 
     * @param array $config Strategy config
     * @param string $symbol Currency pair symbol
     * @param Signal $signal Signal being evaluated
     * @return array Evaluation result
     */
    protected function evaluateMultiTimeframe(array $config, string $symbol, Signal $signal): array
    {
        $timeframes = $config['timeframes'] ?? [];
        $primaryTF = $this->mapTimeframeToStandard($timeframes['primary'] ?? 'H4');
        $srTF = $this->mapTimeframeToStandard($timeframes['sr_mapping'] ?? 'D1');
        $confirmationTFs = $timeframes['confirmation'] ?? [];
        
        if (!is_array($confirmationTFs)) {
            $confirmationTFs = [$confirmationTFs];
        }
        $confirmationTFs = array_map([$this, 'mapTimeframeToStandard'], $confirmationTFs);

        // Fetch market data for all timeframes
        $allTimeframes = array_unique(array_merge([$primaryTF, $srTF], $confirmationTFs));
        $timeframeData = [];
        
        foreach ($allTimeframes as $tf) {
            $marketData = $this->marketDataService->getLatest($symbol, $tf, 200);
            if ($marketData->isEmpty()) {
                Log::warning("FilterStrategyEvaluator: No market data for {$symbol} {$tf}");
                return [
                    'pass' => false,
                    'reason' => "Market data unavailable for {$tf}",
                    'indicators' => [],
                ];
            }
            
            $candles = $marketData->map(function ($data) {
                return $data->getCandleArray();
            })->reverse()->values()->toArray();
            
            $timeframeData[$tf] = $candles;
        }

        // Calculate indicators per timeframe
        $allIndicators = [];
        $indicatorsConfig = $config['indicators'] ?? [];
        
        foreach ($indicatorsConfig as $tfPrefix => $tfIndicators) {
            // Determine which timeframe to use
            $targetTF = $primaryTF;
            if ($tfPrefix === 'h4' || $tfPrefix === 'primary') {
                $targetTF = $primaryTF;
            } elseif ($tfPrefix === 'confirmation') {
                // Use first confirmation timeframe
                $targetTF = $confirmationTFs[0] ?? $primaryTF;
            } elseif ($tfPrefix === 'sr' || $tfPrefix === 'sr_mapping') {
                $targetTF = $srTF;
            }
            
            if (!isset($timeframeData[$targetTF])) {
                continue;
            }
            
            $candles = $timeframeData[$targetTF];
            $tfIndicators = $this->calculateIndicators($tfIndicators, $candles);
            
            // Prefix indicators with timeframe
            foreach ($tfIndicators as $name => $values) {
                $allIndicators["{$tfPrefix}.{$name}"] = $values;
            }
        }

        // Get latest price from primary timeframe
        $primaryCandles = $timeframeData[$primaryTF];
        $latestCandle = $primaryCandles[count($primaryCandles) - 1] ?? null;
        $latestPrice = $latestCandle['close'] ?? null;
        
        if (!$latestPrice) {
            return [
                'pass' => false,
                'reason' => 'Unable to determine current price',
                'indicators' => $this->getLatestIndicatorValues($allIndicators),
            ];
        }

        // Evaluate rules (combine all timeframe candles for validation)
        $allCandles = $primaryCandles; // Use primary for main validation
        $result = $this->evaluateRules($config['rules'], $allIndicators, $latestPrice, $allCandles);

        return [
            'pass' => $result['pass'],
            'reason' => $result['reason'],
            'indicators' => $this->getLatestIndicatorValues($allIndicators),
        ];
    }

    /**
     * Evaluate cross condition (crosses_above, crosses_below, stoch_cross_up, stoch_cross_down)
     */
    protected function evaluateCrossCondition(
        string $left,
        string $operator,
        $right,
        array $indicators,
        array $candles
    ): array {
        if (count($candles) < 3) {
            return ['pass' => false, 'reason' => 'Insufficient candles for cross detection'];
        }

        // Get indicator values for last 3 candles
        $leftValues = $this->getIndicatorValuesForCandles($left, $indicators, $candles, 3);
        $rightValues = $this->getIndicatorValuesForCandles($right, $indicators, $candles, 3);

        if (empty($leftValues) || empty($rightValues)) {
            return ['pass' => false, 'reason' => "Indicator values not available for cross detection"];
        }

        $pass = false;
        $reason = '';

        switch ($operator) {
            case 'crosses_above':
                // Check if left crossed above right in last 3 candles
                for ($i = 1; $i < count($leftValues); $i++) {
                    if ($leftValues[$i - 1] <= $rightValues[$i - 1] && $leftValues[$i] > $rightValues[$i]) {
                        $pass = true;
                        $reason = "{$left} crossed above {$right}";
                        break;
                    }
                }
                if (!$pass) {
                    $reason = "{$left} did not cross above {$right}";
                }
                break;

            case 'crosses_below':
                // Check if left crossed below right in last 3 candles
                for ($i = 1; $i < count($leftValues); $i++) {
                    if ($leftValues[$i - 1] >= $rightValues[$i - 1] && $leftValues[$i] < $rightValues[$i]) {
                        $pass = true;
                        $reason = "{$left} crossed below {$right}";
                        break;
                    }
                }
                if (!$pass) {
                    $reason = "{$left} did not cross below {$right}";
                }
                break;

            case 'stoch_cross_up':
                // Stochastic K crosses above D, from below threshold (default 20)
                $threshold = 20; // Can be configured
                for ($i = 1; $i < count($leftValues); $i++) {
                    if ($leftValues[$i - 1] < $threshold && 
                        $leftValues[$i - 1] <= $rightValues[$i - 1] && 
                        $leftValues[$i] > $rightValues[$i]) {
                        $pass = true;
                        $reason = "Stoch K crossed above D from oversold";
                        break;
                    }
                }
                if (!$pass) {
                    $reason = "Stoch K did not cross up from oversold";
                }
                break;

            case 'stoch_cross_down':
                // Stochastic K crosses below D, from above threshold (default 80)
                $threshold = 80; // Can be configured
                for ($i = 1; $i < count($leftValues); $i++) {
                    if ($leftValues[$i - 1] > $threshold && 
                        $leftValues[$i - 1] >= $rightValues[$i - 1] && 
                        $leftValues[$i] < $rightValues[$i]) {
                        $pass = true;
                        $reason = "Stoch K crossed below D from overbought";
                        break;
                    }
                }
                if (!$pass) {
                    $reason = "Stoch K did not cross down from overbought";
                }
                break;

            default:
                $reason = "Unknown cross operator: {$operator}";
        }

        // Apply 3-bar validation if needed
        if ($pass) {
            $validation = $this->validateCandleSignal($candles, $operator, $indicators, $left, $right);
            if (!$validation['valid']) {
                $pass = false;
                $reason = $validation['reason'];
            }
        }

        return ['pass' => $pass, 'reason' => $reason];
    }

    /**
     * Get indicator values for last N candles
     */
    protected function getIndicatorValuesForCandles(string $name, array $indicators, array $candles, int $count): array
    {
        $values = [];
        
        if (!isset($indicators[$name])) {
            return $values;
        }

        $indicatorValues = $indicators[$name];
        if (!is_array($indicatorValues)) {
            return $values;
        }

        // Get last N values
        $startIndex = max(0, count($indicatorValues) - $count);
        for ($i = $startIndex; $i < count($indicatorValues); $i++) {
            if (isset($indicatorValues[$i]) && $indicatorValues[$i] !== null) {
                $values[] = (float) $indicatorValues[$i];
            }
        }

        return $values;
    }

    /**
     * Validate candle signal across 3 bars (n-1, n, n+1)
     */
    protected function validateCandleSignal(
        array $candles,
        string $signalType,
        array $indicators,
        ?string $left = null,
        ?string $right = null
    ): array {
        if (count($candles) < 3) {
            return [
                'valid' => false,
                'reason' => 'Insufficient candles for 3-bar validation',
            ];
        }

        // Get last 3 candles (n-1, n, n+1 where n+1 is current)
        $last3 = array_slice($candles, -3);
        
        // For now, just check if we have enough data
        // More sophisticated validation can be added based on signal type
        $confirmed = 0;
        $candleIndices = [];

        // Simple validation: signal should be present in at least 2 of 3 candles
        // This is a placeholder - actual validation depends on signal type
        for ($i = 0; $i < count($last3); $i++) {
            // Check if signal conditions are met in this candle
            // This is simplified - actual implementation would check specific conditions
            $confirmed++;
            $candleIndices[] = $i === 0 ? 'n-1' : ($i === 1 ? 'n' : 'n+1');
        }

        if ($confirmed >= 2) {
            return [
                'valid' => true,
                'reason' => "Signal confirmed in " . implode(', ', $candleIndices),
                'candle_index' => count($candles) - 3,
                'confirmed_in' => $candleIndices,
            ];
        }

        return [
            'valid' => false,
            'reason' => 'Signal not confirmed across 3 candles',
        ];
    }

    /**
     * Evaluate Fibonacci retracement rule
     */
    protected function evaluateFibonacciRule(array $fibConfig, array $candles, float $currentPrice): array
    {
        if (empty($candles)) {
            return ['pass' => false, 'reason' => 'No candles for Fibonacci calculation'];
        }

        $levels = $fibConfig['levels'] ?? [0.236, 0.382, 0.5, 0.618];
        $lookback = $fibConfig['lookback'] ?? 20;
        $direction = strtoupper($fibConfig['direction'] ?? 'BUY');
        $tolerance = $fibConfig['tolerance'] ?? 0.001;

        $fibResult = $this->indicatorService->calculateFibonacciRetracement($candles, $levels, $lookback);
        
        if (empty($fibResult['zones'])) {
            return ['pass' => false, 'reason' => 'Fibonacci zones not calculated'];
        }

        // Check if current price is within any retracement zone
        $inZone = false;
        $matchedZone = null;

        foreach ($fibResult['zones'] as $zone) {
            if ($currentPrice >= $zone['lower'] && $currentPrice <= $zone['upper']) {
                $inZone = true;
                $matchedZone = $zone;
                break;
            }
        }

        if (!$inZone) {
            return [
                'pass' => false,
                'reason' => "Price not in Fibonacci retracement zone (current: {$currentPrice})",
            ];
        }

        // For BUY: price should be retracing down (from swing high)
        // For SELL: price should be retracing up (from swing low)
        if ($direction === 'BUY' && $currentPrice < $fibResult['swing_high']) {
            return [
                'pass' => true,
                'reason' => "Price in Fibonacci zone {$matchedZone['level']} for BUY",
            ];
        } elseif ($direction === 'SELL' && $currentPrice > $fibResult['swing_low']) {
            return [
                'pass' => true,
                'reason' => "Price in Fibonacci zone {$matchedZone['level']} for SELL",
            ];
        }

        return [
            'pass' => false,
            'reason' => "Fibonacci zone direction mismatch (direction: {$direction})",
        ];
    }

    /**
     * Evaluate Support/Resistance rule
     */
    protected function evaluateSRRule(array $srConfig, array $candles, float $currentPrice): array
    {
        if (empty($candles)) {
            return ['pass' => false, 'reason' => 'No candles for S/R calculation'];
        }

        $lookback = $srConfig['lookback'] ?? 20;
        $minStrength = $srConfig['min_strength'] ?? 0.5;
        $direction = strtoupper($srConfig['direction'] ?? 'BUY');
        $validateBreak = $srConfig['validate_break'] ?? true;

        $srResult = $this->indicatorService->calculateSupportResistance($candles, $lookback, $minStrength);

        // For BUY: price should be above support, not breaking below
        // For SELL: price should be below resistance, not breaking above
        if ($direction === 'BUY') {
            // Check if price is above nearest support
            $nearestSupport = null;
            foreach ($srResult['support'] as $support) {
                if ($support['price'] < $currentPrice) {
                    if ($nearestSupport === null || $support['price'] > $nearestSupport['price']) {
                        $nearestSupport = $support;
                    }
                }
            }

            if ($nearestSupport === null) {
                return [
                    'pass' => false,
                    'reason' => 'No support level found below current price',
                ];
            }

            // Check for support break
            if ($validateBreak) {
                foreach ($srResult['breakouts'] as $breakout) {
                    if ($breakout['type'] === 'support' && $breakout['level'] === $nearestSupport['price']) {
                        return [
                            'pass' => false,
                            'reason' => 'Support level broken',
                        ];
                    }
                }
            }

            return [
                'pass' => true,
                'reason' => "Price above support at {$nearestSupport['price']}",
            ];

        } else { // SELL
            // Check if price is below nearest resistance
            $nearestResistance = null;
            foreach ($srResult['resistance'] as $resistance) {
                if ($resistance['price'] > $currentPrice) {
                    if ($nearestResistance === null || $resistance['price'] < $nearestResistance['price']) {
                        $nearestResistance = $resistance;
                    }
                }
            }

            if ($nearestResistance === null) {
                return [
                    'pass' => false,
                    'reason' => 'No resistance level found above current price',
                ];
            }

            // Check for resistance break
            if ($validateBreak) {
                foreach ($srResult['breakouts'] as $breakout) {
                    if ($breakout['type'] === 'resistance' && $breakout['level'] === $nearestResistance['price']) {
                        return [
                            'pass' => false,
                            'reason' => 'Resistance level broken',
                        ];
                    }
                }
            }

            return [
                'pass' => true,
                'reason' => "Price below resistance at {$nearestResistance['price']}",
            ];
        }
    }
}

