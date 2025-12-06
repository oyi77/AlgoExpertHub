<?php

namespace Addons\TradingPresetAddon\App\Services;

use Addons\TradingPresetAddon\App\Models\TradingPreset;
use App\Models\User;

class PresetResolverService
{
    /**
     * Resolve preset for execution context
     * Priority: Bot preset > Subscription preset > Connection preset > User default > System default
     *
     * @param array $context Context with keys: bot, subscription, connection, user, signal
     * @return TradingPreset|null
     */
    public function resolve(array $context): ?TradingPreset
    {
        // 1. Bot preset (highest priority)
        if (!empty($context['bot']) && !empty($context['bot']->preset_id)) {
            $preset = TradingPreset::find($context['bot']->preset_id);
            if ($preset && $preset->enabled) {
                return $preset;
            }
        }

        // 2. Subscription preset (copy trading)
        if (!empty($context['subscription']) && !empty($context['subscription']->preset_id)) {
            $preset = TradingPreset::find($context['subscription']->preset_id);
            if ($preset && $preset->enabled) {
                return $preset;
            }
        }

        // 3. Connection preset
        if (!empty($context['connection']) && !empty($context['connection']->preset_id)) {
            $preset = TradingPreset::find($context['connection']->preset_id);
            if ($preset && $preset->enabled) {
                return $preset;
            }
        }

        // 4. User default preset
        if (!empty($context['user']) && !empty($context['user']->default_preset_id)) {
            $preset = TradingPreset::find($context['user']->default_preset_id);
            if ($preset && $preset->enabled) {
                return $preset;
            }
        }

        // 5. System default preset (lowest priority)
        $defaultPreset = TradingPreset::defaultTemplates()
            ->enabled()
            ->orderBy('id')
            ->first();

        return $defaultPreset;
    }

    /**
     * Resolve preset for bot execution
     *
     * @param mixed $bot
     * @param mixed $connection
     * @param User|null $user
     * @return TradingPreset|null
     */
    public function resolveForBot($bot, $connection, ?User $user = null): ?TradingPreset
    {
        return $this->resolve([
            'bot' => $bot,
            'connection' => $connection,
            'user' => $user,
        ]);
    }

    /**
     * Resolve preset for copy trading
     *
     * @param mixed $subscription
     * @param mixed $connection
     * @param User|null $user
     * @return TradingPreset|null
     */
    public function resolveForCopyTrading($subscription, $connection, ?User $user = null): ?TradingPreset
    {
        return $this->resolve([
            'subscription' => $subscription,
            'connection' => $connection,
            'user' => $user,
        ]);
    }

    /**
     * Resolve preset for signal execution
     *
     * @param mixed $connection
     * @param User|null $user
     * @param mixed $signal
     * @return TradingPreset|null
     */
    public function resolveForSignal($connection, ?User $user = null, $signal = null): ?TradingPreset
    {
        // Future: signal.preset_id can be added here
        $context = [
            'connection' => $connection,
            'user' => $user,
            'signal' => $signal,
        ];

        // If signal has preset_id (future feature)
        if ($signal && isset($signal->preset_id) && $signal->preset_id) {
            $preset = TradingPreset::find($signal->preset_id);
            if ($preset && $preset->enabled) {
                return $preset;
            }
        }

        return $this->resolve($context);
    }
}

