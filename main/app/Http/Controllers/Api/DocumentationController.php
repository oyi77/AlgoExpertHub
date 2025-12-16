<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

class DocumentationController extends BaseApiController
{
    /**
     * Get API documentation overview.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $documentation = [
            'api' => [
                'name' => config('app.name') . ' API',
                'version' => config('app.api_version'),
                'description' => 'RESTful API for the AlgoExpertHub Trading Signal Platform',
                'base_url' => config('app.url') . '/api',
                'documentation_url' => config('app.url') . '/api/docs',
            ],
            'authentication' => [
                'type' => 'Bearer Token (Laravel Sanctum)',
                'header' => 'Authorization: Bearer {token}',
                'endpoints' => [
                    'login' => 'POST /api/auth/login',
                    'register' => 'POST /api/auth/register',
                    'logout' => 'POST /api/auth/logout',
                ],
            ],
            'versioning' => [
                'current_version' => config('app.api_versioning.current'),
                'supported_versions' => config('app.api_versioning.supported'),
                'deprecated_versions' => config('app.api_versioning.deprecated'),
                'header_name' => config('app.api_versioning.header_name'),
                'example' => 'Accept-Version: v1',
            ],
            'response_format' => [
                'success' => [
                    'success' => true,
                    'message' => 'Success message',
                    'data' => '...',
                    'meta' => [
                        'timestamp' => '2024-01-01T00:00:00.000000Z',
                        'version' => 'v1',
                    ],
                ],
                'error' => [
                    'success' => false,
                    'message' => 'Error message',
                    'errors' => '...',
                ],
                'pagination' => [
                    'success' => true,
                    'message' => 'Success message',
                    'data' => '...',
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 10,
                        'per_page' => 20,
                        'total' => 200,
                        'from' => 1,
                        'to' => 20,
                        'has_more_pages' => true,
                    ],
                ],
            ],
            'http_status_codes' => [
                200 => 'OK - Request successful',
                201 => 'Created - Resource created successfully',
                204 => 'No Content - Request successful, no content to return',
                400 => 'Bad Request - Invalid request parameters',
                401 => 'Unauthorized - Authentication required',
                403 => 'Forbidden - Access denied',
                404 => 'Not Found - Resource not found',
                422 => 'Unprocessable Entity - Validation failed',
                429 => 'Too Many Requests - Rate limit exceeded',
                500 => 'Internal Server Error - Server error',
            ],
            'rate_limiting' => [
                'api_limit' => '60 requests per minute',
                'headers' => [
                    'X-RateLimit-Limit' => 'Request limit',
                    'X-RateLimit-Remaining' => 'Remaining requests',
                    'X-RateLimit-Reset' => 'Reset timestamp',
                ],
            ],
        ];

        return $this->successResponse($documentation, 'API documentation retrieved successfully');
    }

    /**
     * Get available API endpoints.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function endpoints(Request $request): JsonResponse
    {
        $routes = collect(Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->uri(), 'api/');
            })
            ->map(function ($route) {
                return [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware(),
                ];
            })
            ->groupBy(function ($route) {
                $parts = explode('/', $route['uri']);
                return isset($parts[2]) ? $parts[2] : 'root';
            })
            ->toArray();

        return $this->successResponse($routes, 'API endpoints retrieved successfully');
    }

    /**
     * Get API health status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function health(Request $request): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.api_version'),
            'environment' => config('app.env'),
            'services' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'queue' => $this->checkQueueHealth(),
            ],
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ],
        ];

        $overallStatus = collect($health['services'])->every(fn($status) => $status === 'healthy');
        $health['status'] = $overallStatus ? 'healthy' : 'degraded';

        return $this->successResponse($health, 'API health status retrieved successfully');
    }

    /**
     * Check database health.
     *
     * @return string
     */
    private function checkDatabaseHealth(): string
    {
        try {
            \DB::connection()->getPdo();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check cache health.
     *
     * @return string
     */
    private function checkCacheHealth(): string
    {
        try {
            \Cache::put('health_check', 'test', 1);
            $value = \Cache::get('health_check');
            return $value === 'test' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check queue health.
     *
     * @return string
     */
    private function checkQueueHealth(): string
    {
        try {
            // Simple check - if we can access queue configuration
            config('queue.default');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
}