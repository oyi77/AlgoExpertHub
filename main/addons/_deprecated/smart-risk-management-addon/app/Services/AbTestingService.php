<?php

namespace Addons\SmartRiskManagement\App\Services;

use Addons\SmartRiskManagement\App\Models\AbTest;
use Addons\SmartRiskManagement\App\Models\AbTestAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbTestingService
{
    /**
     * Create a new A/B test
     */
    public function createTest(string $name, array $pilotLogic, float $pilotPercentage = 10.0): AbTest
    {
        return AbTest::create([
            'name' => $name,
            'pilot_group_percentage' => $pilotPercentage,
            'pilot_logic' => $pilotLogic,
            'control_logic' => [], // Current production logic
            'status' => 'draft',
        ]);
    }

    /**
     * Assign user to pilot or control group
     */
    public function assignUserToGroup(int $userId, int $abTestId): string
    {
        try {
            // Check if already assigned
            $existing = AbTestAssignment::where('ab_test_id', $abTestId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                return $existing->group_type;
            }

            // Use consistent hashing to ensure same user always in same group
            $hash = md5($userId . '_' . $abTestId);
            $hashValue = hexdec(substr($hash, 0, 8));
            $percentage = $hashValue % 100;

            $test = AbTest::findOrFail($abTestId);
            $groupType = $percentage < $test->pilot_group_percentage ? 'pilot' : 'control';

            AbTestAssignment::create([
                'ab_test_id' => $abTestId,
                'user_id' => $userId,
                'group_type' => $groupType,
            ]);

            return $groupType;
        } catch (\Exception $e) {
            Log::error("AbTestingService: Failed to assign user to group", [
                'user_id' => $userId,
                'ab_test_id' => $abTestId,
                'error' => $e->getMessage(),
            ]);
            return 'control'; // Default to control on error
        }
    }

    /**
     * Compare results between pilot and control groups
     */
    public function compareResults(int $abTestId): array
    {
        try {
            $test = AbTest::findOrFail($abTestId);

            // Get pilot and control assignments
            $pilotAssignments = $test->pilotAssignments;
            $controlAssignments = $test->controlAssignments;

            // Calculate metrics for each group
            $pilotMetrics = $this->calculateGroupMetrics($pilotAssignments, $test->start_date, $test->end_date);
            $controlMetrics = $this->calculateGroupMetrics($controlAssignments, $test->start_date, $test->end_date);

            // Update test with results
            $test->update([
                'pilot_group_size' => $pilotAssignments->count(),
                'control_group_size' => $controlAssignments->count(),
                'pilot_avg_pnl' => $pilotMetrics['avg_pnl'],
                'control_avg_pnl' => $controlMetrics['avg_pnl'],
                'pilot_avg_drawdown' => $pilotMetrics['avg_drawdown'],
                'control_avg_drawdown' => $controlMetrics['avg_drawdown'],
                'pilot_win_rate' => $pilotMetrics['win_rate'],
                'control_win_rate' => $controlMetrics['win_rate'],
            ]);

            return [
                'pilot' => $pilotMetrics,
                'control' => $controlMetrics,
            ];
        } catch (\Exception $e) {
            Log::error("AbTestingService: Failed to compare results", [
                'ab_test_id' => $abTestId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate metrics for a group
     */
    protected function calculateGroupMetrics($assignments, $startDate, $endDate): array
    {
        if ($assignments->isEmpty()) {
            return [
                'avg_pnl' => 0,
                'avg_drawdown' => 0,
                'win_rate' => 0,
            ];
        }

        // Get positions for these users/connections
        $connectionIds = $assignments->pluck('connection_id')->filter()->toArray();
        $userIds = $assignments->pluck('user_id')->filter()->toArray();

        $totalPnL = 0;
        $totalDrawdown = 0;
        $winningTrades = 0;
        $totalTrades = 0;

        if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
            $positions = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::where('status', 'closed')
                ->whereBetween('closed_at', [$startDate, $endDate])
                ->where(function ($q) use ($connectionIds, $userIds) {
                    if (!empty($connectionIds)) {
                        $q->whereIn('connection_id', $connectionIds);
                    }
                    if (!empty($userIds)) {
                        $q->orWhereHas('connection', function ($q2) use ($userIds) {
                            $q2->whereIn('user_id', $userIds);
                        });
                    }
                })
                ->get();

            foreach ($positions as $position) {
                $totalPnL += $position->pnl ?? 0;
                if ($position->pnl > 0) {
                    $winningTrades++;
                }
                $totalTrades++;
            }
        }

        $count = max(1, $assignments->count());

        return [
            'avg_pnl' => $totalPnL / $count,
            'avg_drawdown' => $totalDrawdown / $count,
            'win_rate' => $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0,
        ];
    }

    /**
     * Calculate statistical significance (p-value)
     */
    public function calculateStatisticalSignificance(int $abTestId): float
    {
        try {
            $test = AbTest::findOrFail($abTestId);

            if (!$test->pilot_avg_pnl || !$test->control_avg_pnl) {
                return 1.0; // No significance if no data
            }

            // Simplified t-test calculation
            // In production, use proper statistical library
            $pilotMean = $test->pilot_avg_pnl;
            $controlMean = $test->control_avg_pnl;
            $pilotSize = $test->pilot_group_size;
            $controlSize = $test->control_group_size;

            if ($pilotSize < 2 || $controlSize < 2) {
                return 1.0; // Not enough data
            }

            // Simplified p-value calculation
            // In production, use proper t-test
            $diff = abs($pilotMean - $controlMean);
            $pooledStd = sqrt(($pilotMean + $controlMean) / ($pilotSize + $controlSize));
            $se = $pooledStd * sqrt(1/$pilotSize + 1/$controlSize);
            
            if ($se == 0) {
                return 1.0;
            }

            $t = $diff / $se;
            $pValue = 2 * (1 - $this->normalCDF(abs($t)));

            // Update test
            $test->update([
                'p_value' => $pValue,
                'is_significant' => $pValue < 0.05,
            ]);

            return $pValue;
        } catch (\Exception $e) {
            Log::error("AbTestingService: Failed to calculate statistical significance", [
                'ab_test_id' => $abTestId,
                'error' => $e->getMessage(),
            ]);
            return 1.0;
        }
    }

    /**
     * Normal CDF approximation (simplified)
     */
    protected function normalCDF(float $x): float
    {
        // Simplified approximation
        return 0.5 * (1 + tanh(sqrt(2 / M_PI) * ($x + 0.044715 * pow($x, 3))));
    }
}

