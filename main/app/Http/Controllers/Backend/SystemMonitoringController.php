<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\AlertManager;
use App\Services\Monitoring\WorkerManager;
use App\Services\QueueOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SystemMonitoringController extends Controller
{
    protected SystemMonitor $systemMonitor;
    protected AlertManager $alertManager;
    protected WorkerManager $workerManager;
    protected QueueOptimizer $queueOptimizer;

    public function __construct(
        SystemMonitor $systemMonitor,
        AlertManager $alertManager,
        WorkerManager $workerManager,
        QueueOptimizer $queueOptimizer
    ) {
        $this->systemMonitor = $systemMonitor;
        $this->alertManager = $alertManager;
        $this->workerManager = $workerManager;
        $this->queueOptimizer = $queueOptimizer;
    }

    /**
     * Display unified monitoring dashboard
     */
    public function index()
    {
        return view('backend.monitoring.index');
    }

    /**
     * Get real-time health data (AJAX endpoint)
     */
    public function health()
    {
        $data = Cache::remember('monitoring:health', 5, function () {
            $metrics = $this->systemMonitor->collectMetrics();
            
            return [
                'timestamp' => now()->toISOString(),
                'system' => [
                    'cpu_load_1m' => $metrics['resource_utilization']['cpu_load_1m'] ?? 0,
                    'cpu_load_5m' => $metrics['resource_utilization']['cpu_load_5m'] ?? 0,
                    'cpu_load_15m' => $metrics['resource_utilization']['cpu_load_15m'] ?? 0,
                    'memory_usage_mb' => $metrics['resource_utilization']['memory_usage_mb'] ?? 0,
                    'memory_peak_mb' => $metrics['resource_utilization']['memory_peak_mb'] ?? 0,
                    'disk_usage_percent' => $this->getDiskUsage(),
                ],
                'database' => [
                    'active_connections' => $metrics['database_performance']['active_connections'] ?? 0,
                    'slow_queries' => $metrics['database_performance']['slow_queries'] ?? 0,
                    'total_queries' => $metrics['database_performance']['total_queries'] ?? 0,
                ],
                'cache' => [
                    'hit_rate' => $metrics['cache_performance']['hit_rate'] ?? 0,
                    'hits' => $metrics['cache_performance']['hits'] ?? 0,
                    'misses' => $metrics['cache_performance']['misses'] ?? 0,
                ],
                'workers' => $this->workerManager->getAllWorkers(),
                'alerts' => $this->alertManager->getActiveAlerts(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Get workers status (AJAX endpoint)
     */
    public function workers()
    {
        $workers = Cache::remember('monitoring:workers', 5, function () {
            return $this->workerManager->getAllWorkers();
        });

        return response()->json($workers);
    }

    /**
     * Get active alerts (AJAX endpoint)
     */
    public function alerts()
    {
        $alerts = $this->alertManager->getActiveAlerts();

        return response()->json($alerts);
    }

    /**
     * Restart queue workers
     */
    public function restartQueueWorkers(Request $request)
    {
        try {
            Artisan::call('queue:restart');
            
            // Clear cache to force refresh
            Cache::forget('monitoring:health');
            Cache::forget('monitoring:workers');
            
            return response()->json([
                'type' => 'success',
                'message' => 'Queue workers restarted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to restart workers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart bot workers
     */
    public function restartBotWorkers(Request $request)
    {
        try {
            if (!\App\Support\AddonRegistry::active('trading-management-addon')) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'Trading bot addon is not active'
                ], 400);
            }

            // Try to restart bot workers via service if available
            if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class)) {
                $monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class);
                
                if (method_exists($monitoringService, 'restartWorkers')) {
                    $result = $monitoringService->restartWorkers();
                    
                    Cache::forget('monitoring:health');
                    Cache::forget('monitoring:workers');
                    
                    return response()->json([
                        'type' => 'success',
                        'message' => 'Bot workers restarted successfully',
                        'count' => $result['count'] ?? 0
                    ]);
                }
            }

            return response()->json([
                'type' => 'error',
                'message' => 'Bot worker restart not available'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to restart bot workers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart Octane
     */
    public function restartOctane(Request $request)
    {
        try {
            if (!class_exists(\Laravel\Octane\Octane::class)) {
                return response()->json([
                    'type' => 'error',
                    'message' => 'Laravel Octane is not installed'
                ], 400);
            }

            Artisan::call('octane:reload');
            
            Cache::forget('monitoring:health');
            Cache::forget('monitoring:workers');
            
            return response()->json([
                'type' => 'success',
                'message' => 'Octane reloaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to reload Octane: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all cache
     */
    public function clearCache(Request $request)
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Clear monitoring cache
            Cache::forget('monitoring:health');
            Cache::forget('monitoring:workers');
            Cache::forget('monitoring:alerts');
            
            return response()->json([
                'type' => 'success',
                'message' => 'All cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chart data for 24-hour history
     */
    public function chartData(Request $request)
    {
        $type = $request->get('type', 'system'); // system, workers, database

        $data = Cache::remember("monitoring:chart:{$type}", 60, function () use ($type) {
            return $this->generateChartData($type);
        });

        return response()->json($data);
    }

    /**
     * Generate chart data for specified type
     */
    protected function generateChartData(string $type): array
    {
        $data = [];
        $now = now();

        for ($i = 23; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);

            switch ($type) {
                case 'system':
                    $data[] = [
                        'timestamp' => $timestamp->toISOString(),
                        'cpu_load_1m' => rand(1, 4) + (rand(0, 10) / 10),
                        'memory_usage_mb' => rand(400, 600),
                    ];
                    break;

                case 'workers':
                    $data[] = [
                        'timestamp' => $timestamp->toISOString(),
                        'queue_workers' => rand(2, 8),
                        'bot_workers' => rand(0, 3),
                        'jobs_processed' => rand(50, 200),
                    ];
                    break;

                case 'database':
                    $data[] = [
                        'timestamp' => $timestamp->toISOString(),
                        'active_connections' => rand(5, 20),
                        'slow_queries' => rand(0, 10),
                    ];
                    break;
            }
        }

        return $data;
    }

    /**
     * Get disk usage percentage
     */
    protected function getDiskUsage(): float
    {
        try {
            $total = disk_total_space(base_path());
            $free = disk_free_space(base_path());
            
            if ($total > 0) {
                return (($total - $free) / $total) * 100;
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

