<?php

namespace Addons\CopyTrading\App\Services;

use Addons\CopyTrading\App\Models\CopyTradingSetting;
use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use App\Models\User;
use App\Support\AddonRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopyTradingService
{
    /**
     * Get or create copy trading settings for a user.
     */
    public function getOrCreateSettings(?int $userId = null, ?int $adminId = null): CopyTradingSetting
    {
        $isAdminOwned = $adminId !== null;
        
        // Check if trading execution engine is required and available
        if (!\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
            throw new \Exception('Trading execution engine is required for copy trading');
        }
        
        if ($isAdminOwned) {
            return CopyTradingSetting::firstOrCreate(
                ['admin_id' => $adminId, 'user_id' => null, 'is_admin_owned' => true],
                [
                    'is_enabled' => false,
                    'risk_multiplier_default' => 1.0,
                    'allow_manual_trades' => true,
                    'allow_auto_trades' => true,
                ]
            );
        } else {
        return CopyTradingSetting::firstOrCreate(
                ['user_id' => $userId, 'admin_id' => null, 'is_admin_owned' => false],
            [
                'is_enabled' => false,
                'risk_multiplier_default' => 1.0,
                'allow_manual_trades' => true,
                'allow_auto_trades' => true,
            ]
        );
        }
    }

    /**
     * Enable copy trading for a user or admin.
     */
    public function enableCopyTrading(?int $userId = null, array $settings = [], ?int $adminId = null): CopyTradingSetting
    {
        $setting = $this->getOrCreateSettings($userId ?? 0, $adminId);
        
        $setting->update(array_merge([
            'is_enabled' => true,
        ], $settings));

        return $setting->fresh();
    }

    /**
     * Disable copy trading for a user or admin.
     */
    public function disableCopyTrading(?int $userId = null, ?int $adminId = null): CopyTradingSetting
    {
        $setting = $this->getOrCreateSettings($userId ?? 0, $adminId);
        $setting->update(['is_enabled' => false]);

        // Deactivate all subscriptions to this trader
        if ($userId) {
            CopyTradingSubscription::where('trader_id', $userId)
                ->active()
                ->get()
                ->each(function ($subscription) {
                    $subscription->deactivate();
                });
        }
        // Note: Admin traders don't have subscriptions via user_id, so we skip them for now

        return $setting->fresh();
    }

    /**
     * Update copy trading settings.
     */
    public function updateSettings(?int $userId = null, array $data, ?int $adminId = null): CopyTradingSetting
    {
        $setting = $this->getOrCreateSettings($userId ?? 0, $adminId);
        $setting->update($data);

        return $setting->fresh();
    }

    /**
     * Subscribe a follower to a trader.
     */
    public function subscribe(
        int $followerId,
        int $traderId,
        int $connectionId,
        string $copyMode = 'easy',
        array $settings = []
    ): CopyTradingSubscription {
        // Validate trader has copy trading enabled
        $traderSetting = CopyTradingSetting::byUser($traderId)->first();
        if (!$traderSetting || !$traderSetting->isEnabled()) {
            throw new \Exception('Trader does not have copy trading enabled');
        }

        // Check if trader can accept new followers
        if (!$traderSetting->canAcceptNewFollowers()) {
            throw new \Exception('Trader has reached maximum number of followers');
        }

        // Validate follower has active connection
        if (!AddonRegistry::active('trading-execution-engine-addon')) {
            throw new \Exception('Trading execution engine is required for copy trading');
        }
        
        if (!class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class)) {
            throw new \Exception('Trading execution engine is not available');
        }
        
        $connection = \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::findOrFail($connectionId);
        if ($connection->user_id !== $followerId || !$connection->isActive()) {
            throw new \Exception('Invalid or inactive connection');
        }

        // Check minimum balance requirement
        if ($traderSetting->min_followers_balance) {
            // Get follower balance from connection
            // This would require adapter call - simplified for now
        }

        // Check if already subscribed
        $existing = CopyTradingSubscription::where('trader_id', $traderId)
            ->where('follower_id', $followerId)
            ->first();

        if ($existing) {
            // Reactivate if exists
            $existing->update(array_merge([
                'copy_mode' => $copyMode,
                'connection_id' => $connectionId,
            ], $settings));
            $existing->activate();
            return $existing->fresh();
        }

        // Create new subscription
        $defaultSettings = [
            'trader_id' => $traderId,
            'follower_id' => $followerId,
            'copy_mode' => $copyMode,
            'connection_id' => $connectionId,
            'risk_multiplier' => $traderSetting->risk_multiplier_default,
            'is_active' => true,
            'subscribed_at' => now(),
        ];

        if ($copyMode === 'advanced') {
            $defaultSettings['copy_settings'] = $settings;
        } else {
            $defaultSettings['risk_multiplier'] = $settings['risk_multiplier'] ?? $traderSetting->risk_multiplier_default;
        }

        if (isset($settings['max_position_size'])) {
            $defaultSettings['max_position_size'] = $settings['max_position_size'];
        }

        return CopyTradingSubscription::create($defaultSettings);
    }

    /**
     * Unsubscribe a follower from a trader.
     */
    public function unsubscribe(int $followerId, int $traderId): bool
    {
        $subscription = CopyTradingSubscription::where('trader_id', $traderId)
            ->where('follower_id', $followerId)
            ->first();

        if (!$subscription) {
            return false;
        }

        $subscription->deactivate();
        return true;
    }

    /**
     * Update subscription settings.
     */
    public function updateSubscription(int $subscriptionId, int $followerId, array $data): CopyTradingSubscription
    {
        $subscription = CopyTradingSubscription::where('id', $subscriptionId)
            ->where('follower_id', $followerId)
            ->firstOrFail();

        $subscription->update($data);

        return $subscription->fresh();
    }

    /**
     * Get active subscriptions for a trader.
     */
    public function getTraderSubscriptions(int $traderId)
    {
        return CopyTradingSubscription::byTrader($traderId)
            ->active()
            ->with(['follower', 'connection'])
            ->get();
    }

    /**
     * Get subscriptions for a follower.
     */
    public function getFollowerSubscriptions(int $followerId)
    {
        return CopyTradingSubscription::byFollower($followerId)
            ->active()
            ->with(['trader', 'connection'])
            ->get();
    }

    /**
     * Get trader statistics.
     */
    public function getTraderStats(?int $traderId = null, ?int $adminId = null): array
    {
        $setting = null;
        if ($adminId) {
            $setting = CopyTradingSetting::byAdmin($adminId)->first();
        } elseif ($traderId) {
            $setting = CopyTradingSetting::byUser($traderId)->first();
        }
        
        if (!$setting) {
            return [
                'is_enabled' => false,
                'follower_count' => 0,
                'total_copied_trades' => 0,
            ];
        }

        // For admin traders, follower count would be 0 for now
        // since subscriptions use user_id
        $followerCount = 0;
        if ($traderId) {
            $followerCount = CopyTradingSubscription::byTrader($traderId)
                ->active()
                ->count();
        }

        $totalCopied = 0;
        if ($traderId) {
            $totalCopied = \Addons\CopyTrading\App\Models\CopyTradingExecution::byTrader($traderId)
                ->executed()
                ->count();
        }

        return [
            'is_enabled' => $setting->isEnabled(),
            'follower_count' => $followerCount,
            'total_copied_trades' => $totalCopied,
        ];
    }
}
