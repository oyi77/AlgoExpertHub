<?php

namespace App\Repositories\Contracts;

interface BacktestRepositoryInterface
{
    /**
     * Get backtests for a specific user with optional filters
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByUser(int $userId, array $filters = []);

    /**
     * Get backtest with trades relationship loaded
     *
     * @param int $backtestId
     * @return mixed
     */
    public function getWithTrades(int $backtestId);

    /**
     * Get backtests by status with optional limit
     *
     * @param string $status
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status, int $limit = 50);
}
