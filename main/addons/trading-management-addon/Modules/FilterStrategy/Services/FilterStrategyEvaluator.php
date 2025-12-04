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

            // Get symbol and timeframe from signal
            $symbol = $signal->pair->name ?? null;
            $timeframe = $this->mapTimeframeToStandard($signal->time->name ?? 'H1');

            if (!$symbol) {
                return [
                    'pass' => false,
                    'reason' => 'Signal missing currency pair',
                    'indicators' => [],
                ];
            }

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
            $result = $this->evaluateRules($config['rules'], $indicators, $latestPrice);

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
    protected function evaluateRules(array $rules, array $indicators, float $currentPrice): array
    {
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

            $result = $this->evaluateCondition($left, $operator, $right, $indicators, $currentPrice);
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
        float $currentPrice
    ): array {
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
        if (!isset($indicators[$name])) return null;

        $values = $indicators[$name];
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

        if (is_string($right) && isset($indicators[$right])) {
            $value = $this->getIndicatorValue($right, $indicators, $currentPrice);
            if ($value !== null) {
                return $value;
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
}

