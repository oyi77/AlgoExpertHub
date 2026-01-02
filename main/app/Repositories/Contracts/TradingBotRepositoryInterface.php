<?php

namespace App\Repositories\Contracts;

interface TradingBotRepositoryInterface
{
    /**
     * Get user's trading bots with optional filters
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserBots(int $userId, array $filters = []);

    /**
     * Get active bots for a specific user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBotsForUser(int $userId);

    /**
     * Get bot with specified relationships loaded
     *
     * @param int $botId
     * @param array $relations
     * @return mixed
     */
    public function getBotWithRelations(int $botId, array $relations = []);
}
