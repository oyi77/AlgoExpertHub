<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CopyTradingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CopyTradingService
{
    protected CopyTradingRepositoryInterface $repository;

    public function __construct(CopyTradingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get copy trading settings for a user
     */
    public function getSettings(int $userId): array
    {
        $settings = Cache::get('smart_risk_settings_' . $userId, [
            'enabled' => false,
            'min_provider_score' => 70,
            'slippage_buffer_enabled' => false,
            'dynamic_lot_enabled' => false,
        ]);

        $followerCount = $this->repository->getFollowerCount($userId);

        return [
            'settings' => $settings,
            'follower_count' => $followerCount
        ];
    }

    /**
     * Update copy trading settings for a user
     */
    public function updateSettings(int $userId, array $validated): array
    {
        $currentSettings = Cache::get('smart_risk_settings_' . $userId, [
            'enabled' => false,
            'min_provider_score' => 70,
            'slippage_buffer_enabled' => false,
            'dynamic_lot_enabled' => false,
        ]);

        $settings = array_merge($currentSettings, $validated);
        Cache::put('smart_risk_settings_' . $userId, $settings, now()->addYear());

        return $settings;
    }

    /**
     * Get public traders
     */
    public function getTraders(int $perPage = 20)
    {
        return $this->repository->getPublicTraders($perPage);
    }

    /**
     * Get trader profile with following status
     */
    public function getTraderProfile(int $traderId, int $followerId): ?array
    {
        $trader = $this->repository->getTraderProfile($traderId);
        
        if (!$trader) {
            return null;
        }

        $isFollowing = $this->repository->isFollowing($followerId, $traderId);

        return [
            'trader' => $trader,
            'is_following' => $isFollowing
        ];
    }

    /**
     * Get subscriptions for a follower
     */
    public function getSubscriptions(int $followerId, int $perPage = 20)
    {
        return $this->repository->getSubscriptions($followerId, $perPage);
    }

    /**
     * Get execution history for a follower
     */
    public function getHistory(int $followerId, int $perPage = 20)
    {
        return $this->repository->getExecutionHistory($followerId, $perPage);
    }
}

