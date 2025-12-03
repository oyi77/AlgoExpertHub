<?php

namespace Addons\AiConnectionAddon\App\Services;

use Addons\AiConnectionAddon\App\Models\AiProvider;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Models\AiConnectionUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiConnectionService
{
    protected $rotationService;
    protected $adapterFactory;

    public function __construct(
        ConnectionRotationService $rotationService,
        ProviderAdapterFactory $adapterFactory
    ) {
        $this->rotationService = $rotationService;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Get available connections for a provider
     *
     * @param string $providerSlug Provider slug (openai, gemini, openrouter)
     * @param bool $activeOnly Only return active connections
     * @return Collection
     */
    public function getAvailableConnections(string $providerSlug, bool $activeOnly = true): Collection
    {
        $provider = AiProvider::bySlug($providerSlug)->first();

        if (!$provider) {
            Log::warning("Provider not found: {$providerSlug}");
            return collect([]);
        }

        $query = $provider->connections();

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        return $query->orderBy('priority')->get();
    }

    /**
     * Get next available connection with rotation logic
     *
     * @param int $providerId Provider ID
     * @return AiConnection|null
     */
    public function getNextConnection(int $providerId): ?AiConnection
    {
        return $this->rotationService->getNextConnection($providerId);
    }

    /**
     * Execute AI call with automatic rotation on failure
     *
     * @param int $connectionId Connection ID
     * @param string $prompt Prompt to send to AI
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @param string $feature Feature name for tracking (translation, parsing, market_analysis)
     * @return array Result with response, tokens_used, cost, connection_used
     */
    public function execute(
        int $connectionId,
        string $prompt,
        array $options = [],
        string $feature = 'general'
    ): array {
        $startTime = microtime(true);
        $connection = AiConnection::find($connectionId);

        if (!$connection) {
            throw new \Exception("Connection not found: {$connectionId}");
        }

        // Check if rate limited
        if ($connection->isRateLimited()) {
            Log::warning("Connection rate limited, attempting rotation", [
                'connection_id' => $connectionId,
                'connection_name' => $connection->name,
            ]);

            // Try to get alternative connection
            $alternativeConnection = $this->rotationService->getNextConnection($connection->provider_id, $connectionId);

            if ($alternativeConnection) {
                $connection = $alternativeConnection;
                Log::info("Rotated to connection: {$connection->name}");
            } else {
                throw new \Exception("All connections are rate limited or unavailable");
            }
        }

        try {
            // Get adapter for this provider
            $adapter = $this->adapterFactory->make($connection->provider->slug);

            // Execute AI call
            $result = $adapter->execute($connection, $prompt, $options);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // Record success
            $connection->recordSuccess();

            // Track usage
            $this->trackUsage([
                'connection_id' => $connection->id,
                'feature' => $feature,
                'tokens_used' => $result['tokens_used'] ?? 0,
                'cost' => $result['cost'] ?? 0,
                'success' => true,
                'response_time_ms' => $responseTime,
            ]);

            return [
                'success' => true,
                'response' => $result['response'],
                'tokens_used' => $result['tokens_used'] ?? 0,
                'cost' => $result['cost'] ?? 0,
                'connection_id' => $connection->id,
                'connection_name' => $connection->name,
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // Record error
            $connection->recordError($e->getMessage());

            // Track failed usage
            $this->trackUsage([
                'connection_id' => $connection->id,
                'feature' => $feature,
                'tokens_used' => 0,
                'cost' => 0,
                'success' => false,
                'response_time_ms' => $responseTime,
                'error_message' => $e->getMessage(),
            ]);

            // Try rotation if available
            if ($this->shouldAttemptRotation($e)) {
                Log::warning("Attempting connection rotation after error", [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                ]);

                $alternativeConnection = $this->rotationService->getNextConnection($connection->provider_id, $connectionId);

                if ($alternativeConnection) {
                    // Recursive call with alternative connection
                    return $this->execute($alternativeConnection->id, $prompt, $options, $feature);
                }
            }

            throw $e;
        }
    }

    /**
     * Test a connection
     *
     * @param int $connectionId Connection ID
     * @return array Result with success, message, response_time_ms
     */
    public function testConnection(int $connectionId): array
    {
        $startTime = microtime(true);
        $connection = AiConnection::find($connectionId);

        if (!$connection) {
            return [
                'success' => false,
                'message' => 'Connection not found',
            ];
        }

        try {
            $adapter = $this->adapterFactory->make($connection->provider->slug);
            $result = $adapter->test($connection);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $connection->recordSuccess();
            }

            return [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Connection test successful',
                'response_time_ms' => $responseTime,
                'provider' => $connection->provider->name,
                'connection_name' => $connection->name,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $connection->recordError($e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'response_time_ms' => $responseTime,
                'provider' => $connection->provider->name,
                'connection_name' => $connection->name,
            ];
        }
    }

    /**
     * Track usage
     *
     * @param array $data Usage data
     * @return void
     */
    public function trackUsage(array $data): void
    {
        try {
            AiConnectionUsage::log(
                connectionId: $data['connection_id'],
                feature: $data['feature'] ?? 'general',
                tokensUsed: $data['tokens_used'] ?? 0,
                cost: $data['cost'] ?? 0,
                success: $data['success'] ?? true,
                responseTimeMs: $data['response_time_ms'] ?? null,
                errorMessage: $data['error_message'] ?? null
            );
        } catch (\Exception $e) {
            Log::error('Failed to track usage', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Get connection by ID
     *
     * @param int $connectionId
     * @return AiConnection|null
     */
    public function getConnection(int $connectionId): ?AiConnection
    {
        return AiConnection::find($connectionId);
    }

    /**
     * Get provider by slug
     *
     * @param string $slug
     * @return AiProvider|null
     */
    public function getProvider(string $slug): ?AiProvider
    {
        return AiProvider::bySlug($slug)->first();
    }

    /**
     * Determine if we should attempt rotation based on error
     *
     * @param \Exception $e
     * @return bool
     */
    protected function shouldAttemptRotation(\Exception $e): bool
    {
        $errorMessage = strtolower($e->getMessage());

        // Rotate on rate limit errors
        if (str_contains($errorMessage, 'rate limit') || 
            str_contains($errorMessage, 'too many requests') ||
            str_contains($errorMessage, '429')) {
            return true;
        }

        // Rotate on service unavailable
        if (str_contains($errorMessage, 'service unavailable') ||
            str_contains($errorMessage, '503') ||
            str_contains($errorMessage, 'timeout')) {
            return true;
        }

        return false;
    }

    /**
     * Get usage statistics
     *
     * @param int|null $connectionId Optional connection ID to filter
     * @param int $days Number of days to look back
     * @return array
     */
    public function getUsageStatistics(?int $connectionId = null, int $days = 30): array
    {
        return [
            'total_cost' => AiConnectionUsage::getTotalCost($connectionId, $days),
            'total_tokens' => AiConnectionUsage::getTotalTokens($connectionId, $days),
            'by_feature' => AiConnectionUsage::getUsageByFeature($days),
            'avg_response_time' => AiConnectionUsage::getAverageResponseTime($connectionId, $days),
            'period_days' => $days,
        ];
    }
}

