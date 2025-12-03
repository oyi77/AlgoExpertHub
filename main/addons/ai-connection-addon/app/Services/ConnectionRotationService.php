<?php

namespace Addons\AiConnectionAddon\App\Services;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Support\Facades\Log;

class ConnectionRotationService
{
    /**
     * Get next available connection for a provider
     *
     * @param int $providerId Provider ID
     * @param int|null $excludeConnectionId Connection to exclude (current failing connection)
     * @return AiConnection|null
     */
    public function getNextConnection(int $providerId, ?int $excludeConnectionId = null): ?AiConnection
    {
        $connections = AiConnection::byProvider($providerId)
            ->active()
            ->healthy(10) // Error count < 10
            ->byPriority()
            ->when($excludeConnectionId, function ($query) use ($excludeConnectionId) {
                $query->where('id', '!=', $excludeConnectionId);
            })
            ->get();

        if ($connections->isEmpty()) {
            Log::warning("No healthy connections available for provider", [
                'provider_id' => $providerId,
                'excluded_connection_id' => $excludeConnectionId,
            ]);
            return null;
        }

        // Filter out rate limited connections
        $availableConnections = $connections->reject(function ($connection) {
            return $connection->isRateLimited();
        });

        if ($availableConnections->isEmpty()) {
            Log::warning("All connections are rate limited", [
                'provider_id' => $providerId,
                'total_connections' => $connections->count(),
            ]);
            return null;
        }

        // Get connection with highest priority (lowest priority number)
        $selectedConnection = $availableConnections->first();

        Log::info("Connection selected for rotation", [
            'provider_id' => $providerId,
            'connection_id' => $selectedConnection->id,
            'connection_name' => $selectedConnection->name,
            'priority' => $selectedConnection->priority,
        ]);

        return $selectedConnection;
    }

    /**
     * Get best connection based on health and priority
     *
     * @param int $providerId Provider ID
     * @return AiConnection|null
     */
    public function getBestConnection(int $providerId): ?AiConnection
    {
        return $this->getNextConnection($providerId);
    }

    /**
     * Check if provider has available connections
     *
     * @param int $providerId Provider ID
     * @return bool
     */
    public function hasAvailableConnections(int $providerId): bool
    {
        return AiConnection::byProvider($providerId)
            ->active()
            ->exists();
    }

    /**
     * Get fallback connection if primary fails
     *
     * @param int $providerId Provider ID
     * @param int $primaryConnectionId Primary connection that failed
     * @return AiConnection|null
     */
    public function getFallbackConnection(int $providerId, int $primaryConnectionId): ?AiConnection
    {
        // Get next connection excluding the failed one
        return $this->getNextConnection($providerId, $primaryConnectionId);
    }

    /**
     * Reorder connections by priority
     *
     * @param int $providerId Provider ID
     * @param array $connectionIds Array of connection IDs in desired order
     * @return void
     */
    public function reorderConnections(int $providerId, array $connectionIds): void
    {
        foreach ($connectionIds as $index => $connectionId) {
            AiConnection::where('id', $connectionId)
                ->where('provider_id', $providerId)
                ->update(['priority' => $index + 1]);
        }

        Log::info("Connections reordered", [
            'provider_id' => $providerId,
            'new_order' => $connectionIds,
        ]);
    }

    /**
     * Reset error counts for a provider's connections
     *
     * @param int $providerId Provider ID
     * @return int Number of connections reset
     */
    public function resetErrorCounts(int $providerId): int
    {
        $count = AiConnection::byProvider($providerId)
            ->where('error_count', '>', 0)
            ->update([
                'error_count' => 0,
                'status' => 'active',
            ]);

        Log::info("Error counts reset", [
            'provider_id' => $providerId,
            'connections_reset' => $count,
        ]);

        return $count;
    }

    /**
     * Get connection statistics for rotation decisions
     *
     * @param int $providerId Provider ID
     * @return array
     */
    public function getConnectionStatistics(int $providerId): array
    {
        $connections = AiConnection::byProvider($providerId)->get();

        return [
            'total' => $connections->count(),
            'active' => $connections->where('status', 'active')->count(),
            'inactive' => $connections->where('status', 'inactive')->count(),
            'error' => $connections->where('status', 'error')->count(),
            'healthy' => $connections->where('error_count', '<', 5)->count(),
            'degraded' => $connections->where('error_count', '>=', 5)->where('error_count', '<', 10)->count(),
            'critical' => $connections->where('error_count', '>=', 10)->count(),
        ];
    }
}

