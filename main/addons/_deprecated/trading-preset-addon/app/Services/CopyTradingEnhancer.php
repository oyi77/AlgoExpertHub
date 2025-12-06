<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use Addons\TradingExecutionEngine\App\Services\ConnectionService;
use Illuminate\Support\Facades\Log;

/**
 * Enhancer for Copy Trading to add preset functionality
 */
class CopyTradingEnhancer
{
    protected PresetResolverService $presetResolver;
    protected PresetApplicatorService $presetApplicator;
    protected PresetExecutionService $presetExecutionService;
    protected ?ConnectionService $connectionService;

    public function __construct(
        PresetResolverService $presetResolver,
        PresetApplicatorService $presetApplicator,
        PresetExecutionService $presetExecutionService
    ) {
        $this->presetResolver = $presetResolver;
        $this->presetApplicator = $presetApplicator;
        $this->presetExecutionService = $presetExecutionService;

        if (class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            $this->connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
        } else {
            $this->connectionService = null;
        }
    }

    /**
     * Enhance copied quantity calculation with preset
     * If preset is assigned to subscription, use preset position sizing
     * Otherwise, fall back to existing logic
     */
    public function enhanceCopiedQuantity(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        array $baseCalculation
    ): array {
        // Get preset for subscription
        $preset = $this->presetResolver->resolveForCopyTrading(
            $subscription,
            $subscription->connection,
            $subscription->follower
        );

        if (!$preset) {
            return $baseCalculation; // No preset, use base calculation
        }

        // Get preset config
        $followerConnection = $subscription->connection;
        $connectionSettings = $followerConnection->settings ?? [];
        $config = $this->presetApplicator->applyAsDTO($preset, $connectionSettings);

        // Check if preset position sizing should be used
        // For copy trading, we can use preset RISK_PERCENT mode
        if ($config->position_size_mode === 'RISK_PERCENT') {
            return $this->calculateQuantityWithPreset($traderPosition, $subscription, $config, $baseCalculation);
        }

        // For FIXED mode, use preset fixed lot
        if ($config->position_size_mode === 'FIXED') {
            $quantity = $config->fixed_lot ?? 0.01;
            
            // Apply risk multiplier if in easy mode
            if ($subscription->isEasyMode() && $subscription->risk_multiplier) {
                $quantity = $quantity * $subscription->risk_multiplier;
            }

            // Apply max position size limit
            if ($subscription->max_position_size) {
                $maxValue = $subscription->max_position_size;
                $currentValue = $quantity * $traderPosition->entry_price;
                
                if ($currentValue > $maxValue) {
                    $quantity = $maxValue / $traderPosition->entry_price;
                }
            }

            return [
                'quantity' => max(0, $quantity),
                'risk_multiplier' => $subscription->risk_multiplier ?? null,
                'details' => array_merge($baseCalculation['details'] ?? [], [
                    'preset_id' => $preset->id,
                    'preset_name' => $preset->name,
                    'preset_mode' => 'FIXED',
                    'preset_fixed_lot' => $config->fixed_lot,
                ]),
            ];
        }

        // Fallback to base calculation
        return $baseCalculation;
    }

    /**
     * Calculate quantity using preset RISK_PERCENT mode
     */
    protected function calculateQuantityWithPreset(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        PresetConfigurationDTO $config,
        array $baseCalculation
    ): array {
        if (!$this->connectionService) {
            return $baseCalculation;
        }

        $followerConnection = $subscription->connection;
        $adapter = $this->connectionService->getAdapter($followerConnection);
        if (!$adapter) {
            return $baseCalculation;
        }

        $balance = $adapter->getBalance();
        $equity = $balance['balance'] ?? 0;

        // Get preset for details
        $preset = $this->presetResolver->resolveForCopyTrading(
            $subscription,
            $followerConnection,
            $subscription->follower
        );

        // Calculate quantity based on preset risk percentage
        $slPrice = $traderPosition->sl_price ?? null;
        $quantity = $this->presetApplicator->calculatePositionSize(
            $config,
            $equity,
            $traderPosition->entry_price,
            $slPrice
        );

        // Apply risk multiplier if in easy mode
        if ($subscription->isEasyMode() && $subscription->risk_multiplier) {
            $quantity = $quantity * $subscription->risk_multiplier;
        }

        // Apply max position size limit
        if ($subscription->max_position_size) {
            $maxValue = $subscription->max_position_size;
            $currentValue = $quantity * $traderPosition->entry_price;
            
            if ($currentValue > $maxValue) {
                $quantity = $maxValue / $traderPosition->entry_price;
            }
        }

        // Apply min/max constraints from subscription (advanced mode)
        if ($subscription->isAdvancedMode()) {
            $minQuantity = $subscription->getMinQuantity();
            $maxQuantity = $subscription->getMaxQuantity();

            if ($minQuantity !== null && $quantity < $minQuantity) {
                $quantity = $minQuantity;
            }

            if ($maxQuantity !== null && $quantity > $maxQuantity) {
                $quantity = $maxQuantity;
            }
        }

        return [
            'quantity' => max(0, $quantity),
            'risk_multiplier' => $subscription->risk_multiplier ?? null,
            'details' => array_merge($baseCalculation['details'] ?? [], [
                'preset_id' => $preset?->id,
                'preset_name' => $preset?->name,
                'preset_mode' => 'RISK_PERCENT',
                'preset_risk_pct' => $config->risk_per_trade_pct,
                'follower_equity' => $equity,
            ]),
        ];
    }

    /**
     * Enhance execution with preset checks
     */
    public function enhanceCopyExecution(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        ExecutionConnection $followerConnection
    ): array {
        // Get preset config
        $preset = $this->presetResolver->resolveForCopyTrading(
            $subscription,
            $followerConnection,
            $subscription->follower
        );

        if (!$preset) {
            return ['allowed' => true, 'reason' => null];
        }

        $connectionSettings = $followerConnection->settings ?? [];
        $config = $this->presetApplicator->applyAsDTO($preset, $connectionSettings);

        // Check trading schedule
        $scheduleCheck = $this->presetExecutionService->checkTradingSchedule($config);
        if (!$scheduleCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $scheduleCheck['reason'],
            ];
        }

        // Check weekly target
        $weeklyTargetCheck = $this->presetExecutionService->checkWeeklyTarget($followerConnection, $config);
        if (!$weeklyTargetCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $weeklyTargetCheck['reason'],
            ];
        }

        // Check max positions
        $symbol = $traderPosition->symbol;
        $maxPositionsCheck = $this->presetExecutionService->checkMaxPositions($followerConnection, $config, $symbol);
        if (!$maxPositionsCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $maxPositionsCheck['reason'],
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Enhance order options for copied trade
     */
    public function enhanceCopiedOrderOptions(
        ExecutionPosition $traderPosition,
        CopyTradingSubscription $subscription,
        ExecutionConnection $followerConnection,
        array $baseOptions
    ): array {
        $preset = $this->presetResolver->resolveForCopyTrading(
            $subscription,
            $followerConnection,
            $subscription->follower
        );

        if (!$preset) {
            return $baseOptions;
        }

        $connectionSettings = $followerConnection->settings ?? [];
        $config = $this->presetApplicator->applyAsDTO($preset, $connectionSettings);

        $orderOptions = $baseOptions;

        // Calculate SL price from preset (if not already set)
        if (empty($orderOptions['sl_price'])) {
            $structureSlPrice = null;
            if ($traderPosition->signal) {
                $structureSlPrice = $traderPosition->signal->structure_sl_price ?? null;
            }
            
            $slPrice = $this->presetExecutionService->calculateSlPrice(
                $config,
                $traderPosition->entry_price,
                $traderPosition->direction,
                $structureSlPrice
            );

            if ($slPrice) {
                $orderOptions['sl_price'] = $slPrice;
            }
        }

        // Calculate TP prices (for multi-TP)
        $slPriceForTp = $orderOptions['sl_price'] ?? $traderPosition->sl_price ?? $traderPosition->entry_price * 0.99;
        $tpPrices = $this->presetExecutionService->calculateTpPrices(
            $config,
            $traderPosition->entry_price,
            $slPriceForTp,
            $traderPosition->direction
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
}

