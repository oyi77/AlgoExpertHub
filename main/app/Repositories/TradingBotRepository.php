<?php

namespace App\Repositories;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use App\Repositories\Contracts\TradingBotRepositoryInterface;

class TradingBotRepository extends BaseRepository implements TradingBotRepositoryInterface
{
    /**
     * TradingBotRepository constructor.
     *
     * @param TradingBot $model
     */
    public function __construct(TradingBot $model)
    {
        parent::__construct($model);
    }

    /**
     * Get user's trading bots
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserBots(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * Get active bots for user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBotsForUser(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get bot with relations
     *
     * @param int $botId
     * @param array $relations
     * @return TradingBot|null
     */
    public function getBotWithRelations(int $botId, array $relations = [])
    {
        $query = $this->model->newQuery();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->find($botId);
    }
}
