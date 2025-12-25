<?php

namespace App\Http\Controllers\Backend\Traits;

use Illuminate\Http\Request;

trait HandlesSystemStatus
{
    /**
     * Get real-time system and OPcache status (AJAX endpoint - fallback)
     */
    public function getSystemStatus(Request $request)
    {
        try {
            $data = [
                'system' => $this->getSystemInfo(),
                'opcache' => $this->getOpcacheStatus(),
                'processes' => $this->getProcessInfo(),
                'horizon' => $this->getHorizonStats(),
                'queue' => $this->getQueueStats(),
                'timestamp' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Server-Sent Events stream for real-time system monitoring
     */
    public function streamSystemStatus(Request $request)
    {
        // Disable output buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable time limit
        set_time_limit(0);
        ignore_user_abort(false);

        // Send initial connection message
        echo "data: " . json_encode(['type' => 'connected', 'message' => 'System monitoring connected']) . "\n\n";
        flush();

        $updateCount = 0;

        while (true) {
            if (connection_aborted()) {
                break;
            }

            // Send keepalive every 30 seconds
            if ($updateCount % 6 == 0 && $updateCount > 0) {
                echo ": keepalive\n\n";
                flush();
            }

            try {
                $data = [
                    'type' => 'status',
                    'system' => $this->getSystemInfo(),
                    'opcache' => $this->getOpcacheStatus(),
                    'processes' => $this->getProcessInfo(),
                    'horizon' => $this->getHorizonStats(),
                    'queue' => $this->getQueueStats(),
                    'timestamp' => now()->toIso8601String(),
                ];

                echo "data: " . json_encode($data) . "\n\n";
                flush();

            } catch (\Exception $e) {
                \Log::error('SSE system status error', ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['type' => 'error', 'message' => $e->getMessage()]) . "\n\n";
                flush();
            }

            $updateCount++;

            // Update every 3 seconds
            sleep(3);
        }

        return response('', 200);
    }

    /**
     * Get comprehensive system information
     */
    protected function getSystemInfo(): array
    {
        $info = [
            'php_version' => PHP_VERSION,
            'php_binary' => defined('PHP_BINARY') ? PHP_BINARY : null,
            'application_path' => base_path(),
            'shell_exec_available' => function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions'))),
            'opcache_available' => function_exists('opcache_reset'),
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'timezone' => config('app.timezone'),
            'locale' => app()->getLocale(),
        ];

        // Server info
        $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['server_name'] = $_SERVER['SERVER_NAME'] ?? 'Unknown';
        $info['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? null;

        // Memory info
        if (function_exists('memory_get_usage')) {
            $info['memory_usage'] = memory_get_usage(true);
            $info['memory_peak'] = memory_get_peak_usage(true);
            $info['memory_limit'] = ini_get('memory_limit');
        }

        // PHP extensions
        $info['loaded_extensions'] = count(get_loaded_extensions());
        $info['important_extensions'] = [
            'pdo' => extension_loaded('pdo'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
            'zip' => extension_loaded('zip'),
            'gd' => extension_loaded('gd'),
            'imagick' => extension_loaded('imagick'),
        ];

        // Disk space
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $info['disk_free'] = disk_free_space(base_path());
            $info['disk_total'] = disk_total_space(base_path());
        }

        return $info;
    }

    /**
     * Get comprehensive OPcache status
     */
    protected function getOpcacheStatus(): array
    {
        if (!function_exists('opcache_get_status') || !function_exists('opcache_get_configuration')) {
            return ['enabled' => false];
        }

        $status = opcache_get_status();
        $config = opcache_get_configuration();

        if ($status === false) {
            return ['enabled' => false];
        }

        $memory = $status['memory_usage'] ?? [];
        $statistics = $status['opcache_statistics'] ?? [];
        $interned = $status['interned_strings_usage'] ?? [];

        $usedMemory = $memory['used_memory'] ?? 0;
        $freeMemory = $memory['free_memory'] ?? 0;
        $totalMemory = $usedMemory + $freeMemory;
        $memoryPercent = $totalMemory > 0 ? ($usedMemory / $totalMemory) * 100 : 0;

        $hits = $statistics['hits'] ?? 0;
        $misses = $statistics['misses'] ?? 0;
        $totalRequests = $hits + $misses;
        $hitRate = $totalRequests > 0 ? ($hits / $totalRequests) * 100 : 0;

        return [
            'enabled' => true,
            'memory' => [
                'used' => $usedMemory,
                'free' => $freeMemory,
                'total' => $totalMemory,
                'wasted' => $memory['wasted_memory'] ?? 0,
                'percent' => round($memoryPercent, 2),
                'used_mb' => round($usedMemory / 1024 / 1024, 2),
                'total_mb' => round($totalMemory / 1024 / 1024, 2),
            ],
            'statistics' => [
                'hits' => $hits,
                'misses' => $misses,
                'total_requests' => $totalRequests,
                'hit_rate' => round($hitRate, 2),
                'num_cached_scripts' => $statistics['num_cached_scripts'] ?? 0,
                'num_cached_keys' => $statistics['num_cached_keys'] ?? 0,
                'max_cached_keys' => $statistics['max_cached_keys'] ?? 0,
                'opcache_hit_rate' => $statistics['opcache_hit_rate'] ?? 0,
            ],
            'interned_strings' => [
                'used_memory' => $interned['used_memory'] ?? 0,
                'free_memory' => $interned['free_memory'] ?? 0,
                'number_of_strings' => $interned['number_of_strings'] ?? 0,
            ],
            'configuration' => [
                'opcache_enabled' => $config['directives']['opcache.enable'] ?? false,
                'max_accelerated_files' => $config['directives']['opcache.max_accelerated_files'] ?? 0,
                'memory_consumption' => $config['directives']['opcache.memory_consumption'] ?? 0,
                'interned_strings_buffer' => $config['directives']['opcache.interned_strings_buffer'] ?? 0,
                'max_wasted_percentage' => $config['directives']['opcache.max_wasted_percentage'] ?? 0,
            ],
            'scripts' => [
                'cached' => $statistics['num_cached_scripts'] ?? 0,
                'keys' => $statistics['num_cached_keys'] ?? 0,
                'max_keys' => $statistics['max_cached_keys'] ?? 0,
            ],
        ];
    }

    /**
     * Get process information
     */
    protected function getProcessInfo(): array
    {
        $info = [
            'php_processes' => null,
            'system_load' => null,
            'uptime' => null,
        ];

        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            try {
                // PHP processes
                $phpProcesses = shell_exec('ps aux | grep -E "[p]hp|[l]sphp" | wc -l');
                $info['php_processes'] = (int)trim($phpProcesses);

                // System load average
                if (function_exists('sys_getloadavg')) {
                    $info['system_load'] = sys_getloadavg();
                } else {
                    $load = shell_exec('uptime 2>/dev/null | awk -F\'load average:\' \'{print $2}\'');
                    if ($load) {
                        $info['system_load'] = array_map('trim', explode(',', trim($load)));
                    }
                }

                // Uptime
                $uptime = shell_exec('uptime -p 2>/dev/null || uptime 2>/dev/null');
                if ($uptime) {
                    $info['uptime'] = trim($uptime);
                }
            } catch (\Exception $e) {
                // Silent fail
            }
        }

        return $info;
    }

    /**
     * Get Horizon statistics if available
     */
    protected function getHorizonStats(): ?array
    {
        if (!class_exists(\Laravel\Horizon\Horizon::class)) {
            return null;
        }

        try {
            $horizon = app(\Laravel\Horizon\Contracts\MetricsRepository::class);
            
            return [
                'available' => true,
                'throughput' => $horizon->throughput() ?? 0,
                'wait_time' => $horizon->waitTime() ?? 0,
                'recent_jobs' => $horizon->recentJobs() ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get queue statistics from database
     */
    protected function getQueueStats(): array
    {
        try {
            $stats = [
                'pending' => \DB::table('jobs')->count(),
                'failed' => \DB::table('failed_jobs')->count(),
                'queues' => \DB::table('jobs')
                    ->select('queue', \DB::raw('count(*) as count'))
                    ->groupBy('queue')
                    ->get()
                    ->pluck('count', 'queue')
                    ->toArray(),
            ];

            return $stats;
        } catch (\Throwable $e) {
            return [
                'pending' => 0,
                'failed' => 0,
                'queues' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all cron job commands dynamically
     */
    protected function getCronJobs()
    {
        // Get PHP binary path
        $phpPath = defined('PHP_BINARY') ? PHP_BINARY : (function_exists('php_ini_loaded_file') ? exec('which php') : '/usr/bin/php');
        if (empty($phpPath) || !file_exists($phpPath)) {
            $phpPath = '/usr/bin/php'; // Fallback
        }

        // Get base path dynamically
        $basePath = base_path();
        
        // Get app URL for web-based cron jobs
        $appUrl = config('app.url', url('/'));
        
        $cronJobs = [];

        // 1. Laravel Scheduler (Main - Required)
        $cronJobs[] = [
            'title' => __('Laravel Scheduler'),
            'description' => __('Runs all scheduled tasks defined in app/Console/Kernel.php. This is the main cron job that must run every minute.'),
            'command' => "* * * * * cd {$basePath} && {$phpPath} artisan schedule:run >> /dev/null 2>&1",
            'frequency' => __('Every minute'),
            'required' => true,
            'category' => 'core'
        ];

        // 2. Queue Worker (Required if using queues)
        $cronJobs[] = [
            'title' => __('Queue Worker'),
            'description' => __('Processes queued jobs. Run this if you use queues for background tasks. For production, use Supervisor instead.'),
            'command' => "{$phpPath} {$basePath}/artisan queue:work --stop-when-empty",
            'frequency' => __('As needed (or use Supervisor)'),
            'required' => false,
            'category' => 'queue'
        ];

        // 3. Trading Interest Route (if route exists)
        try {
            if (route('trading-interest')) {
                $cronJobs[] = [
                    'title' => __('Trading Interest Calculator'),
                    'description' => __('Calculates trading interest/returns. Set frequency based on your needs.'),
                    'command' => "curl -s {$appUrl}/trading-return",
                    'frequency' => __('As needed'),
                    'required' => false,
                    'category' => 'trading'
                ];
            }
        } catch (\Exception $e) {
            // Route doesn't exist, skip
        }

        // 4. Fire Email Route (if exists)
        try {
            if (route('admin.fire')) {
                $cronJobs[] = [
                    'title' => __('Bulk Email Sender'),
                    'description' => __('Sends queued bulk emails. Usually triggered by Laravel scheduler.'),
                    'command' => "curl -s {$appUrl}/admin/fire/email",
                    'frequency' => __('As needed'),
                    'required' => false,
                    'category' => 'email'
                ];
            }
        } catch (\Exception $e) {
            // Route doesn't exist, skip
        }

        // Add scheduled tasks from Kernel.php as informational
        $scheduledTasks = $this->getScheduledTasksInfo();
        if (!empty($scheduledTasks)) {
            $cronJobs[] = [
                'title' => __('Scheduled Tasks Information'),
                'description' => __('These tasks are automatically handled by Laravel Scheduler (cron job #1 above).'),
                'tasks' => $scheduledTasks,
                'category' => 'info'
            ];
        }

        return $cronJobs;
    }

    /**
     * Get information about scheduled tasks from Kernel
     */
    protected function getScheduledTasksInfo()
    {
        $tasks = [];
        
        // Multi-Channel Signal Addon tasks
        if (\App\Support\AddonRegistry::active('multi-channel-signal-addon')) {
            $tasks[] = [
                'name' => __('Process RSS Channels'),
                'frequency' => __('Every 10 minutes'),
                'command' => 'channel:process-rss'
            ];
            $tasks[] = [
                'name' => __('Process Web Scrape Channels'),
                'frequency' => __('Every minute'),
                'command' => 'channel:process-web-scrape'
            ];
            $tasks[] = [
                'name' => __('Process Telegram MTProto Channels'),
                'frequency' => __('Every 5 minutes'),
                'command' => 'channel:process-telegram-mtproto'
            ];
            $tasks[] = [
                'name' => __('Process Trading Bot Channels'),
                'frequency' => __('Every 2 minutes'),
                'command' => 'channel:process-trading-bot'
            ];
        }

        // Trading Execution Engine Addon tasks
        if (\App\Support\AddonRegistry::active('trading-management-addon') && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'execution')) {
            $tasks[] = [
                'name' => __('Monitor Trading Positions'),
                'frequency' => __('Every minute'),
                'command' => 'MonitorPositionsJob'
            ];
            $tasks[] = [
                'name' => __('Update Trading Analytics'),
                'frequency' => __('Daily at 00:00'),
                'command' => 'UpdateAnalyticsJob'
            ];
        }

        // Smart Risk Management Addon tasks
        if (\App\Support\AddonRegistry::active('trading-management-addon') && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management')) {
            $tasks[] = [
                'name' => __('Update Performance Scores'),
                'frequency' => __('Daily at 01:00'),
                'command' => 'UpdatePerformanceScoresJob'
            ];
            $tasks[] = [
                'name' => __('Monitor Drawdown'),
                'frequency' => __('Every 5 minutes'),
                'command' => 'MonitorDrawdownJob'
            ];
            $tasks[] = [
                'name' => __('Retrain ML Models'),
                'frequency' => __('Weekly (Sunday at 03:00)'),
                'command' => 'RetrainModelsJob'
            ];
        }

        return $tasks;
    }
}
