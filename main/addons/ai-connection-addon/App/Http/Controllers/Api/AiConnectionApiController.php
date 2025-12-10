<?php

namespace Addons\AiConnectionAddon\App\Http\Controllers\Api;

use Addons\AiConnectionAddon\App\Models\AiConnection;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @group AI Connection
 * Endpoints for managing and using AI connections.
 * 
 * These endpoints allow consumer addons to interact with AI connections
 * for executing AI calls, testing connections, and tracking usage.
 * @authenticated
 */
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
     * @urlParam provider string required The provider key. Example: openai
     * @response 200 {
     *   "success": true,
     *   "provider": "openai",
     *   "connections": [
     *     {"id": 1, "name": "Primary OpenAI", "priority": 10, "status": "active", "health_status": "healthy", "success_rate": 0.99}
     *   ]
     * }
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
     * @bodyParam connection_id integer required The connection ID to use. Example: 1
     * @bodyParam prompt string required The prompt to send to the model. Example: Write a haiku about markets
     * @bodyParam options object Optional options such as model or temperature. Example: {"model":"gpt-4o-mini","temperature":0.2}
     * @bodyParam feature string Optional feature tag for tracking. Example: api_call
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "output": "Calm waves of trade\nCharts whisper in silent code\nProfit blooms or fades"
     *   }
     * }
     * @response 422 {"message":"The given data was invalid.","errors":{"connection_id":["The connection id field is required."]}}
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
     * @urlParam connection integer required The connection ID. Example: 1
     * @response 200 {"status":"ok","latency_ms":120,"healthy":true}
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
     * @bodyParam connection_id integer required The connection ID. Example: 1
     * @bodyParam feature string required Feature tag. Example: api_call
     * @bodyParam tokens_used integer required Tokens used. Example: 153
     * @bodyParam cost number required Cost in USD. Example: 0.0025
     * @bodyParam success boolean required Whether the call succeeded. Example: true
     * @bodyParam response_time_ms integer Response time in ms. Example: 120
     * @bodyParam error_message string Error message if any.
     * @response 200 {"success":true,"message":"Usage tracked successfully"}
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
