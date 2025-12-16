<?php

namespace Addons\TradingManagement\Modules\TradingBot\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Illuminate\Support\Facades\Log;

/**
 * PositionMonitoringService
 * 
 * Centralized position management for trading bots
 */
class PositionMonitoringService
{
    /**
     * Get all open positions for bot
     * 
     * @param TradingBot $bot
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOpenPositions(TradingBot $bot)
    {
        return TradingBotPosition::where('bot_id', $bot->id)
            ->where('status', 'open')
            ->get();
    }

    /**
     * Check stop loss for position
     * 
     * @param TradingBotPosition $position
     * @return bool True if SL hit
     */
    public function checkStopLoss(TradingBotPosition $position): bool
    {
        if (!$position->isOpen() || !$position->stop_loss) {
            return false;
        }

        // Update current price first
        $this->updatePositionPrice($position);

        if ($position->isStopLossHit()) {
            $this->closePosition($position, 'stop_loss_hit');
            return true;
        }

        return false;
    }

    /**
     * Check take profit for position
     * 
     * @param TradingBotPosition $position
     * @return bool True if TP hit
     */
    public function checkTakeProfit(TradingBotPosition $position): bool
    {
        if (!$position->isOpen() || !$position->take_profit) {
            return false;
        }

        // Update current price first
        $this->updatePositionPrice($position);

        if ($position->isTakeProfitHit()) {
            $this->closePosition($position, 'take_profit_hit');
            return true;
        }

        return false;
    }

    /**
     * Update position current price from exchange
     * 
     * @param TradingBotPosition $position
     * @return void
     */
    public function updatePositionPrice(TradingBotPosition $position): void
    {
        try {
            $bot = $position->bot;
            if (!$bot || !$bot->exchangeConnection) {
                return;
            }

            // Get current price from exchange
            // This would use the exchange connection adapter
            // For now, placeholder - will be implemented with exchange adapters
            $currentPrice = $this->fetchCurrentPrice($bot->exchangeConnection, $position->symbol);

            if ($currentPrice) {
                $position->update(['current_price' => $currentPrice]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update position price', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Close position
     * 
     * @param TradingBotPosition $position
     * @param string $reason
     * @return void
     */
    public function closePosition(TradingBotPosition $position, string $reason): void
    {
        try {
            // Calculate P/L
            $profitLoss = $this->calculateProfitLoss($position);

            // Update position
            $position->update([
                'status' => 'closed',
                'profit_loss' => $profitLoss,
                'close_reason' => $reason,
                'closed_at' => now(),
            ]);

            // Close linked ExecutionPosition if exists
            if ($position->executionPosition && $position->executionPosition->isOpen()) {
                try {
                    $executionPosition = $position->executionPosition;
                    $connection = $executionPosition->connection;
                    
                    if ($connection) {
                        // Get adapter and close on exchange
                        $adapterFactory = app(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class);
                        $adapter = $adapterFactory->create($connection->provider, $connection->credentials ?? []);
                        
                        if (method_exists($adapter, 'closePosition')) {
                            $result = $adapter->closePosition($executionPosition->order_id);
                            if (!$result['success']) {
                                Log::warning('Failed to close ExecutionPosition on exchange', [
                                    'execution_position_id' => $executionPosition->id,
                                    'error' => $result['error'] ?? 'Unknown error',
                                ]);
                            }
                        }
                    }
                    
                    // Update ExecutionPosition status
                    $executionPosition->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                        'closed_reason' => $this->mapCloseReason($reason),
                        'current_price' => $position->current_price,
                    ]);
                    
                    // Update PnL
                    $executionPosition->updatePnL($position->current_price);
                    
                    Log::info('ExecutionPosition closed via TradingBotPosition', [
                        'execution_position_id' => $executionPosition->id,
                        'bot_position_id' => $position->id,
                        'reason' => $reason,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to close ExecutionPosition', [
                        'bot_position_id' => $position->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Trading bot position closed', [
                'position_id' => $position->id,
                'bot_id' => $position->bot_id,
                'reason' => $reason,
                'profit_loss' => $profitLoss,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate profit/loss for position
     * 
     * @param TradingBotPosition $position
     * @return float
     */
    public function calculateProfitLoss(TradingBotPosition $position): float
    {
        if (!$position->current_price || !$position->entry_price) {
            return 0;
        }

        $priceDiff = $position->current_price - $position->entry_price;
        
        if (in_array($position->direction, ['buy', 'long'])) {
            // Profit if price goes up
            return $priceDiff * $position->quantity;
        } else {
            // Profit if price goes down
            return -$priceDiff * $position->quantity;
        }
    }

    /**
     * Fetch current price from exchange
     * 
     * @param \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection $connection
     * @param string $symbol
     * @return float|null
     */
    protected function fetchCurrentPrice($connection, string $symbol): ?float
    {
        try {
            $credentials = $connection->credentials ?? [];
            $provider = $connection->provider;
            $adapter = new CcxtAdapter($provider, $credentials);
            $result = $adapter->fetchCurrentPrice($symbol);
            if (!isset($result['success']) || !$result['success']) {
                return null;
            }
            $data = $result['data'] ?? [];
            if (isset($data['last'])) {
                return (float) $data['last'];
            }
            if (isset($data['bid'])) {
                return (float) $data['bid'];
            }
            if (isset($data['ask'])) {
                return (float) $data['ask'];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch current price', [
                'connection_id' => $connection->id,
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Monitor all open positions for bot
     * 
     * @param TradingBot $bot
     * @return array ['sl_closed' => int, 'tp_closed' => int]
     */
    public function monitorPositions(TradingBot $bot): array
    {
        $openPositions = $this->getOpenPositions($bot);
        $slClosed = 0;
        $tpClosed = 0;

        foreach ($openPositions as $position) {
            // Check stop loss
            if ($this->checkStopLoss($position)) {
                $slClosed++;
                continue;
            }

            // Check take profit
            if ($this->checkTakeProfit($position)) {
                $tpClosed++;
            }
        }

        // Update bot's last position check time
        $bot->update(['last_position_check_at' => now()]);

        return [
            'sl_closed' => $slClosed,
            'tp_closed' => $tpClosed,
            'total_checked' => $openPositions->count(),
        ];
    }

    /**
     * Map TradingBotPosition close_reason to ExecutionPosition closed_reason
     */
    protected function mapCloseReason(string $botReason): string
    {
        $mapping = [
            'take_profit_hit' => 'tp',
            'stop_loss_hit' => 'sl',
            'manual_close' => 'manual',
            'liquidation' => 'liquidation',
        ];

        return $mapping[$botReason] ?? 'manual';
    }
}
