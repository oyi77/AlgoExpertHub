<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Services;

use Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription;
use Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter;
use Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TradeCopyService
 * 
 * Handles copy trading execution when trader opens position
 */
class TradeCopyService
{
    /**
     * Copy trade to all active subscribers
     * 
     * @param ExecutionPosition $traderPosition
     * @return void
     */
    public function copyToSubscribers(ExecutionPosition $traderPosition): void
    {
        try {
            // Get trader user ID from connection
            $traderId = $traderPosition->connection->user_id ?? null;
            if (!$traderId) {
                return; // Not a user connection, skip copy trading
            }

            // Get all active subscriptions for this trader
            $subscriptions = CopyTradingSubscription::where('trader_id', $traderId)
                ->where('is_active', true)
                ->with(['follower', 'executionConnection'])
                ->get();

            if ($subscriptions->isEmpty()) {
                return; // No active subscriptions
            }

            Log::info('Copying trade to subscribers', [
                'trader_position_id' => $traderPosition->id,
                'trader_id' => $traderId,
                'subscriptions_count' => $subscriptions->count(),
            ]);

            foreach ($subscriptions as $subscription) {
                try {
                    $this->executeCopiedTrade($traderPosition, $subscription);
                } catch (\Exception $e) {
                    Log::error('Failed to copy trade for subscription', [
                        'subscription_id' => $subscription->id,
                        'trader_position_id' => $traderPosition->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to copy trade to subscribers', [
                'trader_position_id' => $traderPosition->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute copied trade for a subscription
     * 
     * @param ExecutionPosition $traderPosition
     * @param CopyTradingSubscription $subscription
     * @return void
     */
    protected function executeCopiedTrade(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): void
    {
        // Validate follower connection
        $followerConnection = $subscription->executionConnection;
        if (!$followerConnection || !$followerConnection->is_active) {
            Log::warning('Follower connection not active', [
                'subscription_id' => $subscription->id,
                'connection_id' => $subscription->connection_id,
            ]);
            return;
        }

        // Calculate copied quantity
        $copiedQuantity = $this->calculateCopiedQuantity($traderPosition, $subscription);
        
        if ($copiedQuantity <= 0) {
            Log::warning('Calculated copied quantity is zero or invalid', [
                'subscription_id' => $subscription->id,
                'copied_quantity' => $copiedQuantity,
            ]);
            return;
        }

        // Create copy trading execution record
        $execution = CopyTradingExecution::create([
            'subscription_id' => $subscription->id,
            'trader_execution_log_id' => $traderPosition->execution_log_id,
            'original_lot_size' => $traderPosition->quantity,
            'copied_lot_size' => $copiedQuantity,
            'multiplier_applied' => $subscription->risk_multiplier ?? 1.0,
            'status' => 'pending',
        ]);

        // Execute trade on follower's connection
        $executionData = [
            'connection_id' => $followerConnection->id,
            'symbol' => $traderPosition->symbol,
            'direction' => $traderPosition->direction,
            'quantity' => $copiedQuantity,
            'stop_loss' => $traderPosition->sl_price,
            'take_profit' => $traderPosition->tp_price,
            'entry_price' => $traderPosition->entry_price,
            'signal_id' => $traderPosition->signal_id,
            'copy_trading_execution_id' => $execution->id,
        ];

        // Dispatch execution job
        ExecutionJob::dispatch($executionData);

        Log::info('Copied trade execution dispatched', [
            'execution_id' => $execution->id,
            'subscription_id' => $subscription->id,
            'copied_quantity' => $copiedQuantity,
        ]);
    }

    /**
     * Calculate copied quantity based on copy mode
     * 
     * @param ExecutionPosition $traderPosition
     * @param CopyTradingSubscription $subscription
     * @return float
     */
    public function calculateCopiedQuantity(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): float
    {
        $copyMode = $subscription->copy_mode ?? 'easy';
        $riskMultiplier = $subscription->risk_multiplier ?? 1.0;
        $traderQuantity = $traderPosition->quantity;

        // Get balances
        $traderConnection = $traderPosition->connection;
        $followerConnection = $subscription->executionConnection;

        $traderBalance = $this->getBalance($traderConnection);
        $followerBalance = $this->getBalance($followerConnection);

        if ($traderBalance <= 0 || $followerBalance <= 0) {
            return 0;
        }

        $baseQuantity = 0;

        // Calculate base quantity based on copy mode
        if ($copyMode === 'easy') {
            // Simple proportional based on risk multiplier
            $balanceRatio = $followerBalance / $traderBalance;
            $baseQuantity = $traderQuantity * $balanceRatio;
        } elseif ($copyMode === 'advanced') {
            // Advanced mode uses copy_settings
            $settings = $subscription->copy_settings ?? [];
            $method = $settings['method'] ?? 'proportional';

            switch ($method) {
                case 'proportional':
                    $balanceRatio = $followerBalance / $traderBalance;
                    $baseQuantity = $traderQuantity * $balanceRatio;
                    break;

                case 'fixed_lot':
                    $baseQuantity = $settings['fixed_quantity'] ?? 0.01;
                    break;

                case 'risk_percent':
                    $riskPercent = $settings['risk_percent'] ?? 1;
                    $riskAmount = ($followerBalance * $riskPercent) / 100;
                    // Calculate quantity from risk amount
                    $priceDiff = abs($traderPosition->entry_price - ($traderPosition->sl_price ?? $traderPosition->entry_price));
                    if ($priceDiff > 0) {
                        $baseQuantity = $riskAmount / $priceDiff;
                    }
                    break;

                default:
                    $baseQuantity = $traderQuantity * ($followerBalance / $traderBalance);
            }

            // Apply min/max constraints
            if (isset($settings['min_quantity'])) {
                $baseQuantity = max($baseQuantity, $settings['min_quantity']);
            }
            if (isset($settings['max_quantity'])) {
                $baseQuantity = min($baseQuantity, $settings['max_quantity']);
            }
        }

        // Apply risk multiplier
        $finalQuantity = $baseQuantity * $riskMultiplier;

        // Apply max position size if set
        if ($subscription->max_position_size) {
            $finalQuantity = min($finalQuantity, $subscription->max_position_size);
        }

        return max(0, $finalQuantity);
    }

    /**
     * Get balance from connection
     */
    protected function getBalance($connection): float
    {
        try {
            $adapter = $this->getAdapter($connection);
            
            if (method_exists($adapter, 'fetchBalance')) {
                $balance = $adapter->fetchBalance();
                return (float) ($balance['total'] ?? $balance['balance'] ?? 0);
            } elseif (method_exists($adapter, 'getAccountInfo')) {
                $accountInfo = $adapter->getAccountInfo();
                return (float) ($accountInfo['balance'] ?? 0);
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning('Failed to get balance for copy trading', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get adapter for connection (handles both ExchangeConnection and ExecutionConnection)
     */
    protected function getAdapter($connection)
    {
        // Handle ExchangeConnection (unified model)
        if ($connection instanceof ExchangeConnection) {
            if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
                return new CcxtAdapter($connection->credentials, $connection->provider);
            } elseif ($connection->provider === 'metaapi') {
                return new MetaApiAdapter($connection->credentials);
            }
            return new CcxtAdapter($connection->credentials, $connection->provider ?? 'binance');
        }

        // Handle ExecutionConnection (legacy model)
        if ($connection instanceof ExecutionConnection) {
            $provider = $connection->exchange_name ?? 'binance';
            $type = $connection->type ?? 'crypto';
            
            if ($type === 'fx' || strpos(strtolower($provider), 'mt') !== false) {
                return new MetaApiAdapter($connection->credentials);
            }
            return new CcxtAdapter($connection->credentials, $provider);
        }

        // Fallback
        return new CcxtAdapter($connection->credentials ?? [], 'binance');
    }

    /**
     * Close copied positions when trader closes position
     * 
     * @param ExecutionPosition $traderPosition
     * @return void
     */
    public function closeCopiedPositions(ExecutionPosition $traderPosition): void
    {
        try {
            // Get all copy executions for this trader position
            $executions = CopyTradingExecution::where('trader_execution_log_id', $traderPosition->execution_log_id)
                ->where('status', 'executed')
                ->with(['followerExecution'])
                ->get();

            foreach ($executions as $execution) {
                try {
                    // Get follower's position
                    if ($execution->followerExecution) {
                        $followerPosition = ExecutionPosition::where('execution_log_id', $execution->follower_execution_log_id)
                            ->where('status', 'open')
                            ->first();

                        if ($followerPosition) {
                            // Close follower position
                            $this->closeFollowerPosition($followerPosition);
                            
                            $execution->update(['status' => 'closed']);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to close copied position', [
                        'execution_id' => $execution->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to close copied positions', [
                'trader_position_id' => $traderPosition->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Close follower position
     */
    protected function closeFollowerPosition(ExecutionPosition $position): void
    {
        try {
            $connection = $position->connection;
            $adapter = $this->getAdapter($connection);

            if (method_exists($adapter, 'closePosition')) {
                $result = $adapter->closePosition($position->order_id);
                if ($result['success']) {
                    $position->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                        'closed_reason' => 'trader_closed',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to close follower position', [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
