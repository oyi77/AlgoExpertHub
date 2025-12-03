<?php

namespace Addons\AiConnectionAddon\App\Http\Controllers\Api;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AiConnectionApiController extends Controller
{
    protected $connectionService;

    public function __construct(AiConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Get available connections for a provider
     *
     * @param string $provider Provider slug (openai, gemini, openrouter)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnections(string $provider)
    {
        $connections = $this->connectionService->getAvailableConnections($provider, true);

        return response()->json([
            'success' => true,
            'provider' => $provider,
            'connections' => $connections->map(function ($connection) {
                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'priority' => $connection->priority,
                    'status' => $connection->status,
                    'health_status' => $connection->health_status,
                    'success_rate' => $connection->success_rate,
                ];
            }),
        ]);
    }

    /**
     * Execute AI call
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(Request $request)
    {
        $request->validate([
            'connection_id' => 'required|integer|exists:ai_connections,id',
            'prompt' => 'required|string',
            'options' => 'nullable|array',
            'feature' => 'nullable|string',
        ]);

        try {
            $result = $this->connectionService->execute(
                connectionId: $request->connection_id,
                prompt: $request->prompt,
                options: $request->options ?? [],
                feature: $request->feature ?? 'api_call'
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test a connection
     *
     * @param AiConnection $connection
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(AiConnection $connection)
    {
        $result = $this->connectionService->testConnection($connection->id);

        return response()->json($result);
    }

    /**
     * Track usage (manual tracking if needed)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackUsage(Request $request)
    {
        $request->validate([
            'connection_id' => 'required|integer|exists:ai_connections,id',
            'feature' => 'required|string',
            'tokens_used' => 'required|integer',
            'cost' => 'required|numeric',
            'success' => 'required|boolean',
            'response_time_ms' => 'nullable|integer',
            'error_message' => 'nullable|string',
        ]);

        $this->connectionService->trackUsage($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Usage tracked successfully',
        ]);
    }
}

