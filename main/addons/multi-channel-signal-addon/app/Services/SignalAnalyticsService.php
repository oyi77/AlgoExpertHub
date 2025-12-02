<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\SignalAnalytic;
use App\Models\Plan;
use App\Models\Signal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SignalAnalyticsService
{
    /**
     * Track signal creation for analytics.
     *
     * @param Signal $signal
     * @param int|null $channelSourceId
     * @param array $metadata
     * @return SignalAnalytic|null
     */
    public function trackSignal(Signal $signal, ?int $channelSourceId = null, array $metadata = []): ?SignalAnalytic
    {
        try {
            $analytic = SignalAnalytic::create([
                'signal_id' => $signal->id,
                'channel_source_id' => $channelSourceId,
                'currency_pair' => $signal->pair->name ?? null,
                'direction' => $signal->direction,
                'open_price' => $signal->open_price,
                'sl' => $signal->sl,
                'tp' => $signal->tp,
                'signal_received_at' => now(),
                'signal_published_at' => $signal->is_published ? now() : null,
                'trade_status' => 'pending',
                'metadata' => $metadata,
            ]);

            Log::info("Tracked signal analytics for signal {$signal->id}");

            return $analytic;
        } catch (\Exception $e) {
            Log::error("Failed to track signal analytics: " . $e->getMessage(), [
                'signal_id' => $signal->id,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Track signal distribution to user/plan.
     *
     * @param Signal $signal
     * @param int|null $planId
     * @param int|null $userId
     * @return SignalAnalytic|null
     */
    public function trackDistribution(Signal $signal, ?int $planId = null, ?int $userId = null): ?SignalAnalytic
    {
        try {
            // Find or create base analytic
            $analytic = SignalAnalytic::where('signal_id', $signal->id)
                ->whereNull('plan_id')
                ->whereNull('user_id')
                ->first();

            if (!$analytic) {
                $analytic = $this->trackSignal($signal, $signal->channel_source_id);
            }

            if ($analytic && ($planId || $userId)) {
                // Create user-specific analytic entry
                SignalAnalytic::create([
                    'signal_id' => $signal->id,
                    'channel_source_id' => $signal->channel_source_id,
                    'plan_id' => $planId,
                    'user_id' => $userId,
                    'currency_pair' => $signal->pair->name ?? null,
                    'direction' => $signal->direction,
                    'open_price' => $signal->open_price,
                    'sl' => $signal->sl,
                    'tp' => $signal->tp,
                    'signal_received_at' => $analytic->signal_received_at ?? now(),
                    'signal_published_at' => $signal->is_published ? now() : null,
                    'trade_status' => 'pending',
                    'metadata' => $analytic->metadata ?? [],
                ]);
            }

            return $analytic;
        } catch (\Exception $e) {
            Log::error("Failed to track signal distribution: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update trade execution data.
     *
     * @param int $signalId
     * @param float|null $actualOpenPrice
     * @param float|null $actualClosePrice
     * @param int|null $userId
     * @return bool
     */
    public function updateTradeExecution(
        int $signalId,
        ?float $actualOpenPrice = null,
        ?float $actualClosePrice = null,
        ?int $userId = null
    ): bool {
        try {
            $query = SignalAnalytic::where('signal_id', $signalId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->whereNull('user_id');
            }

            $analytic = $query->first();

            if (!$analytic) {
                return false;
            }

            $updates = [];

            if ($actualOpenPrice !== null) {
                $updates['actual_open_price'] = $actualOpenPrice;
                $updates['trade_opened_at'] = now();
                $updates['trade_status'] = 'open';
            }

            if ($actualClosePrice !== null && $analytic->actual_open_price) {
                $updates['actual_close_price'] = $actualClosePrice;
                $updates['trade_closed_at'] = now();
                $updates['trade_status'] = 'closed';

                // Calculate profit/loss
                $direction = $analytic->direction === 'buy' ? 1 : -1;
                $priceDiff = ($actualClosePrice - $analytic->actual_open_price) * $direction;
                $updates['profit_loss'] = $priceDiff;

                // Calculate pips (simplified)
                if ($analytic->currency_pair) {
                    $pips = $this->calculatePips(
                        $analytic->currency_pair,
                        $analytic->actual_open_price,
                        $actualClosePrice,
                        $direction
                    );
                    $updates['pips'] = $pips;
                }
            }

            if (!empty($updates)) {
                $analytic->update($updates);
                Log::info("Updated trade execution for signal {$signalId}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to update trade execution: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate pips from price difference.
     */
    protected function calculatePips(string $currencyPair, float $openPrice, float $closePrice, int $direction): float
    {
        $priceDiff = abs($closePrice - $openPrice);
        
        // Determine pip value based on pair
        // For major pairs, 1 pip = 0.0001 for most pairs, 0.01 for JPY pairs
        $isJpyPair = strpos($currencyPair, 'JPY') !== false;
        $pipValue = $isJpyPair ? 0.01 : 0.0001;
        
        $pips = ($priceDiff / $pipValue) * $direction;
        
        return round($pips, 2);
    }

    /**
     * Get analytics summary for a channel.
     *
     * @param int $channelSourceId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getChannelAnalytics(int $channelSourceId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $query = SignalAnalytic::byChannel($channelSourceId)
            ->whereBetween('signal_received_at', [$startDate, $endDate]);

        $totalSignals = $query->count();
        $publishedSignals = (clone $query)->whereNotNull('signal_published_at')->count();
        $closedTrades = (clone $query)->closed()->count();
        $profitableTrades = (clone $query)->profitable()->count();
        $lossTrades = (clone $query)->loss()->count();

        $totalProfitLoss = (clone $query)->closed()->sum('profit_loss') ?? 0;
        $totalPips = (clone $query)->closed()->sum('pips') ?? 0;

        $winRate = $closedTrades > 0 ? ($profitableTrades / $closedTrades) * 100 : 0;
        $avgProfitLoss = $closedTrades > 0 ? ($totalProfitLoss / $closedTrades) : 0;
        $avgPips = $closedTrades > 0 ? ($totalPips / $closedTrades) : 0;

        return [
            'total_signals' => $totalSignals,
            'published_signals' => $publishedSignals,
            'closed_trades' => $closedTrades,
            'profitable_trades' => $profitableTrades,
            'loss_trades' => $lossTrades,
            'win_rate' => round($winRate, 2),
            'total_profit_loss' => round($totalProfitLoss, 2),
            'avg_profit_loss' => round($avgProfitLoss, 2),
            'total_pips' => round($totalPips, 2),
            'avg_pips' => round($avgPips, 2),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get analytics summary for a plan.
     *
     * @param int $planId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getPlanAnalytics(int $planId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $query = SignalAnalytic::byPlan($planId)
            ->whereBetween('signal_received_at', [$startDate, $endDate]);

        $totalSignals = $query->count();
        $closedTrades = (clone $query)->closed()->count();
        $profitableTrades = (clone $query)->profitable()->count();
        $lossTrades = (clone $query)->loss()->count();

        $totalProfitLoss = (clone $query)->closed()->sum('profit_loss') ?? 0;
        $winRate = $closedTrades > 0 ? ($profitableTrades / $closedTrades) * 100 : 0;

        return [
            'total_signals' => $totalSignals,
            'closed_trades' => $closedTrades,
            'profitable_trades' => $profitableTrades,
            'loss_trades' => $lossTrades,
            'win_rate' => round($winRate, 2),
            'total_profit_loss' => round($totalProfitLoss, 2),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get daily signal statistics.
     *
     * @param int|null $channelSourceId
     * @param int $days
     * @return array
     */
    public function getDailyStatistics(?int $channelSourceId = null, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $query = SignalAnalytic::where('signal_received_at', '>=', $startDate);
        
        if ($channelSourceId) {
            $query->byChannel($channelSourceId);
        }

        $stats = $query->select(
            DB::raw('DATE(signal_received_at) as date'),
            DB::raw('COUNT(*) as total_signals'),
            DB::raw('SUM(CASE WHEN signal_published_at IS NOT NULL THEN 1 ELSE 0 END) as published_signals'),
            DB::raw('SUM(CASE WHEN trade_status = "closed" THEN 1 ELSE 0 END) as closed_trades'),
            DB::raw('SUM(CASE WHEN trade_status = "closed" AND profit_loss > 0 THEN 1 ELSE 0 END) as profitable_trades')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return $stats->toArray();
    }
}

