<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get user with subscriptions relationship loaded
     *
     * @param int $userId
     * @return User|null
     */
    public function getWithSubscriptions(int $userId)
    {
        return $this->model->with('subscriptions')->find($userId);
    }

    /**
     * Search users by query with optional filters
     *
     * @param string $query
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function searchUsers(string $query, array $filters = [])
    {
        $q = $this->model->newQuery();

        if ($query) {
            $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
        }

        if (isset($filters['role'])) {
            $q->where('role', $filters['role']);
        }

        if (isset($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get active users with optional limit
     *
     * @param int $limit
     * @return Collection
     */
    public function getActiveUsers(int $limit = 100)
    {
        return $this->model->where('status', 'active')
            ->limit($limit)
            ->get();
    }
}
