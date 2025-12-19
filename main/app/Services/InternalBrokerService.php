<?php

namespace App\Services;

use App\Models\InternalTrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternalBrokerService
{
    /**
     * Place a new internal trade
     */
    public function placeOrder(
        User $user,
        string $symbol,
        string $direction,
        float $quantity,
        float $currentPrice,
        ?float $slPrice = null,
        ?float $tpPrice = null
    ): InternalTrade {
        DB::beginTransaction();
        
        try {
            // Validate user has sufficient balance
            $requiredMargin = $this->calculateRequiredMargin($quantity, $currentPrice);
            
            if ($user->balance < $requiredMargin) {
                throw new \Exception('Insufficient balance. Required: ' . $requiredMargin . ', Available: ' . $user->balance);
            }

            // Create the trade
            $trade = InternalTrade::create([
                'user_id' => $user->id,
                'symbol' => strtoupper($symbol),
                'direction' => strtolower($direction),
                'quantity' => $quantity,
                'entry_price' => $currentPrice,
                'current_price' => $currentPrice,
                'sl_price' => $slPrice,
                'tp_price' => $tpPrice,
                'pnl' => 0,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            // Deduct margin from user balance (optional, depends on your business logic)
            // $user->balance -= $requiredMargin;
            // $user->save();

            DB::commit();

            Log::info('Internal trade placed', [
                'trade_id' => $trade->id,
                'user_id' => $user->id,
                'symbol' => $symbol,
                'direction' => $direction,
                'quantity' => $quantity,
                'entry_price' => $currentPrice,
            ]);

            return $trade;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to place internal trade', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Close an internal trade
     */
    public function closePosition(InternalTrade $trade, float $closePrice): InternalTrade
    {
        if ($trade->isClosed()) {
            throw new \Exception('Trade is already closed');
        }

        DB::beginTransaction();

        try {
            $trade->close($closePrice);

            DB::commit();

            Log::info('Internal trade closed', [
                'trade_id' => $trade->id,
                'user_id' => $trade->user_id,
                'entry_price' => $trade->entry_price,
                'close_price' => $closePrice,
                'pnl' => $trade->pnl,
            ]);

            return $trade;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to close internal trade', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update position with current price and check SL/TP
     */
    public function updatePosition(InternalTrade $trade, float $currentPrice): void
    {
        if ($trade->isClosed()) {
            return;
        }

        // Update P&L
        $trade->updatePnL($currentPrice);

        // Check stop-loss
        if ($trade->sl_price) {
            $shouldCloseSL = ($trade->direction === 'buy' && $currentPrice <= $trade->sl_price) ||
                            ($trade->direction === 'sell' && $currentPrice >= $trade->sl_price);

            if ($shouldCloseSL) {
                $this->closePosition($trade, $currentPrice);
                Log::info('Position closed by stop-loss', [
                    'trade_id' => $trade->id,
                    'sl_price' => $trade->sl_price,
                    'close_price' => $currentPrice,
                ]);
                return;
            }
        }

        // Check take-profit
        if ($trade->tp_price) {
            $shouldCloseTP = ($trade->direction === 'buy' && $currentPrice >= $trade->tp_price) ||
                            ($trade->direction === 'sell' && $currentPrice <= $trade->tp_price);

            if ($shouldCloseTP) {
                $this->closePosition($trade, $currentPrice);
                Log::info('Position closed by take-profit', [
                    'trade_id' => $trade->id,
                    'tp_price' => $trade->tp_price,
                    'close_price' => $currentPrice,
                ]);
                return;
            }
        }
    }

    /**
     * Calculate required margin for a trade
     */
    protected function calculateRequiredMargin(float $quantity, float $price): float
    {
        // Simple calculation: quantity * price
        // You can implement leverage here if needed
        return $quantity * $price;
    }

    /**
     * Get user's open positions
     */
    public function getUserOpenPositions(User $user)
    {
        return InternalTrade::byUser($user->id)
            ->open()
            ->orderBy('opened_at', 'desc')
            ->get();
    }

    /**
     * Get user's closed positions
     */
    public function getUserClosedPositions(User $user, int $limit = 50)
    {
        return InternalTrade::byUser($user->id)
            ->closed()
            ->orderBy('closed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's total P&L
     */
    public function getUserTotalPnL(User $user): float
    {
        return InternalTrade::byUser($user->id)
            ->closed()
            ->sum('pnl');
    }
}
