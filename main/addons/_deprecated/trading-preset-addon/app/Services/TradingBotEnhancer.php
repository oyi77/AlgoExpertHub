<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingPresetAddon\App\DTOs\PresetConfigurationDTO;
use App\Models\Signal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Enhancer for Trading Bots to add preset functionality
 * Trading bots execute signals through SignalExecutionService,
 * so this enhancer provides preset context for bot execution
 */
class TradingBotEnhancer
{
    protected PresetResolverService $presetResolver;
    protected PresetApplicatorService $presetApplicator;
    protected PresetExecutionService $presetExecutionService;

    public function __construct(
        PresetResolverService $presetResolver,
        PresetApplicatorService $presetApplicator,
        PresetExecutionService $presetExecutionService
    ) {
        $this->presetResolver = $presetResolver;
        $this->presetApplicator = $presetApplicator;
        $this->presetExecutionService = $presetExecutionService;
    }

    /**
     * Get preset configuration for bot execution
     * 
     * @param mixed $bot Trading bot instance (with preset_id property)
     * @param ExecutionConnection $connection Execution connection
     * @param User|null $user User who owns the bot
     * @return PresetConfigurationDTO|null
     */
    public function getPresetConfig($bot, ExecutionConnection $connection, ?User $user = null): ?PresetConfigurationDTO
    {
        $preset = $this->presetResolver->resolveForBot($bot, $connection, $user);
        
        if (!$preset) {
            return null;
        }

        $connectionSettings = $connection->settings ?? [];
        return $this->presetApplicator->applyAsDTO($preset, $connectionSettings);
    }

    /**
     * Check if bot can execute signal based on preset rules
     * 
     * @param mixed $bot Trading bot instance
     * @param ExecutionConnection $connection Execution connection
     * @param Signal $signal Signal to execute
     * @param User|null $user User who owns the bot
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canExecuteSignal($bot, ExecutionConnection $connection, Signal $signal, ?User $user = null): array
    {
        $preset = $this->presetResolver->resolveForBot($bot, $connection, $user);
        
        if (!$preset) {
            return ['allowed' => true, 'reason' => null]; // No preset, allow execution
        }

        $connectionSettings = $connection->settings ?? [];
        $config = $this->presetApplicator->applyAsDTO($preset, $connectionSettings);

        // Check symbol filter
        if ($config->symbol && $config->symbol !== '*' && $signal->symbol !== $config->symbol) {
            return [
                'allowed' => false,
                'reason' => "Preset is configured for symbol {$config->symbol}, but signal is for {$signal->symbol}",
            ];
        }

        // Check timeframe filter
        if ($config->timeframe && $config->timeframe !== '*' && $signal->timeframe !== $config->timeframe) {
            return [
                'allowed' => false,
                'reason' => "Preset is configured for timeframe {$config->timeframe}, but signal is for {$signal->timeframe}",
            ];
        }

        // Check trading schedule
        $scheduleCheck = $this->presetExecutionService->checkTradingSchedule($config);
        if (!$scheduleCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $scheduleCheck['reason'],
            ];
        }

        // Check weekly target
        $weeklyTargetCheck = $this->presetExecutionService->checkWeeklyTarget($connection, $config);
        if (!$weeklyTargetCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $weeklyTargetCheck['reason'],
            ];
        }

        // Check max positions
        $symbol = $signal->symbol ?? 'UNKNOWN';
        $maxPositionsCheck = $this->presetExecutionService->checkMaxPositions($connection, $config, $symbol);
        if (!$maxPositionsCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $maxPositionsCheck['reason'],
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Enhance order options for bot execution
     * 
     * @param mixed $bot Trading bot instance
     * @param ExecutionConnection $connection Execution connection
     * @param Signal $signal Signal to execute
     * @param User|null $user User who owns the bot
     * @param array $baseOptions Base order options
     * @return array Enhanced order options
     */
    public function enhanceOrderOptions(
        $bot,
        ExecutionConnection $connection,
        Signal $signal,
        ?User $user = null,
        array $baseOptions = []
    ): array {
        $preset = $this->presetResolver->resolveForBot($bot, $connection, $user);
        
        if (!$preset) {
            return $baseOptions; // No preset, return base options
        }

        $connectionSettings = $connection->settings ?? [];
        $config = $this->presetApplicator->applyAsDTO($preset, $connectionSettings);

        $orderOptions = $baseOptions;

        // Calculate position size if not already set
        if (empty($orderOptions['quantity'])) {
            $quantity = $this->calculatePositionSize($config, $connection, $signal);
            if ($quantity > 0) {
                $orderOptions['quantity'] = $quantity;
            }
        }

        // Calculate SL price from preset (if not already set)
        if (empty($orderOptions['sl_price']) && empty($signal->sl)) {
            $entryPrice = $signal->open_price ?? 0;
            $direction = $signal->direction ?? 'buy';
            $slPrice = $this->presetExecutionService->calculateSlPrice(
                $config,
                $entryPrice,
                $direction,
                $signal->structure_sl_price ?? null
            );

            if ($slPrice) {
                $orderOptions['sl_price'] = $slPrice;
            }
        }

        // Calculate TP prices (for multi-TP)
        $entryPrice = $signal->open_price ?? 0;
        $slPriceForTp = $orderOptions['sl_price'] ?? $signal->sl ?? $entryPrice * 0.99;
        $direction = $signal->direction ?? 'buy';
        
        $tpPrices = $this->presetExecutionService->calculateTpPrices(
            $config,
            $entryPrice,
            $slPriceForTp,
            $direction
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
     * Calculate position size based on preset
     * 
     * @param PresetConfigurationDTO $config Preset configuration
     * @param ExecutionConnection $connection Execution connection
     * @param Signal $signal Signal to execute
     * @return float Position size (quantity)
     */
    protected function calculatePositionSize(
        PresetConfigurationDTO $config,
        ExecutionConnection $connection,
        Signal $signal
    ): float {
        if ($config->position_size_mode === 'FIXED') {
            return $config->fixed_lot ?? 0.01;
        }

        // RISK_PERCENT mode
        if (!class_exists(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)) {
            return 0.01; // Fallback
        }

        try {
            $connectionService = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class);
            $adapter = $connectionService->getAdapter($connection);
            
            if (!$adapter) {
                return 0.01; // Fallback
            }

            $balance = $adapter->getBalance();
            $equity = $balance['balance'] ?? 0;

            $entryPrice = $signal->open_price ?? 0;
            $slPrice = $signal->sl ?? null;

            return $this->presetApplicator->calculatePositionSize(
                $config,
                $equity,
                $entryPrice,
                $slPrice
            );
        } catch (\Exception $e) {
            Log::warning("TradingBotEnhancer: Failed to calculate position size", [
                'error' => $e->getMessage(),
            ]);
            return 0.01; // Fallback
        }
    }
}

