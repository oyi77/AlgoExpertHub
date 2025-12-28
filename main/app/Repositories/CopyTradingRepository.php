<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopyTradingRepository implements CopyTradingRepositoryInterface
{
    /**
     * Get follower count for a trader
     */
    public function getFollowerCount(int $traderId): int
    {
        if (!Schema::hasTable('copy_trading_subscriptions')) {
            return 0;
        }

        return (int) DB::table('copy_trading_subscriptions')
            ->where('trader_id', $traderId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Get public traders with pagination
     */
    public function getPublicTraders(int $perPage = 20): LengthAwarePaginator
    {
        if (!Schema::hasTable('trader_profiles')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return DB::table('trader_profiles')
            ->where('visibility', 'PUBLIC')
            ->where('is_verified', true)
            ->join('users', 'trader_profiles.user_id', '=', 'users.id')
            ->select('trader_profiles.*', 'users.username', 'users.email')
            ->orderBy('total_profit_percent', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get trader profile by user ID
     */
    public function getTraderProfile(int $userId): ?object
    {
        if (!Schema::hasTable('trader_profiles')) {
            return null;
        }

        return DB::table('trader_profiles')
            ->where('user_id', $userId)
            ->where('visibility', 'PUBLIC')
            ->join('users', 'trader_profiles.user_id', '=', 'users.id')
            ->select('trader_profiles.*', 'users.username', 'users.email')
            ->first();
    }

    /**
     * Check if user is following a trader
     */
    public function isFollowing(int $followerId, int $traderId): bool
    {
        if (!Schema::hasTable('copy_trading_subscriptions')) {
            return false;
        }

        return DB::table('copy_trading_subscriptions')
            ->where('trader_id', $traderId)
            ->where('follower_id', $followerId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get copy trading subscriptions for a follower
     */
    public function getSubscriptions(int $followerId, int $perPage = 20): LengthAwarePaginator
    {
        if (!Schema::hasTable('copy_trading_subscriptions')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return DB::table('copy_trading_subscriptions')
            ->where('follower_id', $followerId)
            ->join('users', 'copy_trading_subscriptions.trader_id', '=', 'users.id')
            ->select('copy_trading_subscriptions.*', 'users.username as trader_username')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get copy trading execution history for a follower
     */
    public function getExecutionHistory(int $followerId, int $perPage = 20): LengthAwarePaginator
    {
        if (!Schema::hasTable('copy_trading_executions')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return DB::table('copy_trading_executions')
            ->where('follower_id', $followerId)
            ->join('users', 'copy_trading_executions.trader_id', '=', 'users.id')
            ->select('copy_trading_executions.*', 'users.username as trader_username')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

