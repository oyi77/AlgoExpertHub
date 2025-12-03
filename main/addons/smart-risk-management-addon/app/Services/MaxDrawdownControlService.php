<?php

namespace Addons\SmartRiskManagement\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Support\Facades\Log;

class MaxDrawdownControlService
{
    /**
     * Check drawdown for a connection
     * 
     * @param ExecutionConnection $connection Execution connection
     * @return array ['drawdown_percent' => float, 'exceeds_threshold' => bool, 'floating_loss' => float]
     */
    public function checkDrawdown(ExecutionConnection $connection): array
    {
        try {
            $threshold = config('srm.drawdown_threshold', 20.0); // Default 20%
            
            // Get current equity from adapter
            $adapter = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)
                ->getAdapter($connection);
            
            if (!$adapter) {
                return [
                    'drawdown_percent' => 0,
                    'exceeds_threshold' => false,
                    'floating_loss' => 0,
                ];
            }
            
            $balanceData = $adapter->getBalance();
            $currentEquity = $balanceData['equity'] ?? $balanceData['balance'] ?? 0;
            $initialBalance = $connection->initial_balance ?? $currentEquity;
            
            if ($initialBalance <= 0) {
                return [
                    'drawdown_percent' => 0,
                    'exceeds_threshold' => false,
                    'floating_loss' => 0,
                ];
            }
            
            // Calculate floating loss
            $floatingLoss = $initialBalance - $currentEquity;
            $drawdownPercent = ($floatingLoss / $initialBalance) * 100;
            
            return [
                'drawdown_percent' => round($drawdownPercent, 2),
                'exceeds_threshold' => $drawdownPercent >= $threshold,
                'floating_loss' => $floatingLoss,
                'current_equity' => $currentEquity,
                'initial_balance' => $initialBalance,
                'threshold' => $threshold,
            ];
        } catch (\Exception $e) {
            Log::error("MaxDrawdownControlService: Failed to check drawdown", [
                'connection_id' => $connection->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'drawdown_percent' => 0,
                'exceeds_threshold' => false,
                'floating_loss' => 0,
            ];
        }
    }

    /**
     * Trigger emergency stop for a connection
     * 
     * @param ExecutionConnection $connection Execution connection
     * @param string $reason Reason for emergency stop
     * @return void
     */
    public function triggerEmergencyStop(ExecutionConnection $connection, string $reason): void
    {
        try {
            // Mark connection as emergency stopped
            $settings = $connection->settings ?? [];
            $settings['srm_emergency_stop'] = true;
            $settings['srm_emergency_stop_reason'] = $reason;
            $settings['srm_emergency_stop_at'] = now()->toDateTimeString();
            
            $connection->update([
                'settings' => $settings,
                'is_active' => false,
            ]);
            
            // Close all open positions
            $this->closeAllPositions($connection);
            
            // Send notification
            $this->sendEmergencyStopNotification($connection, $reason);
            
            Log::warning("MaxDrawdownControlService: Emergency stop triggered", [
                'connection_id' => $connection->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error("MaxDrawdownControlService: Failed to trigger emergency stop", [
                'connection_id' => $connection->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Close all open positions for a connection
     * 
     * @param ExecutionConnection $connection Execution connection
     * @return void
     */
    public function closeAllPositions(ExecutionConnection $connection): void
    {
        try {
            $openPositions = ExecutionPosition::where('connection_id', $connection->id)
                ->where('status', 'open')
                ->get();
            
            $adapter = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)
                ->getAdapter($connection);
            
            if (!$adapter) {
                Log::warning("MaxDrawdownControlService: Cannot close positions - no adapter", [
                    'connection_id' => $connection->id,
                ]);
                return;
            }
            
            foreach ($openPositions as $position) {
                try {
                    // Close position via adapter
                    $result = $adapter->closePosition($position->order_id);
                    
                    if ($result['success']) {
                        $position->close('emergency_stop', $result['price'] ?? $position->current_price);
                    } else {
                        Log::warning("MaxDrawdownControlService: Failed to close position", [
                            'position_id' => $position->id,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("MaxDrawdownControlService: Exception closing position", [
                        'position_id' => $position->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("MaxDrawdownControlService: Failed to close all positions", [
                'connection_id' => $connection->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send emergency stop notification
     */
    protected function sendEmergencyStopNotification(ExecutionConnection $connection, string $reason): void
    {
        try {
            if (class_exists(\Addons\TradingExecutionEngine\App\Services\NotificationService::class)) {
                $notificationService = app(\Addons\TradingExecutionEngine\App\Services\NotificationService::class);
                
                $notificationService->notifyError(
                    $connection,
                    null,
                    'emergency_stop',
                    "Emergency stop triggered: {$reason}"
                );
            }
        } catch (\Exception $e) {
            Log::error("MaxDrawdownControlService: Failed to send notification", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

