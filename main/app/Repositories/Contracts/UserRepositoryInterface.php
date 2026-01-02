<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    /**
     * Get user with subscriptions relationship loaded
     *
     * @param int $userId
     * @return \App\Models\User
     */
    public function getWithSubscriptions(int $userId);

    /**
     * Search users by query with optional filters
     *
     * @param string $query
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchUsers(string $query, array $filters = []);

    /**
     * Get active users with optional limit
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveUsers(int $limit = 100);
}
