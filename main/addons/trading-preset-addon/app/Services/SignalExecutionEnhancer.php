<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;

/**
 * Enhancer for SignalExecutionService to add preset functionality
 * This service can be used to enhance existing SignalExecutionService
 */
class SignalExecutionEnhancer
{
    protected PresetExecutionService $presetExecutionService;
    protected WeeklyTargetTracker $weeklyTargetTracker;

    public function __construct(
        PresetExecutionService $presetExecutionService,
        WeeklyTargetTracker $weeklyTargetTracker
    ) {
        $this->presetExecutionService = $presetExecutionService;
        $this->weeklyTargetTracker = $weeklyTargetTracker;
    }

    /**
     * Enhance canExecute check with preset validations
     */
    public function enhanceCanExecute(Signal $signal, ExecutionConnection $connection, array $baseResult): array
    {
        // If base check failed, return early
        if (!$baseResult['can_execute']) {
            return $baseResult;
        }

        // Get preset config
        $config = $this->presetExecutionService->getPresetConfig($connection, $signal);
        if (!$config) {
            return $baseResult; // No preset, use base logic
        }

        // Check trading schedule
        $scheduleCheck = $this->presetExecutionService->checkTradingSchedule($config);
        if (!$scheduleCheck['allowed']) {
            return [
                'can_execute' => false,
                'reason' => $scheduleCheck['reason'],
            ];
        }

        // Check weekly target
        $weeklyTargetCheck = $this->presetExecutionService->checkWeeklyTarget($connection, $config);
        if (!$weeklyTargetCheck['allowed']) {
            return [
                'can_execute' => false,
                'reason' => $weeklyTargetCheck['reason'],
            ];
        }

        // Check max positions
        $symbol = $this->getSymbolFromSignal($signal);
        $maxPositionsCheck = $this->presetExecutionService->checkMaxPositions($connection, $config, $symbol);
        if (!$maxPositionsCheck['allowed']) {
            return [
                'can_execute' => false,
                'reason' => $maxPositionsCheck['reason'],
            ];
        }

        return $baseResult;
    }

    /**
     * Enhance position size calculation with preset
     */
    public function enhancePositionSize(Signal $signal, ExecutionConnection $connection, array $options, float $baseQuantity): float
    {
        $config = $this->presetExecutionService->getPresetConfig($connection, $signal);
        if (!$config) {
            return $baseQuantity; // No preset, use base calculation
        }

        // Get adapter to get balance
        if (!class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            return $baseQuantity;
        }

        $connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        $adapter = $connectionService->getAdapter($connection);
        if (!$adapter) {
            return $baseQuantity;
        }

        $balance = $adapter->getBalance();
        $equity = $balance['balance'] ?? 0;

        // Calculate SL price for risk calculation
        $slPrice = $signal->sl ?? null;
        if (!$slPrice && $signal->structure_sl_price) {
            $slPrice = $signal->structure_sl_price;
        }

        // Use preset calculation
        return $this->presetExecutionService->calculatePositionSize($config, $equity, $signal->open_price, $slPrice);
    }

    /**
     * Enhance order options with preset SL/TP
     */
    public function enhanceOrderOptions(Signal $signal, ExecutionConnection $connection, array $baseOptions): array
    {
        $config = $this->presetExecutionService->getPresetConfig($connection, $signal);
        if (!$config) {
            return $baseOptions; // No preset, use base options
        }

        $orderOptions = $baseOptions;

        // Calculate SL price
        $slPrice = $this->presetExecutionService->calculateSlPrice(
            $config,
            $signal->open_price,
            $signal->direction,
            $signal->structure_sl_price ?? null
        );

        if ($slPrice) {
            $orderOptions['sl_price'] = $slPrice;
        }

        // Calculate TP prices (for multi-TP, we'll handle separately)
        $slPriceForTp = $slPrice ?? $signal->sl ?? $signal->open_price * 0.99; // Fallback
        $tpPrices = $this->presetExecutionService->calculateTpPrices(
            $config,
            $signal->open_price,
            $slPriceForTp,
            $signal->direction
        );

        // For single TP, use tp_price
        if ($config->tp_mode === 'SINGLE' && $tpPrices['tp1_price']) {
            $orderOptions['tp_price'] = $tpPrices['tp1_price'];
        } elseif ($config->tp_mode === 'MULTI') {
            // Multi-TP will be handled after position creation
            $orderOptions['tp1_price'] = $tpPrices['tp1_price'];
            $orderOptions['tp2_price'] = $tpPrices['tp2_price'];
            $orderOptions['tp3_price'] = $tpPrices['tp3_price'];
        }

        return $orderOptions;
    }

    /**
     * Enhance position creation with multi-TP data
     */
    public function enhancePositionCreation(ExecutionPosition $position, Signal $signal, ExecutionConnection $connection): void
    {
        $config = $this->presetExecutionService->getPresetConfig($connection, $signal);
        if (!$config || $config->tp_mode !== 'MULTI') {
            return; // No preset or not multi-TP
        }

        // Calculate TP prices
        $slPrice = $position->sl_price ?? $signal->sl ?? $signal->open_price * 0.99;
        $tpPrices = $this->presetExecutionService->calculateTpPrices(
            $config,
            $position->entry_price,
            $slPrice,
            $position->direction
        );

        // Update position with multi-TP data
        $position->tp1_price = $tpPrices['tp1_price'];
        $position->tp2_price = $tpPrices['tp2_price'];
        $position->tp3_price = $tpPrices['tp3_price'];
        $position->tp1_close_pct = $config->tp1_close_pct;
        $position->tp2_close_pct = $config->tp2_close_pct;
        $position->tp3_close_pct = $config->tp3_close_pct;
        $position->save();
    }

    /**
     * Get symbol from signal
     */
    protected function getSymbolFromSignal(Signal $signal): ?string
    {
        if (!$signal->pair) {
            return null;
        }

        return $signal->pair->name;
    }
}

