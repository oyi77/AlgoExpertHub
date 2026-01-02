<?php

namespace App\Repositories;

use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Repositories\Contracts\ExchangeConnectionRepositoryInterface;

class ExchangeConnectionRepository extends BaseRepository implements ExchangeConnectionRepositoryInterface
{
    /**
     * ExchangeConnectionRepository constructor.
     *
     * @param ExchangeConnection $model
     */
    public function __construct(ExchangeConnection $model)
    {
        $this->model = $model;
    }

    /**
     * Get user's exchange connections with optional active filter
     *
     * @param int $userId
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConnections(int $userId, bool $activeOnly = false)
    {
        $query = $this->model->where('user_id', $userId);
        
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        
        return $query->get();
    }

    /**
     * Get all active exchange connections
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveConnections()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get connections by exchange type, optionally for a specific user
     *
     * @param string $type
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByExchangeType(string $type, int $userId = null)
    {
        $query = $this->model->where('type', $type);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->get();
    }
}
