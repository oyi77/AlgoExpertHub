<?php

declare(strict_types=1);

namespace Addons\TradingManagement\Modules\Execution\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ExecutionRepositoryInterface
{
    /**
     * Get execution logs with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExecutionLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * Get execution log statistics
     *
     * @return array<string, int>
     */
    public function getExecutionLogStats(): array;

    /**
     * Get open positions with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getOpenPositions(array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * Get open position statistics
     *
     * @return array<string, mixed>
     */
    public function getOpenPositionStats(): array;

    /**
     * Get closed positions with filters
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getClosedPositions(array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * Get closed position statistics
     *
     * @return array<string, mixed>
     */
    public function getClosedPositionStats(): array;

    /**
     * Get position updates by IDs
     *
     * @param array<int> $positionIds
     * @return Collection
     */
    public function getPositionUpdates(array $positionIds): Collection;

    /**
     * Get analytics metrics
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array<string, mixed>
     */
    public function getAnalyticsMetrics(string $dateFrom, string $dateTo): array;

    /**
     * Get daily PnL chart data
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return Collection
     */
    public function getDailyPnlData(string $dateFrom, string $dateTo): Collection;

    /**
     * Get top performing connections
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $limit
     * @return Collection
     */
    public function getTopConnections(string $dateFrom, string $dateTo, int $limit = 10): Collection;

    /**
     * Calculate win rate
     *
     * @return float
     */
    public function calculateWinRate(): float;
}

