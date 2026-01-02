<?php

namespace App\Services;

use App\Models\InternalTrade;
use App\Models\User;
use App\Events\PositionUpdated;
use Illuminate\Support\Facades\Log;

class PositionManagementService
{
    protected $brokerService;
    protected $marketDataService;

    public function __construct(
        InternalBrokerService $brokerService,
        MarketDataService $marketDataService
    ) {
        $this->brokerService = $brokerService;
        $this->marketDataService = $marketDataService;
    }

    /**
     * Get user's open positions
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserOpenPositions(User $user)
    {
        return $this->brokerService->getUserOpenPositions($user);
    }

    /**
     * Close a position
     *
     * @param int $tradeId
     * @param User $user
     * @return array
     * @throws \Exception
     */
    public function closePosition(int $tradeId, User $user): array
    {
        try {
            $trade = InternalTrade::where('id', $tradeId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($trade->isClosed()) {
                throw new \Exception('Position is already closed');
            }

            // Get current market price
            $currentPrice = $this->marketDataService->getCurrentPrice($trade->symbol);
            
            if (!$currentPrice) {
                throw new \Exception('Unable to fetch current market price');
            }

            // Close position
            $this->brokerService->closePosition($trade, $currentPrice);

            // Broadcast position update
            broadcast(new PositionUpdated($user->id, [
                'id' => $trade->id,
                'status' => 'closed',
                'close_price' => $currentPrice,
                'pnl' => $trade->pnl,
            ]));

            return [
                'trade_id' => $trade->id,
                'close_price' => $currentPrice,
                'pnl' => $trade->pnl,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'trade_id' => $tradeId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
