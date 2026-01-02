<?php

namespace App\Repositories\Contracts;

interface ExchangeConnectionRepositoryInterface
{
    /**
     * Get user's exchange connections with optional active filter
     *
     * @param int $userId
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConnections(int $userId, bool $activeOnly = false);

    /**
     * Get all active exchange connections
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveConnections();

    /**
     * Get connections by exchange type, optionally for a specific user
     *
     * @param string $type
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByExchangeType(string $type, int $userId = null);
}
