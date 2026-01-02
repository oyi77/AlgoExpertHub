<?php

namespace App\Repositories\Contracts;

interface SignalRepositoryInterface
{
    /**
     * Get recent signals with optional filters
     *
     * @param int $limit
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentSignals(int $limit = 50, array $filters = []);

    /**
     * Get signals for a specific trading pair
     *
     * @param string $pair
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSignalsByPair(string $pair, int $limit = 100);

    /**
     * Get active signals, optionally filtered by user
     *
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSignals(int $userId = null);

    /**
     * Get signals for a user based on subscription plans
     *
     * @param \App\Models\User $user
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSignalsForUser(\App\Models\User $user, array $params = []);
}
