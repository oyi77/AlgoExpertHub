<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DexVisualizationService
{
    public function buildPnlHeatmap(int $watchlistId): array
    {
        $records = DB::table('dex_pnl_records')
            ->where('watchlist_id', $watchlistId)
            ->selectRaw('DATE(closed_at) as trade_date, SUM(realized_pnl) as total_pnl')
            ->groupBy('trade_date')
            ->orderBy('trade_date')
            ->get();

        return $records->map(fn ($row) => [
            'date' => $row->trade_date,
            'pnl' => (float) $row->total_pnl,
        ])->all();
    }

    public function buildDrawdownSeries(int $watchlistId): array
    {
        $records = DB::table('dex_pnl_records')
            ->where('watchlist_id', $watchlistId)
            ->orderBy('closed_at')
            ->get(['closed_at', 'realized_pnl']);

        $series = [];
        $cumulative = 0.0;
        $peak = 0.0;

        foreach ($records as $record) {
            $cumulative += (float) $record->realized_pnl;
            $peak = max($peak, $cumulative);
            $drawdown = $peak - $cumulative;
            $series[] = [
                'timestamp' => $record->closed_at,
                'drawdown' => round($drawdown, 4),
            ];
        }

        return $series;
    }

    public function buildConcentrationChart(int $watchlistId): array
    {
        $records = DB::table('dex_position_snapshots')
            ->where('watchlist_id', $watchlistId)
            ->selectRaw('symbol, SUM(size) as total_size')
            ->groupBy('symbol')
            ->orderByDesc('total_size')
            ->get();

        return $records->map(fn ($row) => [
            'symbol' => $row->symbol,
            'size' => (float) $row->total_size,
        ])->all();
    }
}
