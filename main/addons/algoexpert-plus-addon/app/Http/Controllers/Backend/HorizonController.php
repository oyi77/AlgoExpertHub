<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Jobs\TestJob;
use Addons\AlgoExpertPlus\App\Services\SystemHealthService;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class HorizonController extends Controller
{
    protected $systemHealthService;

    public function __construct(SystemHealthService $systemHealthService)
    {
        $this->systemHealthService = $systemHealthService;
    }

    /**
     * Show embedded Horizon dashboard
     */
    public function index(): View
    {
        // Check if Horizon route exists
        if (!Route::has('horizon.index')) {
            abort(404, 'Horizon is not available');
        }

        $horizonUrl = route('horizon.index');
        
        // Get Horizon stats
        $horizonStats = $this->systemHealthService->getHorizonStats();
        $horizonSupervisorStatus = $this->systemHealthService->getHorizonSupervisorStatus();
        $queueStats = $this->systemHealthService->getQueueStats();
        $queueDiagnostics = $this->systemHealthService->getQueueDiagnostics();
        
        // Determine if Horizon is installed and available
        $isAvailable = class_exists(\Laravel\Horizon\Horizon::class);
        $isRunning = $horizonStats && ($horizonStats['active'] ?? false);
        
        return view('algoexpert-plus::backend.horizon.embedded', [
            'title' => 'Horizon Queue Dashboard',
            'horizonUrl' => $horizonUrl,
            'horizonStats' => $horizonStats,
            'horizonSupervisorStatus' => $horizonSupervisorStatus,
            'queueStats' => $queueStats,
            'queueDiagnostics' => $queueDiagnostics,
            'isAvailable' => $isAvailable,
            'isRunning' => $isRunning,
        ]);
    }

    /**
     * Dispatch a test job to verify queue is working
     */
    public function testJob()
    {
        try {
            $queueConnection = config('queue.default', 'sync');
            
            if ($queueConnection === 'sync') {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue connection is set to "sync" - jobs run immediately and won\'t appear in Horizon. Set QUEUE_CONNECTION=redis in .env',
                ], 400);
            }

            // Dispatch the test job
            TestJob::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Test job dispatched successfully! Check Horizon dashboard - it should appear within a few seconds.',
                'queue_connection' => $queueConnection,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch test job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all failed jobs from database
     */
    public function clearFailedJobs()
    {
        try {
            $count = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            
            \Illuminate\Support\Facades\DB::table('failed_jobs')->truncate();
            
            \Log::info('Failed jobs cleared', [
                'count' => $count,
                'admin_id' => auth()->guard('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully cleared {$count} failed job(s) from database.",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to clear failed jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear failed jobs: ' . $e->getMessage(),
            ], 500);
        }
    }
}
