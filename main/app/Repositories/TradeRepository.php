<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TradeRepository implements TradeRepositoryInterface
{
    /**
     * Get trades for a user with pagination
     */
    public function getUserTrades(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return DB::table('trades')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a trade by ID for a specific user
     */
    public function getUserTrade(int $tradeId, int $userId): ?object
    {
        return DB::table('trades')
            ->where('id', $tradeId)
            ->where('user_id', $userId)
            ->first();
    }
}

