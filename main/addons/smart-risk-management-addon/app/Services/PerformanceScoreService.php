<?php

namespace Addons\SmartRiskManagement\App\Services;

use Addons\SmartRiskManagement\App\Models\SignalProviderMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceScoreService
{
    /**
     * Calculate performance score for a signal provider
     * 
     * Formula: Win Rate (35%) + Max Drawdown (25%) + Reward-to-Risk (20%) + SL Compliance (15%) + Recent Trend (5%)
     * 
     * @param string $signalProviderId Signal provider identifier
     * @param string $type Signal provider type ('channel_source' or 'user')
     * @param Carbon|null $periodStart Period start date
     * @param Carbon|null $periodEnd Period end date
     * @return float Performance score (0-100)
     */
    public function calculatePerformanceScore(
        string $signalProviderId,
        string $type,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null
    ): float {
        try {
            $periodStart = $periodStart ?? now()->subDays(30);
            $periodEnd = $periodEnd ?? now();
            
            // Get or create metrics for the period
            $metrics = $this->getOrCreateMetrics($signalProviderId, $type, $periodStart, $periodEnd);
            
            if (!$metrics || $metrics->total_signals == 0) {
                return 50.0; // Default score if no data
            }
            
            // Calculate components
            $winRate = $metrics->win_rate ?? 0;
            $maxDrawdown = $metrics->max_drawdown ?? 0;
            $rewardToRisk = $metrics->reward_to_risk_ratio ?? 0;
            $slCompliance = $metrics->sl_compliance_rate ?? 0;
            $recentTrend = $this->calculateRecentTrend($signalProviderId, $type);
            
            // Weighted formula
            $score = (
                $winRate * 0.35 +
                (100 - min($maxDrawdown, 100)) * 0.25 + // Invert drawdown (lower is better)
                min($rewardToRisk * 20, 100) * 0.20 + // Cap reward-to-risk at 5.0 (100/20)
                $slCompliance * 0.15 +
                $recentTrend * 0.05
            );
            
            // Ensure score is between 0 and 100
            $score = max(0, min(100, $score));
            
            return round($score, 2);
        } catch (\Exception $e) {
            Log::error("PerformanceScoreService: Failed to calculate performance score", [
                'signal_provider_id' => $signalProviderId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return 50.0; // Default score on error
        }
    }

    /**
     * Get or create metrics for a signal provider and period
     */
    protected function getOrCreateMetrics(
        string $signalProviderId,
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ?SignalProviderMetrics {
        // Try to get existing metrics
        $metrics = SignalProviderMetrics::where('signal_provider_id', $signalProviderId)
            ->where('signal_provider_type', $type)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->where('period_end', $periodEnd->format('Y-m-d'))
            ->where('period_type', 'daily')
            ->first();
        
        if ($metrics) {
            return $metrics;
        }
        
        // Calculate metrics from execution logs
        return $this->calculateMetrics($signalProviderId, $type, $periodStart, $periodEnd);
    }

    /**
     * Calculate metrics from execution logs
     */
    protected function calculateMetrics(
        string $signalProviderId,
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ?SignalProviderMetrics {
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return null;
            }
            
            $logs = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::where('signal_provider_id', $signalProviderId)
                ->where('signal_provider_type', $type)
                ->whereBetween('executed_at', [$periodStart, $periodEnd])
                ->where('status', 'executed')
                ->get();
            
            if ($logs->isEmpty()) {
                return null;
            }
            
            $totalSignals = $logs->count();
            $winningSignals = 0;
            $losingSignals = 0;
            $slippages = [];
            $latencies = [];
            
            // Get positions to determine wins/losses
            foreach ($logs as $log) {
                if ($log->position) {
                    $pnl = $log->position->pnl ?? 0;
                    if ($pnl > 0) {
                        $winningSignals++;
                    } elseif ($pnl < 0) {
                        $losingSignals++;
                    }
                }
                
                if ($log->slippage !== null) {
                    $slippages[] = abs($log->slippage);
                }
                
                if ($log->latency_ms !== null) {
                    $latencies[] = $log->latency_ms;
                }
            }
            
            $winRate = $totalSignals > 0 ? ($winningSignals / $totalSignals) * 100 : 0;
            $avgSlippage = count($slippages) > 0 ? array_sum($slippages) / count($slippages) : 0;
            $maxSlippage = count($slippages) > 0 ? max($slippages) : 0;
            $avgLatency = count($latencies) > 0 ? array_sum($latencies) / count($latencies) : 0;
            
            // Calculate max drawdown (simplified - would need more complex calculation in production)
            $maxDrawdown = $this->calculateMaxDrawdown($signalProviderId, $type, $periodStart, $periodEnd);
            
            // Calculate reward-to-risk ratio (simplified)
            $rewardToRisk = $this->calculateRewardToRisk($signalProviderId, $type, $periodStart, $periodEnd);
            
            // Calculate SL compliance rate (simplified - assumes compliance if SL was hit)
            $slCompliance = $this->calculateSlCompliance($signalProviderId, $type, $periodStart, $periodEnd);
            
            // Get previous performance score
            $previousScore = $this->getPreviousScore($signalProviderId, $type);
            
            // Calculate current score using formula
            $performanceScore = (
                $winRate * 0.35 +
                (100 - min($maxDrawdown, 100)) * 0.25 +
                min($rewardToRisk * 20, 100) * 0.20 +
                $slCompliance * 0.15 +
                50.0 * 0.05 // Default recent trend for now
            );
            $performanceScore = max(0, min(100, round($performanceScore, 2)));
            
            // Determine trend
            $scoreTrend = $this->determineTrend($performanceScore, $previousScore);
            
            // Create metrics record
            $metrics = SignalProviderMetrics::create([
                'signal_provider_id' => $signalProviderId,
                'signal_provider_type' => $type,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'period_type' => 'daily',
                'total_signals' => $totalSignals,
                'winning_signals' => $winningSignals,
                'losing_signals' => $losingSignals,
                'win_rate' => round($winRate, 2),
                'avg_slippage' => round($avgSlippage, 4),
                'max_slippage' => round($maxSlippage, 4),
                'avg_latency_ms' => (int) $avgLatency,
                'max_drawdown' => round($maxDrawdown, 2),
                'reward_to_risk_ratio' => round($rewardToRisk, 4),
                'sl_compliance_rate' => round($slCompliance, 2),
                'performance_score' => $performanceScore,
                'performance_score_previous' => $previousScore,
                'score_trend' => $scoreTrend,
                'calculated_at' => now(),
            ]);
            
            return $metrics;
        } catch (\Exception $e) {
            Log::error("PerformanceScoreService: Failed to calculate metrics", [
                'signal_provider_id' => $signalProviderId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Calculate recent trend score (last 7 days performance)
     */
    protected function calculateRecentTrend(string $signalProviderId, string $type): float
    {
        try {
            $recentStart = now()->subDays(7);
            $recentEnd = now();
            
            $recentMetrics = $this->getOrCreateMetrics($signalProviderId, $type, $recentStart, $recentEnd);
            
            if (!$recentMetrics || $recentMetrics->total_signals == 0) {
                return 50.0; // Neutral trend
            }
            
            // Compare recent win rate to overall
            $overallStart = now()->subDays(30);
            $overallMetrics = $this->getOrCreateMetrics($signalProviderId, $type, $overallStart, $recentEnd);
            
            if (!$overallMetrics || $overallMetrics->total_signals == 0) {
                return 50.0;
            }
            
            $recentWinRate = $recentMetrics->win_rate;
            $overallWinRate = $overallMetrics->win_rate;
            
            // If recent win rate is higher, trend is positive
            if ($recentWinRate > $overallWinRate) {
                return min(100, 50 + (($recentWinRate - $overallWinRate) * 2));
            } else {
                return max(0, 50 - (($overallWinRate - $recentWinRate) * 2));
            }
        } catch (\Exception $e) {
            return 50.0; // Neutral on error
        }
    }

    /**
     * Calculate max drawdown for a signal provider
     */
    protected function calculateMaxDrawdown(
        string $signalProviderId,
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        // Simplified calculation - in production, calculate from cumulative P/L
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
                return 0.0;
            }
            
            // Get all closed positions for this provider
            $positions = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::whereHas('executionLog', function ($q) use ($signalProviderId, $type) {
                $q->where('signal_provider_id', $signalProviderId)
                  ->where('signal_provider_type', $type);
            })
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$periodStart, $periodEnd])
            ->orderBy('closed_at', 'asc')
            ->get();
            
            if ($positions->isEmpty()) {
                return 0.0;
            }
            
            $cumulativePnL = 0;
            $peak = 0;
            $maxDrawdown = 0;
            
            foreach ($positions as $position) {
                $cumulativePnL += $position->pnl ?? 0;
                
                if ($cumulativePnL > $peak) {
                    $peak = $cumulativePnL;
                }
                
                $drawdown = $peak - $cumulativePnL;
                if ($drawdown > $maxDrawdown) {
                    $maxDrawdown = $drawdown;
                }
            }
            
            // Convert to percentage
            return $peak > 0 ? ($maxDrawdown / $peak) * 100 : 0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Calculate reward-to-risk ratio
     */
    protected function calculateRewardToRisk(
        string $signalProviderId,
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        // Simplified - average TP distance / average SL distance
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
                return 1.0;
            }
            
            $logs = \Addons\TradingExecutionEngine\App\Models\ExecutionLog::where('signal_provider_id', $signalProviderId)
                ->where('signal_provider_type', $type)
                ->whereBetween('executed_at', [$periodStart, $periodEnd])
                ->whereNotNull('tp_price')
                ->whereNotNull('sl_price')
                ->get();
            
            if ($logs->isEmpty()) {
                return 1.0;
            }
            
            $ratios = [];
            foreach ($logs as $log) {
                if ($log->entry_price && $log->tp_price && $log->sl_price) {
                    $reward = abs($log->tp_price - $log->entry_price);
                    $risk = abs($log->entry_price - $log->sl_price);
                    
                    if ($risk > 0) {
                        $ratios[] = $reward / $risk;
                    }
                }
            }
            
            return count($ratios) > 0 ? array_sum($ratios) / count($ratios) : 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    /**
     * Calculate SL compliance rate
     */
    protected function calculateSlCompliance(
        string $signalProviderId,
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd
    ): float {
        // Simplified - assumes compliance if position was closed at SL
        // In production, would need to check if SL was actually respected
        try {
            if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class)) {
                return 100.0;
            }
            
            $positions = \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::whereHas('executionLog', function ($q) use ($signalProviderId, $type) {
                $q->where('signal_provider_id', $signalProviderId)
                  ->where('signal_provider_type', $type);
            })
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$periodStart, $periodEnd])
            ->whereNotNull('sl_price')
            ->get();
            
            if ($positions->isEmpty()) {
                return 100.0;
            }
            
            $compliant = 0;
            foreach ($positions as $position) {
                // Check if closed at or near SL (within 1 pip)
                if ($position->closed_reason === 'sl' || 
                    (abs($position->current_price - $position->sl_price) < 0.0001)) {
                    $compliant++;
                }
            }
            
            return ($compliant / $positions->count()) * 100;
        } catch (\Exception $e) {
            return 100.0;
        }
    }

    /**
     * Get previous performance score
     */
    protected function getPreviousScore(string $signalProviderId, string $type): float
    {
        $previous = SignalProviderMetrics::where('signal_provider_id', $signalProviderId)
            ->where('signal_provider_type', $type)
            ->orderBy('period_end', 'desc')
            ->skip(1)
            ->first();
        
        return $previous ? $previous->performance_score : 50.0;
    }

    /**
     * Determine score trend
     */
    protected function determineTrend(float $currentScore, float $previousScore): string
    {
        $diff = $currentScore - $previousScore;
        
        if ($diff > 2) {
            return 'up';
        } elseif ($diff < -2) {
            return 'down';
        } else {
            return 'stable';
        }
    }

    /**
     * Update performance score in real-time (after trade closed)
     */
    public function updatePerformanceScore(string $signalProviderId, string $type): void
    {
        try {
            // Clear cache
            Cache::forget("srm_performance_score_{$signalProviderId}_{$type}");
            
            // Recalculate for last 30 days
            $periodStart = now()->subDays(30);
            $periodEnd = now();
            
            $this->calculateMetrics($signalProviderId, $type, $periodStart, $periodEnd);
        } catch (\Exception $e) {
            Log::error("PerformanceScoreService: Failed to update performance score", [
                'signal_provider_id' => $signalProviderId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get performance score (with caching)
     */
    public function getPerformanceScore(string $signalProviderId, string $type): float
    {
        $cacheKey = "srm_performance_score_{$signalProviderId}_{$type}";
        
        return Cache::remember($cacheKey, 300, function () use ($signalProviderId, $type) {
            $periodStart = now()->subDays(30);
            $periodEnd = now();
            return $this->calculatePerformanceScore($signalProviderId, $type, $periodStart, $periodEnd);
        });
    }

    /**
     * Get score trend
     */
    public function getScoreTrend(string $signalProviderId, string $type): string
    {
        $metrics = SignalProviderMetrics::where('signal_provider_id', $signalProviderId)
            ->where('signal_provider_type', $type)
            ->orderBy('period_end', 'desc')
            ->first();
        
        return $metrics ? $metrics->score_trend : 'stable';
    }
}

