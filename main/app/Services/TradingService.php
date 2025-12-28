<?php

namespace App\Services;

use App\Repositories\TradingRepositoryInterface;
use App\Support\AddonRegistry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TradingService
{
    protected $tradingRepository;

    public function __construct(TradingRepositoryInterface $tradingRepository)
    {
        $this->tradingRepository = $tradingRepository;
    }

    /**
     * Get trading bots for the user, handling errors and addon status.
     *
     * @param int $userId
     * @return LengthAwarePaginator
     */
    public function getTradingBots(int $userId): LengthAwarePaginator
    {
        if (!AddonRegistry::active('trading-management-addon')) {
            return new LengthAwarePaginator(collect([]), 0, 20, 1);
        }

        try {
            return $this->tradingRepository->getUserBots($userId);
        } catch (\Exception $e) {
            Log::error('TradingService: Error loading trading bots', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new LengthAwarePaginator(collect([]), 0, 20, 1);
        }
    }

    /**
     * Check if trading management is enabled.
     *
     * @return bool
     */
    public function isTradingManagementEnabled(): bool
    {
        return AddonRegistry::active('trading-management-addon');
    }
}
