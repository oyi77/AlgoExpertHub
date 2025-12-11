<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Backend\ConfigurationController as WebConfigController;

class SystemManagementController extends Controller
{
    protected $webController;

    public function __construct()
    {
        $this->webController = new WebConfigController();
    }

    /**
     * Get system status
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $status = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'disk_space' => $this->checkDiskSpace(),
                'memory' => $this->checkMemory(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize performance
     */
    public function optimize(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceOptimize($request);

            return response()->json([
                'success' => true,
                'message' => 'Performance optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize assets
     */
    public function optimizeAssets(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceAssets($request);

            return response()->json([
                'success' => true,
                'message' => 'Assets optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize assets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize HTTP
     */
    public function optimizeHttp(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceHttp($request);

            return response()->json([
                'success' => true,
                'message' => 'HTTP optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize HTTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize media
     */
    public function optimizeMedia(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceMedia($request);

            return response()->json([
                'success' => true,
                'message' => 'Media optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize media: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize cache
     */
    public function optimizeCache(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceCache($request);

            return response()->json([
                'success' => true,
                'message' => 'Cache optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase(Request $request): JsonResponse
    {
        try {
            $this->webController->performanceDatabase($request);

            return response()->json([
                'success' => true,
                'message' => 'Database optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prewarm cache
     */
    public function prewarmCache(Request $request): JsonResponse
    {
        try {
            $this->webController->performancePrewarm($request);

            return response()->json([
                'success' => true,
                'message' => 'Cache prewarmed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prewarm cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(Request $request): JsonResponse
    {
        try {
            $this->webController->createBackup($request);

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load database backup
     */
    public function loadBackup(Request $request): JsonResponse
    {
        try {
            $this->webController->loadBackup($request);

            return response()->json([
                'success' => true,
                'message' => 'Backup loaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete database backup
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        try {
            $this->webController->deleteBackup($request);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reseed database
     */
    public function reseedDatabase(Request $request): JsonResponse
    {
        try {
            $this->webController->reseedDatabase($request);

            return response()->json([
                'success' => true,
                'message' => 'Database reseeded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reseed database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset database
     */
    public function resetDatabase(Request $request): JsonResponse
    {
        try {
            $this->webController->resetDatabase($request);

            return response()->json([
                'success' => true,
                'message' => 'Database reset successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset database: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'connected', 'driver' => config('database.default')];
        } catch (\Exception $e) {
            return ['status' => 'disconnected', 'error' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            $driver = config('cache.default');
            \Cache::put('health_check', 'ok', 10);
            $value = \Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'working' : 'error', 'driver' => $driver];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $size = \Queue::size();
            return ['status' => 'ok', 'pending_jobs' => $size];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    protected function checkDiskSpace(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percent = ($used / $total) * 100;

        return [
            'total' => $total,
            'free' => $free,
            'used' => $used,
            'percent_used' => round($percent, 2)
        ];
    }

    protected function checkMemory(): array
    {
        return [
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ];
    }
}

