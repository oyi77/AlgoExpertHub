<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

interface TradingRepositoryInterface
{
    /**
     * Get trading bots for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserBots(int $userId, int $perPage = 20): LengthAwarePaginator;
}
