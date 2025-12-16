<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\QueueOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class QueueManagementController extends Controller
{
    protected QueueOptimizer $queueOptimizer;

    public function __construct(QueueOptimizer $queueOptimizer)
    {
        $this->queueOptimizer = $queueOptimizer;
    }

    /**
     * Display queue management dashboard
     */
    public function index()
    {
        $metrics = $this->queueOptimizer->getMetrics();
        
        return view('backend.queue.index', compact('metrics'));
    }

    /**
     * Get queue health data for AJAX requests
     */
    public function health()
    {
        $health = $this->queueOptimizer->monitorHealth();
        
        return response()->json($health);
    }

    /**
     * Get detailed metrics for AJAX requests
     */
    public function metrics()
    {
        $metrics = $this->queueOptimizer->getMetrics();
        
        return response()->json($metrics);
    }

    /**
     * Scale queue workers
     */
    public function scale(Request $request)
    {
        $request->validate([
            'workers' => 'required|integer|min:1|max:20'
        ]);

        $success = $this->queueOptimizer->scaleWorkers($request->workers);
        
        if ($success) {
            return response()->json([
                'type' => 'success',
                'message' => "Successfully scaled to {$request->workers} workers"
            ]);
        }
        
        return response()->json([
            'type' => 'error',
            'message' => 'Failed to scale workers'
        ], 500);
    }

    /**
     * Clear queue metrics
     */
    public function clearMetrics()
    {
        try {
            Artisan::call('queue:manage', ['action' => 'clear']);
            
            return response()->json([
                'type' => 'success',
                'message' => 'Queue metrics cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Failed to clear metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restart()
    {
        try {
            Artisan::call('queue:restart');
            
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
     * Get queue statistics for charts
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', '24h');
        
        // This would typically fetch from a time-series database
        // For now, return sample data structure
        $statistics = [
            'throughput' => $this->getThroughputData($period),
            'response_times' => $this->getResponseTimeData($period),
            'error_rates' => $this->getErrorRateData($period),
            'worker_utilization' => $this->getWorkerUtilizationData($period)
        ];
        
        return response()->json($statistics);
    }

    /**
     * Get throughput data for charts
     */
    protected function getThroughputData(string $period): array
    {
        // Sample implementation - replace with actual data retrieval
        $data = [];
        $now = now();
        
        for ($i = 23; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);
            $data[] = [
                'timestamp' => $timestamp->toISOString(),
                'jobs_processed' => rand(50, 200),
                'jobs_failed' => rand(0, 10)
            ];
        }
        
        return $data;
    }

    /**
     * Get response time data for charts
     */
    protected function getResponseTimeData(string $period): array
    {
        $data = [];
        $now = now();
        
        for ($i = 23; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);
            $data[] = [
                'timestamp' => $timestamp->toISOString(),
                'avg_response_time' => rand(50, 150),
                'p95_response_time' => rand(100, 300),
                'p99_response_time' => rand(200, 500)
            ];
        }
        
        return $data;
    }

    /**
     * Get error rate data for charts
     */
    protected function getErrorRateData(string $period): array
    {
        $data = [];
        $now = now();
        
        for ($i = 23; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);
            $data[] = [
                'timestamp' => $timestamp->toISOString(),
                'error_rate' => rand(0, 5) / 100 // 0-5% error rate
            ];
        }
        
        return $data;
    }

    /**
     * Get worker utilization data for charts
     */
    protected function getWorkerUtilizationData(string $period): array
    {
        $data = [];
        $now = now();
        
        for ($i = 23; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i);
            $data[] = [
                'timestamp' => $timestamp->toISOString(),
                'active_workers' => rand(2, 8),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 70)
            ];
        }
        
        return $data;
    }
}