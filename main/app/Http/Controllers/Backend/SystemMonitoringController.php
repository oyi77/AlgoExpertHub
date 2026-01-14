<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\WorkerManager;
use App\Services\Monitoring\AlertManager;
use App\Services\Monitoring\MonitoringHistory;
use App\Services\CacheManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SystemMonitoringController
 * 
 * Unified monitoring dashboard controller for system health, workers, and alerts.
 */
class SystemMonitoringController extends Controller
{
    protected SystemMonitor $systemMonitor;
    protected WorkerManager $workerManager;
    protected AlertManager $alertManager;
    protected MonitoringHistory $monitoringHistory;
    protected CacheManager $cacheManager;

    public function __construct(
        SystemMonitor $systemMonitor,
        WorkerManager $workerManager,
        AlertManager $alertManager,
        MonitoringHistory $monitoringHistory,
        CacheManager $cacheManager
    ) {
        $this->systemMonitor = $systemMonitor;
        $this->workerManager = $workerManager;
        $this->alertManager = $alertManager;
        $this->monitoringHistory = $monitoringHistory;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Display monitoring dashboard
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('backend.monitoring.index');
    }

    /**
     * Get real-time health data (AJAX endpoint)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        $cacheKey = 'monitoring:health';
        $ttl = config('monitoring.cache_ttl', 5);

        $data = Cache::remember($cacheKey, $ttl, function () {
            return [
                'system' => $this->systemMonitor->collectMetrics(),
                'workers' => $this->workerManager->getAllWorkers(),
                'alerts' => $this->alertManager->getActiveAlerts(),
                'trading_bots' => $this->getTradingBotMetrics(),
            ];
        });

        // Record snapshot for charts
        $this->monitoringHistory->recordSnapshot($data['system'], $data['workers']);

        return response()->json($data);
    }

    /**
     * Get aggregated trading bot metrics
     * 
     * @return array
     */
    protected function getTradingBotMetrics(): array
    {
        if (!class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class) || 
            !class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class)) {
            return [];
        }

        try {
            $bots = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('status', 'active')->get();
            $monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService::class);
            
            $totalOpen = 0;
            $totalPnl = 0;
            $activeCount = $bots->count();
            $totalBots = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::count();
            $errorCount = 0;
            $botDetails = [];

            foreach ($bots as $bot) {
                // Use the lightweight skipExchangeFetch=true for list view
                $stats = $monitoringService->calculatePositionStats($bot, true);
                $metrics = $monitoringService->getBotMetrics($bot);
                
                $totalOpen += $stats['total_open'] ?? 0;
                $totalPnl += $stats['total_unrealized_pnl'] ?? 0;
                $errorCount += $metrics['error_count_24h'] ?? 0;
                
                $botDetails[] = [
                    'id' => $bot->id,
                    'name' => $bot->name,
                    'symbol' => $bot->symbol,
                    'open_positions' => $stats['total_open'] ?? 0,
                    'pnl' => $stats['total_unrealized_pnl'] ?? 0,
                    'status' => $metrics['error_count_24h'] > 0 ? 'warning' : 'healthy',
                ];
            }
            
            // Sort details by PnL descending (best performers first)
            usort($botDetails, fn($a, $b) => $b['pnl'] <=> $a['pnl']);

            return [
                'active_count' => $activeCount,
                'total_count' => $totalBots,
                'total_open_positions' => $totalOpen,
                'total_unrealized_pnl' => $totalPnl,
                'total_errors_24h' => $errorCount,
                'top_bots' => array_slice($botDetails, 0, 5), // Top 5
            ];
        } catch (\Exception $e) {
            \Log::warning('Failed to aggregate bot metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get worker statuses (AJAX endpoint)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function workers()
    {
        $workers = $this->workerManager->getAllWorkers();
        
        return response()->json($workers);
    }

    /**
     * Get active alerts (AJAX endpoint)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function alerts()
    {
        $alerts = $this->alertManager->getActiveAlerts();
        
        return response()->json($alerts);
    }

    /**
     * Get historical data for charts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function history()
    {
        $history = $this->monitoringHistory->getHistory();

        return response()->json($history);
    }

    /**
     * Restart workers by type
     * 
     * @param Request $request
     * @param string $type queue|bots|octane
     * @return \Illuminate\Http\JsonResponse
     */
    public function restartWorkers(Request $request, string $type)
    {
        try {
            switch ($type) {
                case 'queue':
                    Artisan::call('queue:restart');
                    $message = 'Queue workers restarted successfully';
                    break;
                    
                case 'bots':
                    // Check if trading bot addon is active
                    if (!class_exists(\App\Support\AddonRegistry::class) || 
                        !\App\Support\AddonRegistry::active('trading-management-addon')) {
                        return response()->json([
                            'type' => 'error',
                            'message' => 'Trading bot addon is not active'
                        ], 400);
                    }
                    
                    // Restart all active bot workers
                    if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService::class)) {
                        $workerService = app(\Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService::class);
                        $bots = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('status', 'active')->get();
                        
                        foreach ($bots as $bot) {
                            if ($bot->isRunning()) {
                                $workerService->restartWorker($bot);
                            }
                        }
                        
                        $message = 'Bot workers restarted successfully';
                    } else {
                        return response()->json([
                            'type' => 'error',
                            'message' => 'Bot worker service not available'
                        ], 400);
                    }
                    break;
                    
                case 'octane':
                    if (!class_exists(\Laravel\Octane\Octane::class)) {
                        return response()->json([
                            'type' => 'error',
                            'message' => 'Laravel Octane is not installed'
                        ], 400);
                    }
                    
                    Artisan::call('octane:reload');
                    $message = 'Octane workers reloaded successfully';
                    break;
                    
                default:
                    return response()->json([
                        'type' => 'error',
                        'message' => 'Invalid worker type'
                    ], 400);
            }

            // Clear cached metrics to force refresh
            Cache::forget('monitoring:health');
            $this->alertManager->clearAlerts();

            return response()->json([
                'type' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to restart workers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all cache
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        try {
            $success = $this->cacheManager->clearAll();
            
            if ($success) {
                // Clear monitoring cache
                Cache::forget('monitoring:health');
                $this->alertManager->clearAlerts();
                
                return response()->json([
                    'type' => 'success',
                    'message' => 'All cache cleared successfully'
                ]);
            }
            
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear cache'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }
}

