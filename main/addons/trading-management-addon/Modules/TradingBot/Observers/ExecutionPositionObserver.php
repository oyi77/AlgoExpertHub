<?php

namespace Addons\TradingManagement\Modules\TradingBot\Observers;

use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Illuminate\Support\Facades\Log;

/**
 * ExecutionPositionObserver
 * 
 * Syncs TradingBotPosition when ExecutionPosition closes
 */
class ExecutionPositionObserver
{
    /**
     * Handle the ExecutionPosition "updated" event.
     */
    public function updated(ExecutionPosition $position): void
    {
        // Only process if position was just closed
        if ($position->status !== 'closed' || !$position->wasChanged('status')) {
            return;
        }

        try {
            // Find linked TradingBotPosition
            $botPosition = TradingBotPosition::where('execution_position_id', $position->id)
                ->where('status', 'open')
                ->first();

            if ($botPosition) {
                // Calculate P/L from ExecutionPosition
                $profitLoss = $position->pnl ?? 0;

                // Map closed_reason
                $closeReason = $this->mapCloseReason($position->closed_reason);

                // Update TradingBotPosition
                $botPosition->update([
                    'status' => 'closed',
                    'profit_loss' => $profitLoss,
                    'close_reason' => $closeReason,
                    'closed_at' => $position->closed_at ?? now(),
                    'current_price' => $position->current_price ?? $botPosition->current_price,
                ]);

                Log::info('TradingBotPosition synced with ExecutionPosition closure', [
                    'bot_position_id' => $botPosition->id,
                    'execution_position_id' => $position->id,
                    'reason' => $closeReason,
                    'profit_loss' => $profitLoss,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync TradingBotPosition on ExecutionPosition close', [
                'execution_position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Map ExecutionPosition closed_reason to TradingBotPosition close_reason
     */
    protected function mapCloseReason(?string $executionReason): string
    {
        $mapping = [
            'tp' => 'take_profit_hit',
            'sl' => 'stop_loss_hit',
            'manual' => 'manual_close',
            'liquidation' => 'liquidation',
        ];

        return $mapping[$executionReason] ?? 'unknown';
    }
}

