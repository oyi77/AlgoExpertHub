<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CopyTradingRepositoryInterface
{
    /**
     * Get follower count for a trader
     *
     * @param int $traderId
     * @return int
     */
    public function getFollowerCount(int $traderId): int;

    /**
     * Get public traders with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublicTraders(int $perPage = 20): LengthAwarePaginator;

    /**
     * Get trader profile by user ID
     *
     * @param int $userId
     * @return object|null
     */
    public function getTraderProfile(int $userId): ?object;

    /**
     * Check if user is following a trader
     *
     * @param int $followerId
     * @param int $traderId
     * @return bool
     */
    public function isFollowing(int $followerId, int $traderId): bool;

    /**
     * Get copy trading subscriptions for a follower
     *
     * @param int $followerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscriptions(int $followerId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get copy trading execution history for a follower
     *
     * @param int $followerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExecutionHistory(int $followerId, int $perPage = 20): LengthAwarePaginator;
}

