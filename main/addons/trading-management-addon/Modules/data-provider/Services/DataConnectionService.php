<?php

namespace Addons\TradingManagement\Modules\DataProvider\Services;

use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory;
use Illuminate\Support\Facades\DB;

/**
 * Data Connection Service
 * 
 * Handles CRUD operations for data connections and testing
 */
class DataConnectionService
{
    protected AdapterFactory $adapterFactory;

    public function __construct(AdapterFactory $adapterFactory)
    {
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Create a new data connection
     * 
     * @param array $data Connection data
     * @return array ['type' => 'success|error', 'message' => string, 'data' => DataConnection|null]
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            // Determine ownership
            $isAdminOwned = isset($data['is_admin_owned']) && $data['is_admin_owned'];
            
            $connection = DataConnection::create([
                'user_id' => $isAdminOwned ? null : ($data['user_id'] ?? auth()->id()),
                'admin_id' => $isAdminOwned ? ($data['admin_id'] ?? auth()->guard('admin')->id()) : null,
                'name' => $data['name'],
                'type' => $data['type'],
                'provider' => $data['provider'],
                'credentials' => $data['credentials'], // Will be encrypted by trait
                'config' => $data['config'] ?? null,
                'is_admin_owned' => $isAdminOwned,
                'settings' => $data['settings'] ?? null,
                'status' => 'inactive',
                'is_active' => false,
            ]);

            // Log creation
            $connection->logAction('connect', 'success', 'Connection created');

            DB::commit();

            return [
                'type' => 'success',
                'message' => 'Data connection created successfully',
                'data' => $connection,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to create data connection', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'type' => 'error',
                'message' => 'Failed to create connection: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Update a data connection
     * 
     * @param DataConnection $connection
     * @param array $data
     * @return array
     */
    public function update(DataConnection $connection, array $data): array
    {
        try {
            DB::beginTransaction();

            $connection->update([
                'name' => $data['name'] ?? $connection->name,
                'type' => $data['type'] ?? $connection->type,
                'provider' => $data['provider'] ?? $connection->provider,
                'credentials' => $data['credentials'] ?? $connection->credentials,
                'config' => $data['config'] ?? $connection->config,
                'settings' => $data['settings'] ?? $connection->settings,
            ]);

            $connection->logAction('update', 'success', 'Connection updated');

            DB::commit();

            return [
                'type' => 'success',
                'message' => 'Connection updated successfully',
                'data' => $connection->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to update data connection', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'error',
                'message' => 'Failed to update connection: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Delete a data connection
     * 
     * @param DataConnection $connection
     * @return array
     */
    public function delete(DataConnection $connection): array
    {
        try {
            DB::beginTransaction();

            $name = $connection->name;
            $connection->delete();

            DB::commit();

            return [
                'type' => 'success',
                'message' => "Connection '{$name}' deleted successfully",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Failed to delete data connection', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'error',
                'message' => 'Failed to delete connection: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test a data connection
     * 
     * @param DataConnection $connection
     * @return array ['type' => 'success|error', 'message' => string, 'data' => array|null]
     */
    public function test(DataConnection $connection): array
    {
        try {
            $adapter = $this->adapterFactory->create($connection);
            
            // Test connection
            $testResult = $adapter->testConnection();

            if ($testResult['success']) {
                $connection->markAsActive();
                $connection->logAction('test', 'success', $testResult['message'], [
                    'latency' => $testResult['latency'],
                ]);

                return [
                    'type' => 'success',
                    'message' => $testResult['message'],
                    'data' => $testResult,
                ];
            } else {
                $connection->markAsError($testResult['message']);
                $connection->logAction('test', 'failed', $testResult['message']);

                return [
                    'type' => 'error',
                    'message' => $testResult['message'],
                    'data' => $testResult,
                ];
            }
        } catch (\Exception $e) {
            $connection->markAsError($e->getMessage());
            $connection->logAction('test', 'failed', $e->getMessage());

            \Log::error('Connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'type' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Activate a connection
     * 
     * @param DataConnection $connection
     * @return array
     */
    public function activate(DataConnection $connection): array
    {
        // Test before activating
        $testResult = $this->test($connection);

        if ($testResult['type'] === 'success') {
            $connection->update(['is_active' => true]);
            
            return [
                'type' => 'success',
                'message' => 'Connection activated successfully',
            ];
        }

        return [
            'type' => 'error',
            'message' => 'Cannot activate connection. Test failed: ' . $testResult['message'],
        ];
    }

    /**
     * Deactivate a connection
     * 
     * @param DataConnection $connection
     * @return array
     */
    public function deactivate(DataConnection $connection): array
    {
        try {
            $connection->update([
                'is_active' => false,
                'status' => 'inactive',
            ]);

            $connection->logAction('disconnect', 'success', 'Connection deactivated');

            return [
                'type' => 'success',
                'message' => 'Connection deactivated successfully',
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Failed to deactivate connection: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get adapter for a connection
     * 
     * @param DataConnection $connection
     * @return \Addons\TradingManagement\Shared\Contracts\DataProviderInterface
     */
    public function getAdapter(DataConnection $connection)
    {
        return $this->adapterFactory->create($connection);
    }

    /**
     * Get active connections for background jobs
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getActiveConnections()
    {
        return DataConnection::active()->get();
    }

    /**
     * Get connections with errors (for monitoring)
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getConnectionsWithErrors()
    {
        return DataConnection::withErrors()->get();
    }
}

