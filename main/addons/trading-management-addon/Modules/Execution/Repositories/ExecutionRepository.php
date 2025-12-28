<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\Execution\Repositories;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExecutionRepository implements ExecutionRepositoryInterface
{
    /**
     * Get execution logs with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExecutionLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = ExecutionLog::with(['connection', 'signal']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['connection_id'])) {
            $query->where('connection_id', $filters['connection_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get execution log statistics
     *
     * @return array<string, int>
     */
    public function getExecutionLogStats(): array
    {
        return [
            'total' => ExecutionLog::count(),
            'success' => ExecutionLog::where('status', 'SUCCESS')->count(),
            'failed' => ExecutionLog::where('status', 'FAILED')->count(),
            'pending' => ExecutionLog::where('status', 'PENDING')->count(),
        ];
    }

    /**
     * Get open positions with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOpenPositions(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = ExecutionPosition::with(['connection', 'signal', 'preset'])
            ->where('status', 'open');

        if (isset($filters['connection_id'])) {
            $query->where('connection_id', $filters['connection_id']);
        }

        if (isset($filters['symbol'])) {
            $query->where('symbol', 'like', '%' . $filters['symbol'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get open position statistics
     *
     * @return array<string, mixed>
     */
    public function getOpenPositionStats(): array
    {
        return [
            'total_open' => ExecutionPosition::where('status', 'open')->count(),
            'total_pnl' => ExecutionPosition::where('status', 'open')->sum('pnl'),
            'avg_pnl' => ExecutionPosition::where('status', 'open')->avg('pnl'),
        ];
    }

    /**
     * Get closed positions with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getClosedPositions(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = ExecutionPosition::with(['connection', 'signal', 'preset'])
            ->where('status', 'closed');

        if (isset($filters['connection_id'])) {
            $query->where('connection_id', $filters['connection_id']);
        }

        if (isset($filters['symbol'])) {
            $query->where('symbol', 'like', '%' . $filters['symbol'] . '%');
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('closed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('closed_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('closed_at', 'desc')->paginate($perPage);
    }

    /**
     * Get closed position statistics
     *
     * @return array<string, mixed>
     */
    public function getClosedPositionStats(): array
    {
        return [
            'total_closed' => ExecutionPosition::where('status', 'closed')->count(),
            'total_profit' => ExecutionPosition::where('status', 'closed')->where('pnl', '>', 0)->sum('pnl'),
            'total_loss' => ExecutionPosition::where('status', 'closed')->where('pnl', '<', 0)->sum('pnl'),
            'win_rate' => $this->calculateWinRate(),
        ];
    }

    /**
     * Get position updates by IDs
     *
     * @param array<int> $positionIds
     * @return Collection
     */
    public function getPositionUpdates(array $positionIds): Collection
    {
        if (empty($positionIds)) {
            return collect([]);
        }

        return ExecutionPosition::whereIn('id', $positionIds)
            ->where('status', 'open')
            ->get();
    }

    /**
     * Get analytics metrics
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array<string, mixed>
     */
    public function getAnalyticsMetrics(string $dateFrom, string $dateTo): array
    {
        $baseQuery = ExecutionPosition::where('status', 'closed')
            ->whereBetween('closed_at', [$dateFrom, $dateTo]);

        $metrics = [
            'total_trades' => (clone $baseQuery)->count(),
            'winning_trades' => (clone $baseQuery)->where('pnl', '>', 0)->count(),
            'losing_trades' => (clone $baseQuery)->where('pnl', '<', 0)->count(),
            'total_pnl' => (clone $baseQuery)->sum('pnl'),
            'avg_win' => (clone $baseQuery)->where('pnl', '>', 0)->avg('pnl'),
            'avg_loss' => (clone $baseQuery)->where('pnl', '<', 0)->avg('pnl'),
        ];

        // Calculate derived metrics
        $metrics['win_rate'] = $metrics['total_trades'] > 0
            ? ($metrics['winning_trades'] / $metrics['total_trades']) * 100
            : 0;

        $metrics['profit_factor'] = abs($metrics['avg_loss']) > 0
            ? abs($metrics['avg_win'] / $metrics['avg_loss'])
            : 0;

        return $metrics;
    }

    /**
     * Get daily PnL chart data
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return Collection
     */
    public function getDailyPnlData(string $dateFrom, string $dateTo): Collection
    {
        return ExecutionPosition::select(
            DB::raw('DATE(closed_at) as date'),
            DB::raw('SUM(pnl) as pnl'),
            DB::raw('COUNT(*) as trades')
        )
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top performing connections
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $limit
     * @return Collection
     */
    public function getTopConnections(string $dateFrom, string $dateTo, int $limit = 10): Collection
    {
        return ExecutionPosition::select(
            'connection_id',
            DB::raw('COUNT(*) as total_trades'),
            DB::raw('SUM(pnl) as total_pnl')
        )
            ->with('connection')
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$dateFrom, $dateTo])
            ->groupBy('connection_id')
            ->orderBy('total_pnl', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate win rate
     *
     * @return float
     */
    public function calculateWinRate(): float
    {
        $total = ExecutionPosition::where('status', 'closed')->count();
        $wins = ExecutionPosition::where('status', 'closed')->where('pnl', '>', 0)->count();

        return $total > 0 ? ($wins / $total) * 100 : 0;
    }
}

