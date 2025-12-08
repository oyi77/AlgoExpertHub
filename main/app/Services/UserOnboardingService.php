<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlanSubscription;
use App\Models\UserOnboardingProgress;

/**
 * User Onboarding Service
 * Manages user onboarding progress and checklist
 */
class UserOnboardingService
{
    /**
     * Get onboarding steps for a user
     * 
     * @param User $user
     * @return array
     */
    public function getSteps(User $user): array
    {
        $progress = $this->getProgressRecord($user);
        
        return [
            'welcome' => [
                'completed' => $progress->welcome_seen ?? false,
                'required' => false,
                'route' => 'user.onboarding.welcome',
            ],
            'profile' => [
                'completed' => $this->isProfileComplete($user),
                'required' => true,
                'route' => 'user.profile',
            ],
            'plan' => [
                'completed' => $this->hasActivePlan($user),
                'required' => true,
                'route' => 'user.plans',
            ],
            'signal_source' => [
                'completed' => $this->hasSignalSource($user),
                'required' => false,
                'route' => 'user.trading.multi-channel-signal.index',
                'condition' => 'multi-channel-signal-addon',
            ],
            'trading_connection' => [
                'completed' => $this->hasTradingConnection($user),
                'required' => false,
                'route' => 'user.trading.operations.index',
                'condition' => 'trading-management-addon',
            ],
            'trading_preset' => [
                'completed' => $this->hasTradingPreset($user),
                'required' => false,
                'route' => 'user.trading.configuration.index',
                'condition' => 'trading-management-addon',
            ],
            'first_deposit' => [
                'completed' => $this->hasMadeDeposit($user),
                'required' => false,
                'route' => 'user.deposit',
            ],
        ];
    }

    /**
     * Get onboarding checklist for a user (for widget display)
     * 
     * @param User $user
     * @return array
     */
    public function getChecklist(User $user): array
    {
        $steps = $this->getSteps($user);
        $checklist = [];

        foreach ($steps as $key => $step) {
            // Skip welcome step in checklist
            if ($key === 'welcome') {
                continue;
            }

            // Check if step should be shown (conditional steps)
            if (isset($step['condition'])) {
                if (!\App\Support\AddonRegistry::active($step['condition'])) {
                    continue;
                }
            }

            $checklist[] = [
                'id' => $key,
                'label' => $this->getStepLabel($key),
                'completed' => $step['completed'],
                'route' => $step['route'] ?? '#',
            ];
        }

        return $checklist;
    }

    /**
     * Get step label
     * 
     * @param string $stepKey
     * @return string
     */
    protected function getStepLabel(string $stepKey): string
    {
        $labels = [
            'profile' => __('Complete Profile'),
            'plan' => __('Subscribe to Plan'),
            'signal_source' => __('Connect Signal Source'),
            'trading_connection' => __('Setup Auto Trading'),
            'trading_preset' => __('Create Trading Preset'),
            'first_deposit' => __('Make First Deposit'),
        ];

        return $labels[$stepKey] ?? ucfirst(str_replace('_', ' ', $stepKey));
    }

    /**
     * Get onboarding progress percentage
     * 
     * @param User $user
     * @return int
     */
    public function getProgress(User $user): int
    {
        $steps = $this->getSteps($user);
        
        // Count all steps (including optional) for progress calculation
        $allSteps = [];
        foreach ($steps as $key => $step) {
            // Skip welcome step
            if ($key === 'welcome') {
                continue;
            }
            // Check if step should be counted (conditional steps)
            if (isset($step['condition'])) {
                if (!\App\Support\AddonRegistry::active($step['condition'])) {
                    continue;
                }
            }
            $allSteps[$key] = $step;
        }
        
        $completedSteps = count(array_filter($allSteps, fn($step) => $step['completed']));
        $totalSteps = count($allSteps);
        
        return $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;
    }

    /**
     * Check if user should see onboarding
     * 
     * @param User $user
     * @return bool
     */
    public function shouldShowOnboarding(User $user): bool
    {
        $progress = $this->getProgressRecord($user);
        
        // If onboarding already completed, don't show
        if ($progress->onboarding_completed ?? false) {
            return false;
        }

        // Show if progress < 100%
        $progressPercent = $this->getProgress($user);
        return $progressPercent < 100;
    }

    /**
     * Mark onboarding step as completed
     * 
     * @param User $user
     * @param string $step
     * @return void
     */
    public function completeStep(User $user, string $step): void
    {
        $progress = $this->getProgressRecord($user);
        
        $fieldMap = [
            'welcome' => 'welcome_seen',
            'profile' => 'profile_completed',
            'plan' => 'plan_subscribed',
            'signal_source' => 'signal_source_added',
            'trading_connection' => 'trading_connection_setup',
            'trading_preset' => 'trading_preset_created',
            'first_deposit' => 'first_deposit_made',
        ];

        if (isset($fieldMap[$step])) {
            $progress->update([
                $fieldMap[$step] => true,
            ]);
        }

        // Auto-update progress based on current state
        $this->syncProgress($user);
    }

    /**
     * Get next incomplete step
     * 
     * @param User $user
     * @return string|null
     */
    public function getNextIncompleteStep(User $user): ?string
    {
        $steps = $this->getSteps($user);
        
        // Skip welcome step
        unset($steps['welcome']);
        
        foreach ($steps as $key => $step) {
            // Check if step should be shown (conditional steps)
            if (isset($step['condition'])) {
                if (!\App\Support\AddonRegistry::active($step['condition'])) {
                    continue;
                }
            }
            
            if (!$step['completed']) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Mark entire onboarding as completed
     * 
     * @param User $user
     * @return void
     */
    public function completeOnboarding(User $user): void
    {
        $progress = $this->getProgressRecord($user);
        $progress->update([
            'onboarding_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Sync progress from current user state
     * 
     * @param User $user
     * @return void
     */
    public function syncProgress(User $user): void
    {
        $progress = $this->getProgressRecord($user);
        
        $progress->update([
            'profile_completed' => $this->isProfileComplete($user),
            'plan_subscribed' => $this->hasActivePlan($user),
            'signal_source_added' => $this->hasSignalSource($user),
            'trading_connection_setup' => $this->hasTradingConnection($user),
            'trading_preset_created' => $this->hasTradingPreset($user),
            'first_deposit_made' => $this->hasMadeDeposit($user),
        ]);

        // Check if all required steps complete
        $steps = $this->getSteps($user);
        $requiredSteps = array_filter($steps, fn($step) => $step['required'] ?? false);
        $allRequiredComplete = count(array_filter($requiredSteps, fn($step) => $step['completed'])) === count($requiredSteps);
        
        if ($allRequiredComplete && !$progress->onboarding_completed) {
            $this->completeOnboarding($user);
        }
    }

    /**
     * Get or create progress record
     * 
     * @param User $user
     * @return UserOnboardingProgress
     */
    protected function getProgressRecord(User $user): UserOnboardingProgress
    {
        return UserOnboardingProgress::firstOrCreate(
            ['user_id' => $user->id],
            [
                'welcome_seen' => false,
                'profile_completed' => false,
                'plan_subscribed' => false,
                'signal_source_added' => false,
                'trading_connection_setup' => false,
                'trading_preset_created' => false,
                'first_deposit_made' => false,
                'onboarding_completed' => false,
            ]
        );
    }

    /**
     * Check if user has completed basic setup
     * 
     * @param User $user
     * @return bool
     */
    public function hasBasicSetup(User $user): bool
    {
        return $this->isProfileComplete($user) 
            && $this->hasActivePlan($user);
    }

    /**
     * Check if profile is complete
     * 
     * @param User $user
     * @return bool
     */
    public function isProfileComplete(User $user): bool
    {
        // Check required fields
        return !empty($user->email) 
            && !empty($user->username)
            && ($user->is_email_verified || !empty($user->email_verified_at));
    }

    /**
     * Check if user has active plan
     * 
     * @param User $user
     * @return bool
     */
    public function hasActivePlan(User $user): bool
    {
        $subscription = $user->currentplan()
            ->where('is_current', 1)
            ->first();
        
        if (!$subscription) {
            return false;
        }
        
        // If subscription has is_current = 1, consider it active for menu visibility
        // The actual expiry enforcement should be handled separately in middleware or service layer
        // For menu visibility purposes, we show trading menu if user has any subscription marked as current
        return true;
    }

    /**
     * Check if user has trade connection
     * 
     * @param User $user
     * @return bool
     */
    public function hasTradingConnection(User $user): bool
    {
        if (!\App\Support\AddonRegistry::active('trading-management-addon') || !\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution')) {
            return false;
        }

        if (!class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class)) {
            return false;
        }

        try {
            // Use byUser scope if available, otherwise use direct query
            $model = \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::class;
            
            if (method_exists($model, 'scopeByUser')) {
                return $model::byUser($user->id)->exists();
            } else {
                // Fallback: direct query
                return $model::where('user_id', $user->id)
                    ->where('is_admin_owned', false)
                    ->exists();
            }
        } catch (\Exception $e) {
            \Log::warning('Error checking trading connection', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user has signal source
     * 
     * @param User $user
     * @return bool
     */
    public function hasSignalSource(User $user): bool
    {
        if (!\App\Support\AddonRegistry::active('multi-channel-signal-addon')) {
            return false;
        }

        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
            return false;
        }

        try {
            // Use userOwned scope and filter by user_id
            return \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::userOwned()
                ->where('user_id', $user->id)
                ->exists();
        } catch (\Exception $e) {
            \Log::warning('Error checking signal source', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user has trading preset
     * 
     * @param User $user
     * @return bool
     */
    public function hasTradingPreset(User $user): bool
    {
        // Check trading-management-addon first (new unified addon)
        if (\App\Support\AddonRegistry::active('trading-management-addon')) {
            if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                return \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where('created_by_user_id', $user->id)
                    ->exists();
            }
        }

        // Fallback to deprecated addon
        if (\App\Support\AddonRegistry::active('trading-preset-addon')) {
            if (class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
                return \Addons\TradingPresetAddon\App\Models\TradingPreset::where('created_by_user_id', $user->id)
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Check if user has made a deposit
     * 
     * @param User $user
     * @return bool
     */
    public function hasMadeDeposit(User $user): bool
    {
        return $user->deposits()
            ->where('status', 1) // Approved
            ->exists();
    }
}

