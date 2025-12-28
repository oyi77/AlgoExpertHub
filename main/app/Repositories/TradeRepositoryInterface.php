<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TradeRepositoryInterface
{
    /**
     * Get trades for a user with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserTrades(int $userId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get a trade by ID for a specific user
     *
     * @param int $tradeId
     * @param int $userId
     * @return object|null
     */
    public function getUserTrade(int $tradeId, int $userId): ?object;
}

