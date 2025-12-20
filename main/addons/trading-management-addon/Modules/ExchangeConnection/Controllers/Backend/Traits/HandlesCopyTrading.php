<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;

trait HandlesCopyTrading
{
    /**
     * Toggle copy trading for connection
     */
    public function toggleCopyTrading(ExchangeConnection $exchangeConnection)
    {
        try {
            $exchangeConnection->update([
                'copy_trading_enabled' => !$exchangeConnection->copy_trading_enabled,
            ]);

            return response()->json([
                'success' => true,
                'message' => $exchangeConnection->copy_trading_enabled 
                    ? 'Copy trading enabled' 
                    : 'Copy trading disabled',
                'connection' => [
                    'id' => $exchangeConnection->id,
                    'copy_trading_enabled' => $exchangeConnection->copy_trading_enabled,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle copy trading: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get copy trading stats for connection
     */
    public function getCopyTradingStats(ExchangeConnection $exchangeConnection)
    {
        try {
            // Get copy trading subscriptions for this connection
            $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('connection_id', $exchangeConnection->id)
                ->where('is_active', true)
                ->count();

            // Get copied trades count (executions from this connection that were copied)
            $copiedTradesCount = \Addons\TradingManagement\Modules\Execution\Models\ExecutionPosition::where('connection_id', $exchangeConnection->id)
                ->whereNotNull('copied_from_position_id')
                ->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'copy_trading_enabled' => $exchangeConnection->copy_trading_enabled,
                    'active_followers' => $subscriptions,
                    'copied_trades_count' => $copiedTradesCount,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get copy trading stats: ' . $e->getMessage(),
            ], 400);
        }
    }
}

