<?php

namespace Addons\CopyTrading\App\Services;

use Addons\CopyTrading\App\Models\CopyTradingExecution;
use Addons\CopyTrading\App\Models\CopyTradingSetting;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Addons\TradingExecutionEngine\App\Services\SignalExecutionService;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

class TradeCopyService
{
    protected ConnectionService $connectionService;
    protected SignalExecutionService $signalExecutionService;

    public function __construct(
        ConnectionService $connectionService,
        SignalExecutionService $signalExecutionService
    ) {
        $this->connectionService = $connectionService;
        $this->signalExecutionService = $signalExecutionService;
    }

    /**
     * Copy a trader's position to all followers.
     */
    public function copyPosition(ExecutionPosition $traderPosition): void
    {
        $connection = $traderPosition->connection;
        
        // Check if trader has copy trading enabled (user or admin)
        $traderSetting = null;
        if ($connection->is_admin_owned && $connection->admin_id) {
            $traderSetting = CopyTradingSetting::byAdmin($connection->admin_id)
                ->enabled()
                ->first();
        } elseif ($connection->user_id) {
            $traderSetting = CopyTradingSetting::byUser($connection->user_id)
                ->enabled()
                ->first();
        }

        if (!$traderSetting) {
            return;
        }

        // Check if this trade type is allowed
        $isManualTrade = $traderPosition->signal_id === null;
        if ($isManualTrade && !$traderSetting->allow_manual_trades) {
            return;
        }
        if (!$isManualTrade && !$traderSetting->allow_auto_trades) {
            return;
        }

        // Get all active subscriptions
        // For admin traders, we need to handle differently since subscriptions use user_id
        // For now, we'll get subscriptions by checking the trader's connection
        $traderId = $connection->is_admin_owned ? null : $connection->user_id;
        
        // Note: Admin traders will need a different approach for subscriptions
        // For now, we'll only support user traders in subscriptions
        if (!$traderId) {
            return; // Skip admin traders for now - can be enhanced later
        }
        
        $subscriptions = CopyTradingSubscription::byTrader($traderId)
            ->active()
            ->with(['follower', 'connection'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->copyToFollower($traderPosition, $subscription);
            } catch (\Exception $e) {
                Log::error("Failed to copy trade to follower", [
                    'trader_position_id' => $traderPosition->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Copy position to a specific follower.
     */
    protected function copyToFollower(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): void
    {
        // Validate follower connection
        $followerConnection = $subscription->connection;
        if (!$followerConnection || !$followerConnection->isActive()) {
            $this->createFailedExecution($traderPosition, $subscription, 'Follower connection is not active');
            return;
        }

        // Calculate copied quantity
        $calculation = $this->calculateCopiedQuantity($traderPosition, $subscription);
        
        if ($calculation['quantity'] <= 0) {
            $this->createFailedExecution($traderPosition, $subscription, 'Calculated quantity is zero or invalid');
            return;
        }

        // Apply SRM logic (Smart Risk Management Addon)
        $srmAdjustments = [];
        if (class_exists(\Addons\SmartRiskManagement\App\Services\SrmIntegrationService::class)) {
            try {
                $srmService = app(\Addons\SmartRiskManagement\App\Services\SrmIntegrationService::class);
                
                // Get trader's signal provider ID
                $traderId = $traderPosition->connection->is_admin_owned ? null : $traderPosition->connection->user_id;
                
                if ($traderId && $traderPosition->signal) {
                    // Get follower connection adapter for equity and current price
                    $followerAdapter = $this->connectionService->getAdapter($followerConnection);
                    $followerBalance = $followerAdapter ? $followerAdapter->getBalance() : null;
                    $equity = $followerBalance['equity'] ?? $followerBalance['balance'] ?? 10000;
                    
                    // Get current price for slippage check
                    $symbol = $traderPosition->symbol ?? 'EURUSD';
                    $ticker = $followerAdapter ? $followerAdapter->getTicker($symbol) : null;
                    $currentPrice = $ticker['last'] ?? $traderPosition->entry_price;
                    
                    // Prepare options for SRM
                    $srmOptions = [
                        'equity' => $equity,
                        'risk_tolerance' => $subscription->risk_multiplier ?? 1.0,
                        'current_price' => $currentPrice,
                        'base_quantity' => $calculation['quantity'],
                        'min_lot' => $subscription->getMinQuantity() ?? 0.01,
                        'max_lot' => $subscription->getMaxQuantity(),
                        'max_position_size' => $subscription->max_position_size,
                    ];
                    
                    // Apply SRM before execution
                    $srmResult = $srmService->applySrmBeforeExecution(
                        $traderPosition->signal,
                        $followerConnection,
                        $srmOptions
                    );
                    
                    if (!$srmResult['proceed']) {
                        // SRM rejected the signal
                        $this->createFailedExecution($traderPosition, $subscription, 
                            'SRM rejected: ' . ($srmResult['reason'] ?? 'Signal quality too low'));
                        return;
                    }
                    
                    // Apply SRM adjustments
                    if (isset($srmResult['options']['quantity'])) {
                        $calculation['quantity'] = $srmResult['options']['quantity'];
                    }
                    if (isset($srmResult['options']['sl_price'])) {
                        $calculation['srm_adjusted_sl'] = $srmResult['options']['sl_price'];
                    }
                    
                    $srmAdjustments = $srmResult['adjustments'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning("TradeCopyService: SRM integration failed, proceeding without SRM", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create execution record
        $traderId = $traderPosition->connection->is_admin_owned ? null : $traderPosition->connection->user_id;
        
        $execution = CopyTradingExecution::create([
            'trader_position_id' => $traderPosition->id,
            'trader_id' => $traderId,
            'follower_id' => $subscription->follower_id,
            'subscription_id' => $subscription->id,
            'follower_connection_id' => $followerConnection->id,
            'status' => 'pending',
            'original_quantity' => $traderPosition->quantity,
            'copied_quantity' => $calculation['quantity'],
            'risk_multiplier_used' => $calculation['risk_multiplier'] ?? null,
            'calculation_details' => json_encode(array_merge($calculation['details'] ?? [], [
                'srm_adjustments' => $srmAdjustments,
                'srm_adjusted_sl' => $calculation['srm_adjusted_sl'] ?? null,
            ])),
        ]);

        // Execute the trade
        try {
            // Prepare execution options with SRM adjustments
            $executionOptions = ['quantity' => $calculation['quantity']];
            if (isset($calculation['srm_adjusted_sl'])) {
                $executionOptions['sl_price'] = $calculation['srm_adjusted_sl'];
            }
            
            $result = $this->executeCopiedTrade($traderPosition, $followerConnection, $executionOptions);
            
            if ($result['success']) {
                $execution->markAsExecuted($result['position_id']);
            } else {
                $execution->markAsFailed($result['message']);
            }
        } catch (\Exception $e) {
            $execution->markAsFailed($e->getMessage());
            Log::error("Error executing copied trade", [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate copied quantity based on copy mode.
     */
    protected function calculateCopiedQuantity(ExecutionPosition $traderPosition, CopyTradingSubscription $subscription): array
    {
        $traderConnection = $traderPosition->connection;
        $followerConnection = $subscription->connection;

        // Get balances
        $traderAdapter = $this->connectionService->getAdapter($traderConnection);
        $followerAdapter = $this->connectionService->getAdapter($followerConnection);

        $traderBalance = $traderAdapter->getBalance();
        $followerBalance = $followerAdapter->getBalance();

        $traderBalanceAmount = $traderBalance['balance'] ?? 0;
        $followerBalanceAmount = $followerBalance['balance'] ?? 0;

        $details = [
            'trader_balance' => $traderBalanceAmount,
            'follower_balance' => $followerBalanceAmount,
            'trader_position_value' => $traderPosition->quantity * $traderPosition->entry_price,
        ];

        if ($subscription->isEasyMode()) {
            // Easy Copy: Match trader's % of balance
            $traderPositionValue = $traderPosition->quantity * $traderPosition->entry_price;
            $traderPositionPercentage = $traderBalanceAmount > 0 
                ? ($traderPositionValue / $traderBalanceAmount) * 100 
                : 0;

            // Apply same percentage to follower's balance
            $followerPositionValue = ($followerBalanceAmount * $traderPositionPercentage) / 100;

            // Apply risk multiplier
            $riskMultiplier = $subscription->risk_multiplier ?? 1.0;
            $followerPositionValue = $followerPositionValue * $riskMultiplier;

            // Convert to quantity
            $quantity = $followerPositionValue / $traderPosition->entry_price;

            $details['mode'] = 'easy';
            $details['trader_position_percentage'] = $traderPositionPercentage;
            $details['follower_position_value'] = $followerPositionValue;
            $details['risk_multiplier'] = $riskMultiplier;

        } else {
            // Advanced Copy
            $method = $subscription->getCopyMethod();
            $details['mode'] = 'advanced';
            $details['method'] = $method;

            if ($method === 'percentage') {
                $percentage = $subscription->getCopyPercentage() ?? 1.0;
                $followerPositionValue = ($followerBalanceAmount * $percentage) / 100;
                $quantity = $followerPositionValue / $traderPosition->entry_price;
                
                $details['percentage'] = $percentage;
                $details['follower_position_value'] = $followerPositionValue;

            } elseif ($method === 'fixed_quantity') {
                $quantity = $subscription->getFixedQuantity() ?? 0;
                $details['fixed_quantity'] = $quantity;

            } else {
                // Default to fixed quantity if method not set
                $quantity = 0.01;
                $details['method'] = 'default';
            }

            // Apply min/max constraints
            $minQuantity = $subscription->getMinQuantity();
            $maxQuantity = $subscription->getMaxQuantity();

            if ($minQuantity !== null && $quantity < $minQuantity) {
                $quantity = $minQuantity;
                $details['min_quantity_applied'] = true;
            }

            if ($maxQuantity !== null && $quantity > $maxQuantity) {
                $quantity = $maxQuantity;
                $details['max_quantity_applied'] = true;
            }
        }

        // Apply max position size limit
        if ($subscription->max_position_size) {
            $maxValue = $subscription->max_position_size;
            $currentValue = $quantity * $traderPosition->entry_price;
            
            if ($currentValue > $maxValue) {
                $quantity = $maxValue / $traderPosition->entry_price;
                $details['max_position_size_applied'] = true;
            }
        }

        return [
            'quantity' => max(0, $quantity),
            'risk_multiplier' => $subscription->risk_multiplier ?? null,
            'details' => $details,
        ];
    }

    /**
     * Execute the copied trade.
     */
    protected function executeCopiedTrade(ExecutionPosition $traderPosition, ExecutionConnection $followerConnection, array $options): array
    {
        $signal = $traderPosition->signal;
        
        if (!$signal) {
            // Manual trades without signals cannot be copied via SignalExecutionService
            // In the future, we could add a direct execution method
            throw new \Exception('Cannot copy manual trades without signal. Only signal-based trades can be copied.');
        }

        // Temporarily set connection to use signal_based strategy to use our custom quantity
        $originalSettings = $followerConnection->settings ?? [];
        $tempSettings = array_merge($originalSettings, [
            'position_sizing_strategy' => 'signal_based'
        ]);
        
        $followerConnection->settings = $tempSettings;
        $followerConnection->save();

        try {
            // Execute using SignalExecutionService with custom options (quantity, SL, etc.)
            $result = $this->signalExecutionService->executeSignal(
                $signal,
                $followerConnection->id,
                $options
            );
        } finally {
            // Restore original settings
            $followerConnection->settings = $originalSettings;
            $followerConnection->save();
        }

        return $result;
    }

    /**
     * Close copied positions when trader closes position.
     */
    public function closeCopiedPositions(ExecutionPosition $traderPosition): void
    {
        $executions = CopyTradingExecution::byTraderPosition($traderPosition->id)
            ->executed()
            ->with(['followerPosition'])
            ->get();

        foreach ($executions as $execution) {
            if ($execution->followerPosition && $execution->followerPosition->isOpen()) {
                try {
                    $execution->followerPosition->close('trader_closed');
                    $execution->markAsClosed();
                } catch (\Exception $e) {
                    Log::error("Failed to close copied position", [
                        'execution_id' => $execution->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Create a failed execution record.
     */
    protected function createFailedExecution(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        string $errorMessage
    ): void {
        $traderId = $traderPosition->connection->is_admin_owned ? null : $traderPosition->connection->user_id;
        
        CopyTradingExecution::create([
            'trader_position_id' => $traderPosition->id,
            'trader_id' => $traderId,
            'follower_id' => $subscription->follower_id,
            'subscription_id' => $subscription->id,
            'follower_connection_id' => $subscription->connection_id,
            'status' => 'failed',
            'error_message' => $errorMessage,
            'original_quantity' => $traderPosition->quantity,
            'copied_quantity' => 0,
        ]);
    }
}

