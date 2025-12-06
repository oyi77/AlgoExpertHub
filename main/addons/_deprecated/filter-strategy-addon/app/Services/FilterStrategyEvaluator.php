<?php

namespace Addons\FilterStrategyAddon\App\Services;

use Addons\FilterStrategyAddon\App\Models\FilterStrategy;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

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
     * @param ExecutionConnection|null $connection Optional connection for market data
     * @return array ['pass' => bool, 'reason' => string, 'indicators' => array]
     */
    public function evaluate(
        FilterStrategy $strategy,
        Signal $signal,
        ?ExecutionConnection $connection = null
    ): array {
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
                // Fail-safe: if config invalid, reject
                return [
                    'pass' => false,
                    'reason' => 'Invalid filter strategy configuration',
                    'indicators' => [],
                ];
            }

            // Get symbol and timeframe from signal
            $symbol = $signal->pair->name ?? null;
            $timeframe = $signal->time->name ?? '1h';

            if (!$symbol) {
                return [
                    'pass' => false,
                    'reason' => 'Signal missing currency pair',
                    'indicators' => [],
                ];
            }

            // Fetch market data
            $candles = $this->marketDataService->getOhlcv($symbol, $timeframe, 200, $connection);
            
            if (empty($candles)) {
                // Fail-safe: if no market data, reject
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

            // Calculate indicators
            $indicators = $this->calculateIndicators($config['indicators'], $candles);
            
            // Get latest price for PSAR comparison
            $latestPrice = $candles[count($candles) - 1]['close'] ?? null;
            if (!$latestPrice) {
                return [
                    'pass' => false,
                    'reason' => 'Unable to determine current price',
                    'indicators' => $indicators,
                ];
            }

            // Evaluate rules
            $result = $this->evaluateRules($config['rules'], $indicators, $latestPrice);

            return [
                'pass' => $result['pass'],
                'reason' => $result['reason'],
                'indicators' => $indicators,
            ];

        } catch (\Exception $e) {
            Log::error("FilterStrategyEvaluator: Evaluation failed", [
                'strategy_id' => $strategy->id,
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fail-safe: reject on error
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
                        $indicators[$name] = $stoch['k']; // Use %K for evaluation
                        $indicators[$name . '_d'] = $stoch['d']; // Store %D as well
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
    protected function evaluateRules(array $rules, array $indicators, float $currentPrice): array
    {
        $logic = strtoupper($rules['logic'] ?? 'AND');
        $conditions = $rules['conditions'] ?? [];

        if (empty($conditions)) {
            return [
                'pass' => false,
                'reason' => 'No conditions defined',
            ];
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

            $result = $this->evaluateCondition($left, $operator, $right, $indicators, $currentPrice);
            $results[] = $result['pass'];
            
            if (!$result['pass']) {
                $reasons[] = $result['reason'];
            }
        }

        // Apply logic (AND or OR)
        if ($logic === 'OR') {
            $pass = in_array(true, $results, true);
        } else {
            // AND (default)
            $pass = !in_array(false, $results, true) && !empty($results);
        }

        return [
            'pass' => $pass,
            'reason' => $pass 
                ? 'All conditions met' 
                : 'Failed conditions: ' . implode(', ', $reasons),
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
        float $currentPrice
    ): array {
        // Get left value from indicators
        $leftValue = $this->getIndicatorValue($left, $indicators, $currentPrice);
        
        if ($leftValue === null) {
            return [
                'pass' => false,
                'reason' => "Indicator '{$left}' not available",
            ];
        }

        // Get right value (could be number or another indicator)
        $rightValue = $this->getRightValue($right, $indicators, $currentPrice);

        // Evaluate operator
        $pass = false;
        $reason = '';

        switch ($operator) {
            case '>':
                $pass = $leftValue > $rightValue;
                $reason = "{$left} ({$leftValue}) > {$right} ({$rightValue})";
                break;

            case '<':
                $pass = $leftValue < $rightValue;
                $reason = "{$left} ({$leftValue}) < {$right} ({$rightValue})";
                break;

            case '>=':
                $pass = $leftValue >= $rightValue;
                $reason = "{$left} ({$leftValue}) >= {$right} ({$rightValue})";
                break;

            case '<=':
                $pass = $leftValue <= $rightValue;
                $reason = "{$left} ({$leftValue}) <= {$right} ({$rightValue})";
                break;

            case '==':
            case '=':
                $pass = abs($leftValue - $rightValue) < 0.0001; // Float comparison
                $reason = "{$left} ({$leftValue}) == {$right} ({$rightValue})";
                break;

            case 'below_price':
            case 'under_price':
                // Special case: PSAR below price
                $pass = $leftValue < $currentPrice;
                $reason = "{$left} ({$leftValue}) < current price ({$currentPrice})";
                break;

            case 'above_price':
            case 'over_price':
                // Special case: PSAR above price
                $pass = $leftValue > $currentPrice;
                $reason = "{$left} ({$leftValue}) > current price ({$currentPrice})";
                break;

            default:
                $pass = false;
                $reason = "Unknown operator: {$operator}";
        }

        return [
            'pass' => $pass,
            'reason' => $pass ? $reason : "NOT {$reason}",
        ];
    }

    /**
     * Get indicator value (latest value from array)
     */
    protected function getIndicatorValue(string $name, array $indicators, float $currentPrice): ?float
    {
        if (!isset($indicators[$name])) {
            return null;
        }

        $values = $indicators[$name];
        if (!is_array($values) || empty($values)) {
            return null;
        }

        // Get last non-null value
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if ($values[$i] !== null) {
                return (float) $values[$i];
            }
        }

        return null;
    }

    /**
     * Get right value (could be number or indicator)
     */
    protected function getRightValue($right, array $indicators, float $currentPrice): float
    {
        if (is_numeric($right)) {
            return (float) $right;
        }

        // Try as indicator name
        if (is_string($right) && isset($indicators[$right])) {
            $value = $this->getIndicatorValue($right, $indicators, $currentPrice);
            if ($value !== null) {
                return $value;
            }
        }

        // Default to 0 if can't resolve
        return 0.0;
    }
}

