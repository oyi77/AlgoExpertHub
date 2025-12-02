<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Models\SignalAnalytic;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{

    /**
     * Generate daily report.
     *
     * @param Carbon|null $date
     * @param int|null $channelSourceId
     * @param int|null $planId
     * @return array
     */
    public function generateDailyReport(?Carbon $date = null, ?int $channelSourceId = null, ?int $planId = null): array
    {
        $date = $date ?? now();
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        $query = SignalAnalytic::whereBetween('signal_received_at', [$startDate, $endDate]);

        if ($channelSourceId) {
            $query->byChannel($channelSourceId);
        }

        if ($planId) {
            $query->byPlan($planId);
        }

        $signals = $query->get();
        $closedTrades = $signals->where('trade_status', 'closed');

        return [
            'date' => $date->format('Y-m-d'),
            'period' => 'daily',
            'total_signals' => $signals->count(),
            'published_signals' => $signals->whereNotNull('signal_published_at')->count(),
            'closed_trades' => $closedTrades->count(),
            'profitable_trades' => $closedTrades->where('profit_loss', '>', 0)->count(),
            'loss_trades' => $closedTrades->where('profit_loss', '<', 0)->count(),
            'total_profit_loss' => $closedTrades->sum('profit_loss'),
            'total_pips' => $closedTrades->sum('pips'),
            'win_rate' => $closedTrades->count() > 0 
                ? ($closedTrades->where('profit_loss', '>', 0)->count() / $closedTrades->count()) * 100 
                : 0,
            'signals_by_channel' => $this->groupByChannel($signals),
            'signals_by_pair' => $this->groupByPair($signals),
        ];
    }

    /**
     * Generate weekly report.
     *
     * @param Carbon|null $startDate
     * @param int|null $channelSourceId
     * @param int|null $planId
     * @return array
     */
    public function generateWeeklyReport(?Carbon $startDate = null, ?int $channelSourceId = null, ?int $planId = null): array
    {
        $startDate = $startDate ?? now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        return $this->generatePeriodReport($startDate, $endDate, 'weekly', $channelSourceId, $planId);
    }

    /**
     * Generate monthly report.
     *
     * @param Carbon|null $startDate
     * @param int|null $channelSourceId
     * @param int|null $planId
     * @return array
     */
    public function generateMonthlyReport(?Carbon $startDate = null, ?int $channelSourceId = null, ?int $planId = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return $this->generatePeriodReport($startDate, $endDate, 'monthly', $channelSourceId, $planId);
    }

    /**
     * Generate custom period report.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $periodType
     * @param int|null $channelSourceId
     * @param int|null $planId
     * @return array
     */
    protected function generatePeriodReport(
        Carbon $startDate,
        Carbon $endDate,
        string $periodType,
        ?int $channelSourceId = null,
        ?int $planId = null
    ): array {
        $query = SignalAnalytic::whereBetween('signal_received_at', [$startDate, $endDate]);

        if ($channelSourceId) {
            $query->byChannel($channelSourceId);
        }

        if ($planId) {
            $query->byPlan($planId);
        }

        $signals = $query->get();
        $closedTrades = $signals->where('trade_status', 'closed');

        // Daily breakdown
        $dailyBreakdown = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $daySignals = $signals->filter(function ($signal) use ($currentDate) {
                return $signal->signal_received_at->isSameDay($currentDate);
            });
            $dayClosed = $daySignals->where('trade_status', 'closed');

            $dailyBreakdown[] = [
                'date' => $currentDate->format('Y-m-d'),
                'total_signals' => $daySignals->count(),
                'closed_trades' => $dayClosed->count(),
                'profitable_trades' => $dayClosed->where('profit_loss', '>', 0)->count(),
                'total_profit_loss' => $dayClosed->sum('profit_loss'),
            ];

            $currentDate->addDay();
        }

        return [
            'period' => $periodType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_signals' => $signals->count(),
            'published_signals' => $signals->whereNotNull('signal_published_at')->count(),
            'closed_trades' => $closedTrades->count(),
            'profitable_trades' => $closedTrades->where('profit_loss', '>', 0)->count(),
            'loss_trades' => $closedTrades->where('profit_loss', '<', 0)->count(),
            'total_profit_loss' => $closedTrades->sum('profit_loss'),
            'total_pips' => $closedTrades->sum('pips'),
            'avg_profit_loss' => $closedTrades->count() > 0 
                ? $closedTrades->sum('profit_loss') / $closedTrades->count() 
                : 0,
            'win_rate' => $closedTrades->count() > 0 
                ? ($closedTrades->where('profit_loss', '>', 0)->count() / $closedTrades->count()) * 100 
                : 0,
            'daily_breakdown' => $dailyBreakdown,
            'signals_by_channel' => $this->groupByChannel($signals),
            'signals_by_pair' => $this->groupByPair($signals),
            'top_performers' => $this->getTopPerformers($closedTrades),
            'worst_performers' => $this->getWorstPerformers($closedTrades),
        ];
    }

    /**
     * Group signals by channel.
     */
    protected function groupByChannel($signals): array
    {
        return $signals->groupBy('channel_source_id')->map(function ($group, $channelId) {
            $closed = $group->where('trade_status', 'closed');
            return [
                'channel_id' => $channelId,
                'channel_name' => $group->first()->channelSource->name ?? 'Unknown',
                'total_signals' => $group->count(),
                'closed_trades' => $closed->count(),
                'total_profit_loss' => $closed->sum('profit_loss'),
            ];
        })->values()->toArray();
    }

    /**
     * Group signals by currency pair.
     */
    protected function groupByPair($signals): array
    {
        return $signals->groupBy('currency_pair')->map(function ($group, $pair) {
            $closed = $group->where('trade_status', 'closed');
            return [
                'currency_pair' => $pair,
                'total_signals' => $group->count(),
                'closed_trades' => $closed->count(),
                'total_profit_loss' => $closed->sum('profit_loss'),
                'win_rate' => $closed->count() > 0 
                    ? ($closed->where('profit_loss', '>', 0)->count() / $closed->count()) * 100 
                    : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Get top performing signals.
     */
    protected function getTopPerformers($closedTrades, int $limit = 10): array
    {
        return $closedTrades->where('profit_loss', '>', 0)
            ->sortByDesc('profit_loss')
            ->take($limit)
            ->map(function ($signal) {
                return [
                    'signal_id' => $signal->signal_id,
                    'currency_pair' => $signal->currency_pair,
                    'direction' => $signal->direction,
                    'profit_loss' => $signal->profit_loss,
                    'pips' => $signal->pips,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get worst performing signals.
     */
    protected function getWorstPerformers($closedTrades, int $limit = 10): array
    {
        return $closedTrades->where('profit_loss', '<', 0)
            ->sortBy('profit_loss')
            ->take($limit)
            ->map(function ($signal) {
                return [
                    'signal_id' => $signal->signal_id,
                    'currency_pair' => $signal->currency_pair,
                    'direction' => $signal->direction,
                    'profit_loss' => $signal->profit_loss,
                    'pips' => $signal->pips,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Export report to CSV.
     *
     * @param array $reportData
     * @return string
     */
    public function exportToCsv(array $reportData): string
    {
        $filename = 'signal_report_' . ($reportData['period'] ?? 'custom') . '_' . now()->format('Y-m-d') . '.csv';
        $filepath = storage_path('app/reports/' . $filename);

        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Write header
        fputcsv($file, ['Period', 'Total Signals', 'Published', 'Closed Trades', 'Profitable', 'Loss', 'Total P/L', 'Win Rate']);

        // Write data
        fputcsv($file, [
            $reportData['period'] ?? 'N/A',
            $reportData['total_signals'] ?? 0,
            $reportData['published_signals'] ?? 0,
            $reportData['closed_trades'] ?? 0,
            $reportData['profitable_trades'] ?? 0,
            $reportData['loss_trades'] ?? 0,
            $reportData['total_profit_loss'] ?? 0,
            number_format($reportData['win_rate'] ?? 0, 2) . '%',
        ]);

        fclose($file);

        return $filepath;
    }
}

