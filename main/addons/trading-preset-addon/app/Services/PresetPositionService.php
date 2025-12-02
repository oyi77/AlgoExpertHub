<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Addons\TradingExecutionEngine\App\Services\PositionService;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing positions with preset features:
 * - Break-even
 * - Trailing stop
 * - Multi-TP partial closes
 * - Layering
 * - Hedging
 */
class PresetPositionService
{
    protected ?ConnectionService $connectionService;
    protected ?PositionService $positionService;

    public function __construct()
    {
        // Lazy load services if available
        if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            $this->connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        } else {
            $this->connectionService = null;
        }

        if (class_exists(\Addons\TradingExecutionEngine\App\Services\PositionService::class)) {
            $this->positionService = app(\Addons\TradingExecutionEngine\App\Services\PositionService::class);
        } else {
            $this->positionService = null;
        }
    }

    /**
     * Process break-even logic
     */
    public function processBreakEven(ExecutionPosition $position, PresetConfigurationDTO $config): void
    {
        if (!$config->be_enabled || !$config->be_trigger_rr) {
            return;
        }

        if (!$position->isOpen()) {
            return;
        }

        // Calculate current R:R
        $currentRR = $this->calculateCurrentRR($position);
        if ($currentRR < $config->be_trigger_rr) {
            return; // Not reached trigger RR yet
        }

        // Check if already at break-even
        $entryPrice = $position->entry_price;
        $bePrice = $entryPrice;
        
        if ($config->be_offset_pips) {
            $pipValue = 0.0001; // Simplified, should use proper pip calculation
            $offset = $config->be_offset_pips * $pipValue;
            
            if ($position->direction === 'buy') {
                $bePrice = $entryPrice + $offset;
            } else {
                $bePrice = $entryPrice - $offset;
            }
        }

        // Update SL to break-even if not already there
        if ($position->sl_price != $bePrice) {
            $this->updateSlPrice($position, $bePrice);
            
            Log::info("Break-even triggered", [
                'position_id' => $position->id,
                'old_sl' => $position->sl_price,
                'new_sl' => $bePrice,
                'current_rr' => $currentRR,
            ]);
        }
    }

    /**
     * Process trailing stop logic
     */
    public function processTrailingStop(ExecutionPosition $position, PresetConfigurationDTO $config): void
    {
        if (!$config->ts_enabled || !$config->ts_trigger_rr) {
            return;
        }

        if (!$position->isOpen()) {
            return;
        }

        // Check if trigger RR is reached
        $currentRR = $this->calculateCurrentRR($position);
        if ($currentRR < $config->ts_trigger_rr) {
            return; // Not reached trigger RR yet
        }

        $newSlPrice = $this->calculateTrailingStop($position, $config);
        
        if ($newSlPrice && $this->shouldUpdateSl($position, $newSlPrice)) {
            $this->updateSlPrice($position, $newSlPrice);
            
            Log::info("Trailing stop updated", [
                'position_id' => $position->id,
                'old_sl' => $position->sl_price,
                'new_sl' => $newSlPrice,
                'mode' => $config->ts_mode,
            ]);
        }
    }

    /**
     * Process multi-TP partial closes
     */
    public function processMultiTp(ExecutionPosition $position, PresetConfigurationDTO $config): void
    {
        if ($config->tp_mode !== 'MULTI') {
            return;
        }

        if (!$position->isOpen()) {
            return;
        }

        $currentPrice = $position->current_price ?? $position->entry_price;

        // Check TP1
        if ($config->tp1_enabled && $position->tp1_price && !$position->tp1_closed_at) {
            if ($this->isTpHit($position, $position->tp1_price, $currentPrice)) {
                $this->closePartialTp($position, 1, $config->tp1_close_pct);
            }
        }

        // Check TP2
        if ($config->tp2_enabled && $position->tp2_price && !$position->tp2_closed_at) {
            if ($this->isTpHit($position, $position->tp2_price, $currentPrice)) {
                $this->closePartialTp($position, 2, $config->tp2_close_pct);
            }
        }

        // Check TP3
        if ($config->tp3_enabled && $position->tp3_price && !$position->tp3_closed_at) {
            if ($this->isTpHit($position, $position->tp3_price, $currentPrice)) {
                $closePct = $config->tp3_close_pct;
                
                // If close_remaining_at_tp3, close all remaining
                if ($config->close_remaining_at_tp3) {
                    $closePct = 100; // Close all remaining
                }
                
                $this->closePartialTp($position, 3, $closePct);
            }
        }
    }

    /**
     * Calculate current R:R ratio
     */
    protected function calculateCurrentRR(ExecutionPosition $position): float
    {
        if (!$position->sl_price || !$position->entry_price) {
            return 0;
        }

        $risk = abs($position->entry_price - $position->sl_price);
        if ($risk == 0) {
            return 0;
        }

        $currentPrice = $position->current_price ?? $position->entry_price;
        $reward = abs($currentPrice - $position->entry_price);

        return $reward / $risk;
    }

    /**
     * Calculate trailing stop price
     */
    protected function calculateTrailingStop(ExecutionPosition $position, PresetConfigurationDTO $config): ?float
    {
        $currentPrice = $position->current_price ?? $position->entry_price;
        $entryPrice = $position->entry_price;

        switch ($config->ts_mode) {
            case 'STEP_PIPS':
                if (!$config->ts_step_pips) {
                    return null;
                }
                
                $pipValue = 0.0001; // Simplified
                $step = $config->ts_step_pips * $pipValue;
                
                if ($position->direction === 'buy') {
                    // For buy: SL should trail below current price
                    $newSl = $currentPrice - $step;
                    return max($newSl, $position->sl_price ?? $entryPrice); // Only move up
                } else {
                    // For sell: SL should trail above current price
                    $newSl = $currentPrice + $step;
                    return min($newSl, $position->sl_price ?? $entryPrice); // Only move down
                }

            case 'STEP_ATR':
                // ATR-based trailing stop
                if (!$config->ts_atr_period || !$config->ts_atr_multiplier) {
                    return null;
                }
                
                $advancedService = app(AdvancedTradingService::class);
                $candles = $advancedService->getPriceHistory(
                    $position->connection,
                    $position->symbol,
                    $position->signal->timeframe ?? '1h',
                    50
                );
                
                if (empty($candles)) {
                    return null; // Can't calculate without price history
                }
                
                $atr = $advancedService->calculateATR($candles, $config->ts_atr_period);
                if (!$atr) {
                    return null;
                }
                
                return $advancedService->calculateATRTrailingStop(
                    $currentPrice,
                    $atr,
                    $config->ts_atr_multiplier,
                    $position->direction,
                    $position->sl_price
                );

            case 'CHANDELIER':
                // Chandelier stop
                if (!$config->ts_atr_multiplier) {
                    return null;
                }
                
                $advancedService = app(AdvancedTradingService::class);
                $lookbackPeriod = $config->ts_atr_period ?? 22; // Default 22 periods
                $candles = $advancedService->getPriceHistory(
                    $position->connection,
                    $position->symbol,
                    $position->signal->timeframe ?? '1h',
                    max($lookbackPeriod, 50)
                );
                
                if (empty($candles)) {
                    return null; // Can't calculate without price history
                }
                
                return $advancedService->calculateChandelierStop(
                    $candles,
                    $lookbackPeriod,
                    $config->ts_atr_multiplier,
                    $position->direction
                );

            default:
                return null;
        }
    }

    /**
     * Check if SL should be updated (only move in favorable direction)
     */
    protected function shouldUpdateSl(ExecutionPosition $position, float $newSlPrice): bool
    {
        if (!$position->sl_price) {
            return true;
        }

        if ($position->direction === 'buy') {
            // For buy: SL should only move up (higher price)
            return $newSlPrice > $position->sl_price;
        } else {
            // For sell: SL should only move down (lower price)
            return $newSlPrice < $position->sl_price;
        }
    }

    /**
     * Update SL price on exchange and in database
     */
    protected function updateSlPrice(ExecutionPosition $position, float $newSlPrice): void
    {
        if (!$this->connectionService) {
            return;
        }

        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);
            
            if ($adapter && method_exists($adapter, 'updateStopLoss')) {
                $adapter->updateStopLoss($position->order_id, $newSlPrice);
            }

            $position->sl_price = $newSlPrice;
            $position->save();
        } catch (\Exception $e) {
            Log::error("Failed to update SL price", [
                'position_id' => $position->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if TP is hit
     */
    protected function isTpHit(ExecutionPosition $position, float $tpPrice, float $currentPrice): bool
    {
        if ($position->direction === 'buy') {
            return $currentPrice >= $tpPrice;
        } else {
            return $currentPrice <= $tpPrice;
        }
    }

    /**
     * Close partial TP
     */
    protected function closePartialTp(ExecutionPosition $position, int $tpNumber, float $closePct): void
    {
        if (!$this->connectionService) {
            return;
        }

        try {
            $connection = $position->connection;
            $adapter = $this->connectionService->getAdapter($connection);
            
            if (!$adapter) {
                return;
            }

            // Calculate quantity to close
            $remainingQuantity = $this->getRemainingQuantity($position);
            $closeQuantity = ($remainingQuantity * $closePct) / 100;

            if ($closeQuantity <= 0) {
                return;
            }

            // Close partial position on exchange
            $result = $adapter->closePartialPosition($position->order_id, $closeQuantity);
            
            if ($result['success']) {
                // Update position record
                $this->updatePartialClose($position, $tpNumber, $closeQuantity, $result['close_price'] ?? $position->current_price);
                
                Log::info("Partial TP closed", [
                    'position_id' => $position->id,
                    'tp_number' => $tpNumber,
                    'close_pct' => $closePct,
                    'close_quantity' => $closeQuantity,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to close partial TP", [
                'position_id' => $position->id,
                'tp_number' => $tpNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get remaining quantity (not yet closed)
     */
    protected function getRemainingQuantity(ExecutionPosition $position): float
    {
        $closedQty = 0;
        $closedQty += $position->tp1_closed_qty ?? 0;
        $closedQty += $position->tp2_closed_qty ?? 0;
        $closedQty += $position->tp3_closed_qty ?? 0;

        return max(0, $position->quantity - $closedQty);
    }

    /**
     * Update position after partial close
     */
    protected function updatePartialClose(ExecutionPosition $position, int $tpNumber, float $closeQuantity, float $closePrice): void
    {
        $fieldQty = "tp{$tpNumber}_closed_qty";
        $fieldAt = "tp{$tpNumber}_closed_at";

        $position->$fieldQty = ($position->$fieldQty ?? 0) + $closeQuantity;
        $position->$fieldAt = now();
        $position->save();

        // If all quantity is closed, close the position
        $remainingQty = $this->getRemainingQuantity($position);
        if ($remainingQty <= 0) {
            $position->close('tp', $closePrice);
        }
    }

    /**
     * Monitor all positions with preset features
     */
    public function monitorPositions(): void
    {
        if (!$this->positionService) {
            return;
        }

        $openPositions = ExecutionPosition::open()->get();

        foreach ($openPositions as $position) {
            try {
                $config = $this->getPresetConfigForPosition($position);
                if (!$config) {
                    continue; // No preset, skip
                }

                // Process break-even
                $this->processBreakEven($position, $config);

                // Process trailing stop
                $this->processTrailingStop($position, $config);

                // Process multi-TP
                $this->processMultiTp($position, $config);
            } catch (\Exception $e) {
                Log::error("Failed to monitor position with preset", [
                    'position_id' => $position->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get preset config for position
     */
    protected function getPresetConfigForPosition(ExecutionPosition $position): ?PresetConfigurationDTO
    {
        $connection = $position->connection;
        if (!$connection) {
            return null;
        }

        $presetResolver = app(PresetResolverService::class);
        $presetApplicator = app(PresetApplicatorService::class);

        $user = $connection->user;
        $signal = $position->signal;
        
        $preset = $presetResolver->resolveForSignal($connection, $user, $signal);
        
        if (!$preset) {
            return null;
        }

        $connectionSettings = $connection->settings ?? [];
        return $presetApplicator->applyAsDTO($preset, $connectionSettings);
    }
}

