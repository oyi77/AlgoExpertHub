<?php

namespace Addons\TradingExecutionEngine\App\Services;

use Addons\TradingExecutionEngine\App\Adapters\BaseExchangeAdapter;
use Addons\TradingExecutionEngine\App\Adapters\CryptoExchangeAdapter;
use Addons\TradingExecutionEngine\App\Adapters\FxBrokerAdapter;
use Addons\TradingExecutionEngine\App\Adapters\Mt4Adapter;
use Addons\TradingExecutionEngine\App\Adapters\Mt5Adapter;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Illuminate\Support\Facades\Log;

class ConnectionService
{
    /**
     * Create a new connection.
     *
     * @param array $data
     * @return ExecutionConnection
     */
    public function create(array $data): ExecutionConnection
    {
        $data['is_admin_owned'] = isset($data['admin_id']) && !isset($data['user_id']);
        
        return ExecutionConnection::create($data);
    }

    /**
     * Update a connection.
     *
     * @param ExecutionConnection $connection
     * @param array $data
     * @return ExecutionConnection
     */
    public function update(ExecutionConnection $connection, array $data): ExecutionConnection
    {
        $connection->update($data);
        return $connection->fresh();
    }

    /**
     * Delete a connection.
     *
     * @param ExecutionConnection $connection
     * @return bool
     */
    public function delete(ExecutionConnection $connection): bool
    {
        return $connection->delete();
    }

    /**
     * Test connection to exchange/broker.
     *
     * @param ExecutionConnection $connection
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function testConnection(ExecutionConnection $connection): array
    {
        try {
            $adapter = $this->getAdapter($connection);
            
            if (!$adapter) {
                return [
                    'success' => false,
                    'message' => 'Unsupported exchange/broker type',
                    'data' => [],
                ];
            }

            $result = $adapter->testConnection($connection->credentials);

            // Only update connection status if connection is saved (has ID)
            if ($connection->exists && $connection->id) {
                if ($result['success']) {
                    $connection->markAsActive();
                    $connection->update([
                        'last_tested_at' => now(),
                        'last_error' => null,
                    ]);
                } else {
                    $connection->markAsError($result['message']);
                    $connection->update([
                        'last_tested_at' => now(),
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Connection test failed", [
                'connection_id' => $connection->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // Only update connection if it exists
            if ($connection->exists && $connection->id) {
                $connection->markAsError($e->getMessage());
                $connection->update(['last_tested_at' => now()]);
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get adapter instance for connection.
     *
     * @param ExecutionConnection $connection
     * @return BaseExchangeAdapter|null
     */
    public function getAdapter(ExecutionConnection $connection): ?BaseExchangeAdapter
    {
        if ($connection->type === 'crypto') {
            return new CryptoExchangeAdapter($connection);
        } elseif ($connection->type === 'fx') {
            // Check if exchange_name indicates MT4 or MT5
            $exchangeName = strtoupper($connection->exchange_name ?? '');
            
            if (strpos($exchangeName, 'MT4') !== false || $exchangeName === 'MT4') {
                return new Mt4Adapter($connection);
            } elseif (strpos($exchangeName, 'MT5') !== false || $exchangeName === 'MT5') {
                return new Mt5Adapter($connection);
            }
            
            // Fallback to generic FX broker adapter
            return new FxBrokerAdapter($connection);
        }

        return null;
    }

    /**
     * Get user connections.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConnections(int $userId)
    {
        return ExecutionConnection::byUser($userId)->get();
    }

    /**
     * Get admin connections.
     *
     * @param int $adminId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAdminConnections(int $adminId)
    {
        return ExecutionConnection::byAdmin($adminId)->get();
    }

    /**
     * Get active connections for execution.
     *
     * @param int|null $userId
     * @param int|null $adminId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveConnections(?int $userId = null, ?int $adminId = null)
    {
        $query = ExecutionConnection::active();

        if ($userId) {
            $query->where('user_id', $userId)->where('is_admin_owned', false);
        } elseif ($adminId) {
            $query->where('admin_id', $adminId)->where('is_admin_owned', true);
        }

        return $query->get();
    }

    /**
     * Activate a connection.
     *
     * @param ExecutionConnection $connection
     * @return bool
     */
    public function activate(ExecutionConnection $connection): bool
    {
        // Test connection before activating
        $test = $this->testConnection($connection);
        
        if ($test['success']) {
            $connection->update([
                'is_active' => true,
                'status' => 'active',
            ]);
            return true;
        }

        return false;
    }

    /**
     * Deactivate a connection.
     *
     * @param ExecutionConnection $connection
     * @return bool
     */
    public function deactivate(ExecutionConnection $connection): bool
    {
        return $connection->update([
            'is_active' => false,
            'status' => 'inactive',
        ]);
    }
}

