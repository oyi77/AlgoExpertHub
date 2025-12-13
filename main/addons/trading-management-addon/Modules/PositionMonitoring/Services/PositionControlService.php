<?php

namespace Addons\TradingManagement\Modules\PositionMonitoring\Services;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Illuminate\Support\Facades\Log;

/**
 * PositionControlService
 * 
 * Service for real-time position control (update TP/SL, close positions)
 */
class PositionControlService
{
    /**
     * Update position TP/SL
     * 
     * @param TradingBotPosition $position
     * @param array $data ['stop_loss' => float, 'take_profit' => float]
     * @return array ['success' => bool, 'message' => string]
     */
    public function updatePosition(TradingBotPosition $position, array $data): array
    {
        try {
            $updated = false;

            // Update stop loss if provided
            if (isset($data['stop_loss'])) {
                if ($this->validateStopLoss($position, $data['stop_loss'])) {
                    $position->stop_loss = $data['stop_loss'];
                    $updated = true;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid stop loss value',
                    ];
                }
            }

            // Update take profit if provided
            if (isset($data['take_profit'])) {
                if ($this->validateTakeProfit($position, $data['take_profit'])) {
                    $position->take_profit = $data['take_profit'];
                    $updated = true;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid take profit value',
                    ];
                }
            }

            if ($updated) {
                $position->save();

                // Also update ExecutionPosition if linked
                if ($position->execution_position_id) {
                    $this->updateExecutionPosition($position);
                }

                Log::info('Position TP/SL updated', [
                    'position_id' => $position->id,
                    'bot_id' => $position->bot_id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Position updated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'No changes provided',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update position', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Close position
     * 
     * @param TradingBotPosition $position
     * @param string $reason
     * @return array ['success' => bool, 'message' => string]
     */
    public function closePosition(TradingBotPosition $position, string $reason): array
    {
        try {
            if (!$position->isOpen()) {
                return [
                    'success' => false,
                    'message' => 'Position is already closed',
                ];
            }

            // Calculate P/L
            $profitLoss = $this->calculateProfitLoss($position);

            // Update position
            $position->update([
                'status' => 'closed',
                'profit_loss' => $profitLoss,
                'close_reason' => $reason,
                'closed_at' => now(),
            ]);

            // Close ExecutionPosition if linked
            if ($position->execution_position_id) {
                $this->closeExecutionPosition($position);
            }

            Log::info('Position closed', [
                'position_id' => $position->id,
                'bot_id' => $position->bot_id,
                'reason' => $reason,
                'profit_loss' => $profitLoss,
            ]);

            return [
                'success' => true,
                'message' => 'Position closed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to close position', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current balance from exchange
     * 
     * @param ExchangeConnection $connection
     * @return array Balance data
     */
    public function getBalance(ExchangeConnection $connection): array
    {
        try {
            $adapter = $this->getAdapter($connection);

            if (method_exists($adapter, 'fetchBalance')) {
                $balance = $adapter->fetchBalance();
                return $balance ?? [];
            } elseif (method_exists($adapter, 'getAccountInfo')) {
                $accountInfo = $adapter->getAccountInfo();
                return [
                    'balance' => $accountInfo['balance'] ?? 0,
                    'equity' => $accountInfo['equity'] ?? 0,
                    'margin' => $accountInfo['margin'] ?? 0,
                    'free_margin' => $accountInfo['freeMargin'] ?? $accountInfo['free_margin'] ?? 0,
                ];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get balance', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Update stop loss on exchange
     */
    protected function updateStopLoss(TradingBotPosition $position, float $newSl): bool
    {
        if (!$position->executionPosition) {
            return false;
        }

        try {
            $bot = $position->bot;
            $connection = $bot->exchangeConnection;
            $adapter = $this->getAdapter($connection);

            if (method_exists($adapter, 'modifyPosition')) {
                $result = $adapter->modifyPosition(
                    $position->executionPosition->order_id,
                    ['stopLoss' => $newSl]
                );
                return $result['success'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update stop loss on exchange', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update take profit on exchange
     */
    protected function updateTakeProfit(TradingBotPosition $position, float $newTp): bool
    {
        if (!$position->executionPosition) {
            return false;
        }

        try {
            $bot = $position->bot;
            $connection = $bot->exchangeConnection;
            $adapter = $this->getAdapter($connection);

            if (method_exists($adapter, 'modifyPosition')) {
                $result = $adapter->modifyPosition(
                    $position->executionPosition->order_id,
                    ['takeProfit' => $newTp]
                );
                return $result['success'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update take profit on exchange', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Close position on exchange
     */
    protected function closeExecutionPosition(TradingBotPosition $position): bool
    {
        if (!$position->executionPosition) {
            return false;
        }

        try {
            $bot = $position->bot;
            $connection = $bot->exchangeConnection;
            $adapter = $this->getAdapter($connection);

            if (method_exists($adapter, 'closePosition')) {
                $result = $adapter->closePosition($position->executionPosition->order_id);
                return $result['success'] ?? false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to close position on exchange', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update ExecutionPosition TP/SL
     */
    protected function updateExecutionPosition(TradingBotPosition $position): void
    {
        if (!$position->execution_position_id) {
            return;
        }

        try {
            $executionPosition = ExecutionPosition::find($position->execution_position_id);
            if ($executionPosition) {
                $executionPosition->update([
                    'sl_price' => $position->stop_loss,
                    'tp_price' => $position->take_profit,
                ]);

                // Also update on exchange
                if ($position->stop_loss) {
                    $this->updateStopLoss($position, $position->stop_loss);
                }
                if ($position->take_profit) {
                    $this->updateTakeProfit($position, $position->take_profit);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update ExecutionPosition', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate stop loss value
     */
    protected function validateStopLoss(TradingBotPosition $position, float $newSl): bool
    {
        if (in_array($position->direction, ['buy', 'long'])) {
            // For buy: SL should be below entry price
            return $newSl < $position->entry_price;
        } else {
            // For sell: SL should be above entry price
            return $newSl > $position->entry_price;
        }
    }

    /**
     * Validate take profit value
     */
    protected function validateTakeProfit(TradingBotPosition $position, float $newTp): bool
    {
        if (in_array($position->direction, ['buy', 'long'])) {
            // For buy: TP should be above entry price
            return $newTp > $position->entry_price;
        } else {
            // For sell: TP should be below entry price
            return $newTp < $position->entry_price;
        }
    }

    /**
     * Calculate profit/loss
     */
    protected function calculateProfitLoss(TradingBotPosition $position): float
    {
        if (!$position->current_price || !$position->entry_price) {
            return 0;
        }

        $priceDiff = $position->current_price - $position->entry_price;
        
        if (in_array($position->direction, ['buy', 'long'])) {
            return $priceDiff * $position->quantity;
        } else {
            return -$priceDiff * $position->quantity;
        }
    }

    /**
     * Get adapter for connection
     */
    protected function getAdapter(ExchangeConnection $connection)
    {
        if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
            return new CcxtAdapter(
                $connection->provider,
                $connection->credentials ?? []
            );
        } elseif ($connection->provider === 'metaapi') {
            return new MetaApiAdapter($connection->credentials);
        }

        // Default adapter
        return new CcxtAdapter($connection->provider, $connection->credentials ?? []);
    }
}
