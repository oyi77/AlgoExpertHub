<?php

namespace App\Services;

use App\Models\Signal;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingExecutionEngine\App\Services\PositionService;
use Illuminate\Support\Facades\Log;

class SignalModificationService
{
    protected PositionService $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Detect and handle signal modifications.
     * 
     * @param Signal $signal The updated signal
     * @param array $originalData Original signal data before update
     */
    public function handleSignalModification(Signal $signal, array $originalData): void
    {
        // Only handle modifications for published signals
        if (!$signal->is_published) {
            return;
        }

        $modifications = $this->detectModifications($signal, $originalData);

        if (empty($modifications)) {
            return;
        }

        Log::info('Signal modification detected', [
            'signal_id' => $signal->id,
            'modifications' => $modifications,
        ]);

        // Update open positions
        $this->updateOpenPositions($signal, $modifications);

        // Send notifications
        $this->notifyModification($signal, $modifications);
    }

    /**
     * Detect what changed in the signal.
     */
    protected function detectModifications(Signal $signal, array $originalData): array
    {
        $modifications = [];

        // Check SL change
        if (isset($originalData['sl']) && abs($signal->sl - $originalData['sl']) > 0.0001) {
            $modifications['sl'] = [
                'old' => $originalData['sl'],
                'new' => $signal->sl,
            ];
        }

        // Check TP change
        if (isset($originalData['tp']) && abs($signal->tp - $originalData['tp']) > 0.0001) {
            $modifications['tp'] = [
                'old' => $originalData['tp'],
                'new' => $signal->tp,
            ];
        }

        // Check multiple TPs
        if ($signal->hasMultipleTps()) {
            $currentTps = $signal->openTakeProfits()->orderBy('tp_level')->get()->pluck('tp_price', 'tp_level')->toArray();
            $originalTps = $originalData['take_profits'] ?? [];
            
            if ($currentTps != $originalTps) {
                $modifications['multiple_tps'] = [
                    'old' => $originalTps,
                    'new' => $currentTps,
                ];
            }
        }

        // Check entry price change
        if (isset($originalData['open_price']) && abs($signal->open_price - $originalData['open_price']) > 0.0001) {
            $modifications['entry_price'] = [
                'old' => $originalData['open_price'],
                'new' => $signal->open_price,
            ];
        }

        return $modifications;
    }

    /**
     * Update open positions based on signal modifications.
     */
    protected function updateOpenPositions(Signal $signal, array $modifications): void
    {
        $openPositions = ExecutionPosition::open()
            ->where('signal_id', $signal->id)
            ->get();

        foreach ($openPositions as $position) {
            try {
                $updated = false;

                // Update SL if changed
                if (isset($modifications['sl'])) {
                    $newSl = $modifications['sl']['new'];
                    if ($this->shouldUpdateSl($position, $newSl)) {
                        $this->positionService->updateStopLoss($position, $newSl);
                        $updated = true;
                    }
                }

                // Update TP if changed
                if (isset($modifications['tp'])) {
                    $newTp = $modifications['tp']['new'];
                    if ($this->shouldUpdateTp($position, $newTp)) {
                        $this->positionService->updateTakeProfit($position, $newTp);
                        $updated = true;
                    }
                }

                // Handle multiple TPs
                if (isset($modifications['multiple_tps'])) {
                    // For multiple TPs, we need to update the position's TP tracking
                    // This is more complex and may require position-level TP management
                    Log::info('Multiple TP modification detected', [
                        'position_id' => $position->id,
                        'signal_id' => $signal->id,
                    ]);
                }

                if ($updated) {
                    Log::info('Position updated due to signal modification', [
                        'position_id' => $position->id,
                        'signal_id' => $signal->id,
                        'modifications' => $modifications,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to update position after signal modification', [
                    'position_id' => $position->id,
                    'signal_id' => $signal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if SL should be updated.
     */
    protected function shouldUpdateSl(ExecutionPosition $position, float $newSl): bool
    {
        // Only update if new SL is better (further from entry for buy, closer for sell)
        if ($position->direction === 'buy') {
            // For buy: new SL should be lower (better protection)
            return $newSl < $position->sl_price;
        } else {
            // For sell: new SL should be higher (better protection)
            return $newSl > $position->sl_price;
        }
    }

    /**
     * Check if TP should be updated.
     */
    protected function shouldUpdateTp(ExecutionPosition $position, float $newTp): bool
    {
        // Only update if new TP is better (further from entry)
        if ($position->direction === 'buy') {
            // For buy: new TP should be higher (better profit)
            return $newTp > $position->tp_price;
        } else {
            // For sell: new TP should be lower (better profit)
            return $newTp < $position->tp_price;
        }
    }

    /**
     * Notify users about signal modification.
     */
    protected function notifyModification(Signal $signal, array $modifications): void
    {
        // Get users who have open positions for this signal
        $openPositions = ExecutionPosition::open()
            ->where('signal_id', $signal->id)
            ->with('connection.user')
            ->get();

        foreach ($openPositions as $position) {
            if ($position->connection && $position->connection->user) {
                $user = $position->connection->user;
                
                // Create notification
                $user->notify(new \App\Notifications\SignalModifiedNotification($signal, $modifications));
            }
        }
    }
}
