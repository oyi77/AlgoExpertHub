<?php

namespace App\Repositories;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use App\Repositories\Contracts\BacktestRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BacktestRepository extends BaseRepository implements BacktestRepositoryInterface
{
    /**
     * BacktestRepository constructor.
     *
     * @param Backtest $model
     */
    public function __construct(Backtest $model)
    {
        parent::__construct($model);
    }

    /**
     * Get backtests for a specific user with optional filters
     *
     * @param int $userId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, array $filters = [])
    {
        $query = $this->model->where('user_id', $userId);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get backtest with trades relationship loaded
     *
     * @param int $backtestId
     * @return Backtest|null
     */
    public function getWithTrades(int $backtestId)
    {
        return $this->model->with('trades')->find($backtestId);
    }

    /**
     * Get backtests by status with optional limit
     *
     * @param string $status
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status, int $limit = 50)
    {
        return $this->model->where('status', $status)
            ->limit($limit)
            ->get();
    }
}
