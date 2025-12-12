<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - System Tools
 *
 * Endpoints for system monitoring and management (AlgoExpert++).
 */
class SystemToolsApiController extends Controller
{
    /**
     * System Health Check
     */
    public function health()
    {
        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        return response()->json(['success' => true, 'data' => $health]);
    }

    /**
     * Performance Status
     */
    public function performance()
    {
        $stats = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_load' => sys_getloadavg(),
            'disk_free' => disk_free_space('/'),
            'disk_total' => disk_total_space('/'),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * Clear Cache
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return response()->json(['success' => true, 'message' => 'Cache cleared successfully']);
    }

    /**
     * Optimize Application
     */
    public function optimize()
    {
        Artisan::call('optimize');
        Artisan::call('config:cache');
        Artisan::call('route:cache');

        return response()->json(['success' => true, 'message' => 'Application optimized']);
    }

    /**
     * List Cron Jobs
     */
    public function cronJobs()
    {
        // Get scheduled tasks
        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $events = collect($schedule->events())->map(function ($event) {
            return [
                'command' => $event->command ?? $event->description,
                'expression' => $event->expression,
                'timezone' => $event->timezone,
            ];
        });

        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * Horizon Stats
     */
    public function horizonStats()
    {
        if (!class_exists(\Laravel\Horizon\Contracts\MetricsRepository::class)) {
            return response()->json(['success' => false, 'message' => 'Horizon not installed'], 503);
        }

        $stats = [
            'jobs_per_minute' => app(\Laravel\Horizon\Contracts\MetricsRepository::class)->jobsProcessedPerMinute(),
            'recent_jobs' => app(\Laravel\Horizon\Contracts\JobRepository::class)->getRecent(),
            'failed_jobs' => app(\Laravel\Horizon\Contracts\FailedJobRepository::class)->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    /**
     * Clear Failed Jobs
     */
    public function clearFailedJobs()
    {
        if (!class_exists(\Laravel\Horizon\Contracts\FailedJobRepository::class)) {
            return response()->json(['success' => false, 'message' => 'Horizon not installed'], 503);
        }

        app(\Laravel\Horizon\Contracts\FailedJobRepository::class)->flush();

        return response()->json(['success' => true, 'message' => 'Failed jobs cleared']);
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkCache()
    {
        try {
            cache()->put('health_check', true, 10);
            $result = cache()->get('health_check');
            return ['status' => $result ? 'healthy' : 'unhealthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage()
    {
        $writable = is_writable(storage_path());
        return ['status' => $writable ? 'healthy' : 'unhealthy', 'writable' => $writable];
    }

    private function checkQueue()
    {
        try {
            $size = DB::table('jobs')->count();
            return ['status' => 'healthy', 'pending_jobs' => $size];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'message' => $e->getMessage()];
        }
    }
}
