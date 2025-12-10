<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationRequest;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin APIs
 * Configuration management endpoints
 */
class ConfigurationController extends Controller
{
    protected $config;

    public function __construct(ConfigurationService $config)
    {
        $this->config = $config;
    }

    /**
     * Get Configuration
     * 
     * Get system configuration
     * 
     * @return JsonResponse
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "site_name": "AlgoExpertHub",
     *     "registration": 1,
     *     "email_verification": 1,
     *     ...
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        $configuration = Configuration::first();

        return response()->json([
            'success' => true,
            'data' => $configuration
        ]);
    }

    /**
     * Update Configuration
     * 
     * Update system configuration
     * 
     * @param ConfigurationRequest $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam site_name string required Site name. Example: AlgoExpertHub
     * @bodyParam registration integer Registration enabled (0 or 1). Example: 1
     * @bodyParam email_verification integer Email verification enabled (0 or 1). Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Configuration updated successfully"
     * }
     */
    public function update(ConfigurationRequest $request): JsonResponse
    {
        $isSuccess = $this->config->update($request);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to update configuration'
        ], 400);
    }
}
