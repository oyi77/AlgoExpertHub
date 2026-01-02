<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternalTrade;
use App\Models\User;
use Addons\TradingManagement\Modules\RiskManagement\Services\MarginManagementService;
use Addons\TradingManagement\Modules\RiskManagement\Services\SlippageProtectionService;
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

        // ✅ Bug #1 Fix: Wrap entire method in transaction to ensure atomicity
        DB::beginTransaction();
        
        try {
            // Update P&L
            $trade->updatePnL($currentPrice);

        // Check stop-loss
        if ($trade->sl_price) {
            $shouldCloseSL = ($trade->direction === 'buy' && $currentPrice <= $trade->sl_price) ||
                            ($trade->direction === 'sell' && $currentPrice >= $trade->sl_price);

            if ($shouldCloseSL) {
                // Use execution price (with slippage) instead of trigger price
                $slippageService = app(SlippageProtectionService::class);
                $maxSlippage = $slippageService->getMaxAllowedSlippage([]);
                
                // Estimate slippage (in production, get from exchange)
                $predictedSlippage = $slippageService->predictSlippage($trade->symbol, $trade->quantity);
                $executionPrice = $slippageService->adjustStopLossForSlippage(
                    $trade->sl_price,
                    $predictedSlippage,
                    $trade->direction === 'buy' ? 'sell' : 'buy',
                    $trade->symbol
                );
                
                // Validate slippage
                $actualSlippage = $slippageService->calculateSlippage(
                    $trade->sl_price,
                    $executionPrice,
                    $trade->direction === 'buy' ? 'sell' : 'buy',
                    $trade->symbol
                );
                
                if ($actualSlippage > $maxSlippage) {
                    Log::warning('Slippage exceeded on SL execution', [
                        'trade_id' => $trade->id,
                        'slippage_pips' => $actualSlippage,
                        'max_allowed' => $maxSlippage,
                    ]);
                }
                
                // Note: closePosition() starts its own transaction, so we commit this transaction first
                // to ensure P&L update is saved, then closePosition handles closing in its own transaction
                DB::commit();
                
                // Call closePosition (which starts its own transaction)
                $this->closePosition($trade, $executionPrice);
                Log::info('Position closed by stop-loss', [
                    'trade_id' => $trade->id,
                    'sl_price' => $trade->sl_price,
                    'execution_price' => $executionPrice,
                    'slippage_pips' => $actualSlippage,
                ]);
                return;
            }
        }

        // Check take-profit
        if ($trade->tp_price) {
            $shouldCloseTP = ($trade->direction === 'buy' && $currentPrice >= $trade->tp_price) ||
                            ($trade->direction === 'sell' && $currentPrice <= $trade->tp_price);

            if ($shouldCloseTP) {
                // Use execution price (with slippage) instead of trigger price
                $slippageService = app(SlippageProtectionService::class);
                $maxSlippage = $slippageService->getMaxAllowedSlippage([]);
                
                // Estimate slippage
                $predictedSlippage = $slippageService->predictSlippage($trade->symbol, $trade->quantity);
                $executionPrice = $slippageService->adjustStopLossForSlippage(
                    $trade->tp_price,
                    $predictedSlippage,
                    $trade->direction === 'buy' ? 'sell' : 'buy',
                    $trade->symbol
                );
                
                // Validate slippage
                $actualSlippage = $slippageService->calculateSlippage(
                    $trade->tp_price,
                    $executionPrice,
                    $trade->direction === 'buy' ? 'sell' : 'buy',
                    $trade->symbol
                );
                
                if ($actualSlippage > $maxSlippage) {
                    Log::warning('Slippage exceeded on TP execution', [
                        'trade_id' => $trade->id,
                        'slippage_pips' => $actualSlippage,
                        'max_allowed' => $maxSlippage,
                    ]);
                }
                
                // Note: closePosition() starts its own transaction, so we commit this transaction first
                DB::commit();
                
                // Call closePosition (which starts its own transaction)
                $this->closePosition($trade, $executionPrice);
                Log::info('Position closed by take-profit', [
                    'trade_id' => $trade->id,
                    'tp_price' => $trade->tp_price,
                    'execution_price' => $executionPrice,
                    'slippage_pips' => $actualSlippage,
                ]);
                return;
            }
        }
        
        // ✅ Bug #1 Fix: Commit transaction if no SL/TP triggered
        DB::commit();
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update position', [
                'trade_id' => $trade->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate required margin for a trade
     */
    protected function calculateRequiredMargin(float $quantity, float $price, ?int $leverage = null, ?string $symbol = null): float
    {
        $leverage = $leverage ?? 100; // Default 1:100
        
        // Use MarginManagementService for proper calculation
        $marginService = app(MarginManagementService::class);
        
        // For internal broker, treat quantity as lot size
        // If symbol provided, use proper contract size calculation
        if ($symbol) {
            return $marginService->calculateRequiredMargin($quantity, $price, $leverage, $symbol);
        }
        
        // Fallback: simple calculation with leverage
        $notionalValue = $quantity * $price;
        return $notionalValue / $leverage;
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
