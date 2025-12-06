<?php

namespace Addons\FilterStrategyAddon\App\Services;

use Addons\FilterStrategyAddon\App\Models\FilterStrategy;
use Addons\TradingPresetAddon\App\Models\TradingPreset;
use Addons\TradingPresetAddon\App\Services\PresetResolverService;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use App\Models\Signal;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FilterStrategyResolverService
{
    protected ?PresetResolverService $presetResolver = null;

    public function __construct()
    {
        if (class_exists(\Addons\TradingPresetAddon\App\Services\PresetResolverService::class)) {
            $this->presetResolver = app(\Addons\TradingPresetAddon\App\Services\PresetResolverService::class);
        }
    }

    /**
     * Resolve FilterStrategy for a signal
     * Priority: Preset filter_strategy_id > Connection preset > User default preset > System default preset
     * 
     * @param Signal $signal
     * @param ExecutionConnection|null $connection Optional connection
     * @param User|null $user Optional user
     * @return FilterStrategy|null
     */
    public function resolveForSignal(
        Signal $signal,
        ?ExecutionConnection $connection = null,
        ?User $user = null
    ): ?FilterStrategy {
        try {
            // 1. Try to resolve preset first
            $preset = null;
            if ($this->presetResolver && ($connection || $user)) {
                $preset = $this->presetResolver->resolveForSignal($connection, $user, $signal);
            }

            // 2. If preset has filter_strategy_id, use it
            if ($preset && $preset->filter_strategy_id) {
                $strategy = FilterStrategy::find($preset->filter_strategy_id);
                if ($strategy && $strategy->enabled) {
                    return $strategy;
                }
            }

            // 3. Future: Check connection filter_strategy_id (if added)
            // 4. Future: Check channel_source filter_strategy_id (if added)
            // 5. Future: Check user default filter_strategy_id (if added)

            // No filter strategy found
            return null;

        } catch (\Exception $e) {
            Log::error("FilterStrategyResolverService: Failed to resolve filter strategy", [
                'signal_id' => $signal->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

