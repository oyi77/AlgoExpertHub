<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use App\Models\Signal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FilterStrategyService
 * 
 * Enhanced service for applying multiple filters with priority and logging
 */
class FilterStrategyService
{
    protected FilterStrategyEvaluator $evaluator;

    public function __construct(FilterStrategyEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * Apply multiple filters to a signal for a bot
     * 
     * @param TradingBot $bot
     * @param Signal $signal
     * @return FilterResult
     */
    public function applyFilters(TradingBot $bot, Signal $signal): FilterResult
    {
        // Get filter priority configuration from bot
        $filterPriority = $bot->filter_priority ?? [];
        
        // If bot has single filter_strategy_id, use it
        if ($bot->filter_strategy_id && empty($filterPriority)) {
            return $this->applySingleFilter($bot, $signal, $bot->filterStrategy);
        }

        // Apply multiple filters in priority order
        if (empty($filterPriority)) {
            // No filters configured, pass
            return new FilterResult(true, 'No filters configured', []);
        }

        $results = [];
        $allPassed = true;
        $reasons = [];

        // Sort filters by priority (lower number = higher priority)
        uasort($filterPriority, function ($a, $b) {
            $priorityA = $a['priority'] ?? 999;
            $priorityB = $b['priority'] ?? 999;
            return $priorityA <=> $priorityB;
        });

        foreach ($filterPriority as $filterConfig) {
            if (!($filterConfig['enabled'] ?? true)) {
                continue; // Skip disabled filters
            }

            $filterId = $filterConfig['filter_strategy_id'] ?? null;
            if (!$filterId) {
                continue;
            }

            $filter = FilterStrategy::find($filterId);
            if (!$filter || !$filter->enabled) {
                continue;
            }

            // Evaluate filter
            $result = $this->evaluator->evaluate($filter, $signal);
            
            $filterResult = [
                'filter_id' => $filterId,
                'filter_name' => $filter->name,
                'priority' => $filterConfig['priority'] ?? 999,
                'passed' => $result['pass'] ?? false,
                'reason' => $result['reason'] ?? 'Unknown',
                'indicators' => $result['indicators'] ?? [],
            ];

            $results[] = $filterResult;

            // Log filter result
            $this->logFilterResult($bot, $signal, $filterResult);

            if (!$filterResult['passed']) {
                $allPassed = false;
                $reasons[] = "{$filter->name}: {$filterResult['reason']}";
                
                // If filter logic is AND (all must pass), stop on first failure
                $logic = $filterConfig['logic'] ?? 'AND';
                if ($logic === 'AND') {
                    break;
                }
            }
        }

        $finalReason = $allPassed 
            ? 'All filters passed' 
            : 'Failed filters: ' . implode('; ', $reasons);

        return new FilterResult($allPassed, $finalReason, $results);
    }

    /**
     * Apply single filter (backward compatibility)
     * 
     * @param TradingBot $bot
     * @param Signal $signal
     * @param FilterStrategy $filter
     * @return FilterResult
     */
    protected function applySingleFilter(TradingBot $bot, Signal $signal, ?FilterStrategy $filter): FilterResult
    {
        if (!$filter || !$filter->enabled) {
            return new FilterResult(true, 'No filter strategy assigned', []);
        }

        $result = $this->evaluator->evaluate($filter, $signal);

        $filterResult = [
            'filter_id' => $filter->id,
            'filter_name' => $filter->name,
            'priority' => 1,
            'passed' => $result['pass'] ?? false,
            'reason' => $result['reason'] ?? 'Unknown',
            'indicators' => $result['indicators'] ?? [],
        ];

        $this->logFilterResult($bot, $signal, $filterResult);

        return new FilterResult(
            $result['pass'] ?? false,
            $result['reason'] ?? 'Filter evaluation completed',
            [$filterResult]
        );
    }

    /**
     * Log filter result to database
     * 
     * @param TradingBot $bot
     * @param Signal $signal
     * @param array $filterResult
     * @return void
     */
    public function logFilterResult(TradingBot $bot, Signal $signal, array $filterResult): void
    {
        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('trading_bot_filter_results')) {
                return; // Table not created yet, skip logging
            }

            DB::table('trading_bot_filter_results')->insert([
                'bot_id' => $bot->id,
                'signal_id' => $signal->id,
                'filter_strategy_id' => $filterResult['filter_id'],
                'passed' => $filterResult['passed'],
                'result_data' => json_encode([
                    'reason' => $filterResult['reason'],
                    'indicators' => $filterResult['indicators'] ?? [],
                    'priority' => $filterResult['priority'] ?? 999,
                ]),
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log filter result', [
                'bot_id' => $bot->id,
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get filter statistics for a bot
     * 
     * @param TradingBot $bot
     * @return array
     */
    public function getFilterStatistics(TradingBot $bot): array
    {
        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('trading_bot_filter_results')) {
                return [
                    'total_evaluations' => 0,
                    'passed' => 0,
                    'failed' => 0,
                    'pass_rate' => 0,
                    'by_filter' => [],
                ];
            }

            $stats = DB::table('trading_bot_filter_results')
                ->where('bot_id', $bot->id)
                ->selectRaw('
                    COUNT(*) as total_evaluations,
                    SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            $total = $stats->total_evaluations ?? 0;
            $passed = $stats->passed ?? 0;
            $failed = $stats->failed ?? 0;
            $passRate = $total > 0 ? ($passed / $total) * 100 : 0;

            // Get stats by filter
            $byFilter = DB::table('trading_bot_filter_results')
                ->where('bot_id', $bot->id)
                ->join('filter_strategies', 'trading_bot_filter_results.filter_strategy_id', '=', 'filter_strategies.id')
                ->selectRaw('
                    filter_strategies.id,
                    filter_strategies.name,
                    COUNT(*) as total,
                    SUM(CASE WHEN trading_bot_filter_results.passed = 1 THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN trading_bot_filter_results.passed = 0 THEN 1 ELSE 0 END) as failed
                ')
                ->groupBy('filter_strategies.id', 'filter_strategies.name')
                ->get()
                ->map(function ($item) {
                    $total = $item->total ?? 0;
                    $passed = $item->passed ?? 0;
                    return [
                        'filter_id' => $item->id,
                        'filter_name' => $item->name,
                        'total' => $total,
                        'passed' => $passed,
                        'failed' => $item->failed ?? 0,
                        'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
                    ];
                })
                ->toArray();

            return [
                'total_evaluations' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'pass_rate' => round($passRate, 2),
                'by_filter' => $byFilter,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get filter statistics', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_evaluations' => 0,
                'passed' => 0,
                'failed' => 0,
                'pass_rate' => 0,
                'by_filter' => [],
            ];
        }
    }
}

/**
 * FilterResult DTO
 */
class FilterResult
{
    public bool $passed;
    public string $reason;
    public array $filterResults;

    public function __construct(bool $passed, string $reason, array $filterResults = [])
    {
        $this->passed = $passed;
        $this->reason = $reason;
        $this->filterResults = $filterResults;
    }

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'reason' => $this->reason,
            'filter_results' => $this->filterResults,
        ];
    }
}

