<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlanSubscription;

/**
 * User Onboarding Service
 * Computes onboarding checklist state for users
 */
class UserOnboardingService
{
    /**
     * Get onboarding checklist for a user
     */
    public function getChecklist(User $user): array
    {
        $checklist = [];

        // 1. Complete Profile
        $checklist[] = [
            'id' => 'complete_profile',
            'label' => __('Complete Profile'),
            'completed' => $this->isProfileComplete($user),
            'route' => route('user.profile'),
        ];

        // 2. Choose a Plan
        $checklist[] = [
            'id' => 'choose_plan',
            'label' => __('Choose a Plan'),
            'completed' => $this->hasActivePlan($user),
            'route' => route('user.plans'),
        ];

        // 3. Connect Trade Account
        $checklist[] = [
            'id' => 'connect_trade_account',
            'label' => __('Connect Trade Account'),
            'completed' => $this->hasTradeConnection($user),
            'route' => route('user.execution-connections.index') ?? '#',
        ];

        // 4. Add External Signal
        $checklist[] = [
            'id' => 'add_external_signal',
            'label' => __('Add External Signal'),
            'completed' => $this->hasExternalSignal($user),
            'route' => route('user.external-signals.index') ?? '#',
        ];

        // 5. Create Trading Preset
        $checklist[] = [
            'id' => 'create_trading_preset',
            'label' => __('Create Trading Preset'),
            'completed' => $this->hasTradingPreset($user),
            'route' => route('user.trading-presets.index') ?? '#',
        ];

        return $checklist;
    }

    /**
     * Get onboarding progress percentage
     */
    public function getProgress(User $user): int
    {
        $checklist = $this->getChecklist($user);
        $completed = count(array_filter($checklist, fn($item) => $item['completed']));
        return (int) round(($completed / count($checklist)) * 100);
    }

    /**
     * Check if profile is complete
     */
    protected function isProfileComplete(User $user): bool
    {
        // Check required fields
        return !empty($user->email) 
            && !empty($user->username)
            && !empty($user->email_verified_at);
    }

    /**
     * Check if user has active plan
     */
    protected function hasActivePlan(User $user): bool
    {
        $subscription = $user->currentplan()
            ->where('is_current', 1)
            ->where('plan_expired_at', '>', now())
            ->first();
        
        return $subscription !== null;
    }

    /**
     * Check if user has trade connection
     */
    protected function hasTradeConnection(User $user): bool
    {
        if (!\App\Support\AddonRegistry::active('trading-management-addon') || !\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution')) {
            return false;
        }

        if (!class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            return false;
        }

        return \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::userOwned()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if user has external signal source
     */
    protected function hasExternalSignal(User $user): bool
    {
        if (!\App\Support\AddonRegistry::active('multi-channel-signal-addon')) {
            return false;
        }

        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
            return false;
        }

        return \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::userOwned()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if user has trading preset
     */
    protected function hasTradingPreset(User $user): bool
    {
        if (!\App\Support\AddonRegistry::active('trading-preset-addon')) {
            return false;
        }

        if (!class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return false;
        }

        return \Addons\TradingPresetAddon\App\Models\TradingPreset::where('created_by_user_id', $user->id)
            ->exists();
    }
}

