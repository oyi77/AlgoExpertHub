<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\Execution\Services;

use Addons\TradingManagement\Modules\Execution\Repositories\ExecutionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExecutionOperationsService
{
    protected ExecutionRepositoryInterface $repository;

    public function __construct(ExecutionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get execution logs with filters
     *
     * @param Request $request
     * @return array{executions: LengthAwarePaginator, stats: array<string, int>}
     */
    public function getExecutionLogs(Request $request): array
    {
        $filters = [
            'status' => $request->filled('status') ? $request->status : null,
            'connection_id' => $request->filled('connection_id') ? $request->connection_id : null,
            'date_from' => $request->filled('date_from') ? $request->date_from : null,
            'date_to' => $request->filled('date_to') ? $request->date_to : null,
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $executions = $this->repository->getExecutionLogs($filters);
        $stats = $this->repository->getExecutionLogStats();

        return [
            'executions' => $executions,
            'stats' => $stats,
        ];
    }

    /**
     * Get open positions with filters
     *
     * @param Request $request
     * @return array{positions: LengthAwarePaginator, stats: array<string, mixed>}
     */
    public function getOpenPositions(Request $request): array
    {
        $filters = [
            'connection_id' => $request->filled('connection_id') ? $request->connection_id : null,
            'symbol' => $request->filled('symbol') ? $request->symbol : null,
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $positions = $this->repository->getOpenPositions($filters);
        $stats = $this->repository->getOpenPositionStats();

        return [
            'positions' => $positions,
            'stats' => $stats,
        ];
    }

    /**
     * Get position updates
     *
     * @param array<int> $positionIds
     * @return Collection
     */
    public function getPositionUpdates(array $positionIds): Collection
    {
        $positions = $this->repository->getPositionUpdates($positionIds);

        return $positions->map(function ($position) {
            return [
                'id' => $position->id,
                'current_price' => $position->current_price,
                'pnl' => $position->pnl,
                'pnl_percentage' => $position->pnl_percentage,
                'last_price_update_at' => $position->last_price_update_at ? $position->last_price_update_at->toIso8601String() : null,
            ];
        });
    }

    /**
     * Get closed positions with filters
     *
     * @param Request $request
     * @return array{positions: LengthAwarePaginator, stats: array<string, mixed>}
     */
    public function getClosedPositions(Request $request): array
    {
        $filters = [
            'connection_id' => $request->filled('connection_id') ? $request->connection_id : null,
            'symbol' => $request->filled('symbol') ? $request->symbol : null,
            'date_from' => $request->filled('date_from') ? $request->date_from : null,
            'date_to' => $request->filled('date_to') ? $request->date_to : null,
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $positions = $this->repository->getClosedPositions($filters);
        $stats = $this->repository->getClosedPositionStats();

        return [
            'positions' => $positions,
            'stats' => $stats,
        ];
    }

    /**
     * Get analytics data
     *
     * @param Request $request
     * @return array{metrics: array<string, mixed>, dailyPnl: Collection, topConnections: Collection, dateFrom: string, dateTo: string}
     */
    public function getAnalytics(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $metrics = $this->repository->getAnalyticsMetrics($dateFrom, $dateTo);
        $dailyPnl = $this->repository->getDailyPnlData($dateFrom, $dateTo);
        $topConnections = $this->repository->getTopConnections($dateFrom, $dateTo);

        return [
            'metrics' => $metrics,
            'dailyPnl' => $dailyPnl,
            'topConnections' => $topConnections,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }
}

