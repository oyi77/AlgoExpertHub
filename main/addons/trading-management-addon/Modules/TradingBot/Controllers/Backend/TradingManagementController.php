<?php

namespace Addons\TradingManagement\Modules\TradingBot\Controllers\Backend;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotMonitoringService;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotWorkerService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * TradingManagementController
 * 
 * System-wide monitoring and health dashboard
 */
class TradingManagementController extends Controller
{
    protected TradingBotMonitoringService $monitoringService;
    protected TradingBotWorkerService $workerService;

    public function __construct(
        TradingBotMonitoringService $monitoringService,
        TradingBotWorkerService $workerService
    ) {
        $this->monitoringService = $monitoringService;
        $this->workerService = $workerService;
    }

    /**
     * System health overview
     */
    public function systemHealth(): View
    {
        $data['title'] = 'Trading System Health';

        // Get all trading bots
        $allBots = TradingBot::all();
        $runningBots = TradingBot::where('status', 'running')->get();
        
        // Worker status summary
        $workerStats = [
            'total' => $allBots->count(),
            'running' => 0,
            'dead' => 0,
            'stopped' => 0,
            'paused' => 0,
        ];

        foreach ($runningBots as $bot) {
            $status = $this->workerService->getWorkerStatus($bot);
            if ($status === 'running') {
                $workerStats['running']++;
            } elseif ($status === 'dead') {
                $workerStats['dead']++;
            }
        }

        $workerStats['stopped'] = TradingBot::where('status', 'stopped')->count();
        $workerStats['paused'] = TradingBot::where('status', 'paused')->count();

        // Queue workers status (check supervisor)
        $queueWorkersRunning = false;
        $queueWorkersCount = 0;
        try {
            $output = shell_exec("ps aux | grep 'queue:work' | grep -v grep | wc -l 2>&1");
            $queueWorkersCount = (int) trim($output ?? '0');
            $queueWorkersRunning = $queueWorkersCount > 0;
        } catch (\Exception $e) {
            // Ignore
        }

        // MetaAPI stream workers
        $metaapiWorkersCount = 0;
        try {
            $output = shell_exec("ps aux | grep 'metaapi:stream-worker' | grep -v grep | wc -l 2>&1");
            $metaapiWorkersCount = (int) trim($output ?? '0');
        } catch (\Exception $e) {
            // Ignore
        }

        // Exchange connections health
        $connections = ExchangeConnection::all();
        $connectionStats = [
            'total' => $connections->count(),
            'active' => $connections->where('status', 'active')->count(),
            'error' => $connections->where('status', 'error')->count(),
            'testing' => $connections->where('status', 'testing')->count(),
            'inactive' => $connections->where('status', 'inactive')->count(),
        ];

        // Queue statistics
        $queueStats = $this->monitoringService->getQueueStats();

        // Scheduled jobs status (check if scheduler is running)
        $schedulerRunning = false;
        try {
            $output = shell_exec("ps aux | grep 'schedule:run' | grep -v grep 2>&1");
            $schedulerRunning = !empty(trim($output ?? ''));
        } catch (\Exception $e) {
            // Check cron
            $output = shell_exec("crontab -l 2>/dev/null | grep schedule:run");
            $schedulerRunning = !empty(trim($output ?? ''));
        }

        // System metrics
        $systemMetrics = [
            'queue_size' => $queueStats['pending'] ?? 0,
            'failed_jobs' => $queueStats['failed'] ?? 0,
            'processing_jobs' => $queueStats['processing'] ?? 0,
        ];

        // Get recent bot activity
        $recentActivity = TradingBot::with(['user', 'admin'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Pre-calculate worker status for all bots
        $botWorkerStatuses = [];
        foreach ($allBots as $bot) {
            $botWorkerStatuses[$bot->id] = $this->workerService->getWorkerStatus($bot);
        }

        $data['workerStats'] = $workerStats;
        $data['queueWorkersRunning'] = $queueWorkersRunning;
        $data['queueWorkersCount'] = $queueWorkersCount;
        $data['metaapiWorkersCount'] = $metaapiWorkersCount;
        $data['connectionStats'] = $connectionStats;
        $data['queueStats'] = $queueStats;
        $data['schedulerRunning'] = $schedulerRunning;
        $data['systemMetrics'] = $systemMetrics;
        $data['recentActivity'] = $recentActivity;
        $data['allBots'] = $allBots;
        $data['botWorkerStatuses'] = $botWorkerStatuses;

        return view('trading-management::backend.system-health', $data);
    }
}
