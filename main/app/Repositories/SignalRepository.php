<?php

namespace App\Repositories;

use App\Models\Signal;
use App\Repositories\Contracts\SignalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SignalRepository extends BaseRepository implements SignalRepositoryInterface
{
    /**
     * SignalRepository constructor.
     *
     * @param Signal $model
     */
    public function __construct(Signal $model)
    {
        parent::__construct($model);
    }

    /**
     * Get recent signals with optional filters
     *
     * @param int $limit
     * @param array $filters
     * @return Collection
     */
    public function getRecentSignals(int $limit = 50, array $filters = [])
    {
        $query = $this->model->newQuery();

        if (isset($filters['published_only']) && $filters['published_only']) {
            $query->where('is_published', 1);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest('published_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get signals for a specific trading pair
     *
     * @param string $pair
     * @param int $limit
     * @return Collection
     */
    public function getSignalsByPair(string $pair, int $limit = 100)
    {
        return $this->model->whereHas('pair', function ($q) use ($pair) {
            $q->where('name', $pair);
        })
        ->where('is_published', 1)
        ->latest('published_date')
        ->limit($limit)
        ->get();
    }

    /**
     * Get active signals, optionally filtered by user
     *
     * @param int|null $userId
     * @return Collection
     */
    public function getActiveSignals(int $userId = null)
    {
        $query = $this->model->where('is_published', 1)
            ->where('status', 'active'); // Assuming status 'active' exists

        if ($userId) {
            // If user filtering logic is complex (e.g. user subscriptions), add it here
            // For now, assuming basic filtering if relationship exists or ignoring if not applicable directly
        }

        return $query->get();
    }

    /**
     * Get signals for a user based on subscription plans
     *
     * @param \App\Models\User $user
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSignalsForUser(\App\Models\User $user, array $params = [])
    {
        $currentPlan = $user->currentplan()->first();
        
        if (!$currentPlan) {
            // Return empty paginator
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), 
                0, 
                \App\Helpers\Helper\Helper::pagination(), 
                1
            );
        }

        return $this->model->where('is_published', 1)
            ->when($params['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('title', 'LIKE', '%' . $search . '%');
                });
            })
            ->whereHas('plans', function ($query) use ($currentPlan) {
                $query->where('plans.id', $currentPlan->plan_id);
            })
            ->latest('published_date')
            ->with(['plans', 'pair', 'time', 'market'])
            ->paginate(\App\Helpers\Helper\Helper::pagination());
    }
}
