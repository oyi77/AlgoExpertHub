<?php

namespace App\Repositories;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Pagination\LengthAwarePaginator;

class TradingRepository implements TradingRepositoryInterface
{
    /**
     * Get trading bots for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserBots(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        if (!class_exists(TradingBot::class)) {
            return new LengthAwarePaginator(collect([]), 0, $perPage, 1);
        }

        return TradingBot::where('user_id', $userId)
            ->with(['exchangeConnection', 'tradingPreset', 'filterStrategy', 'aiModelProfile'])
            ->latest()
            ->paginate($perPage, ['*'], 'bots_page');
    }
}
