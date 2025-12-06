<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationRequest;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use App\Services\DatabaseBackupService;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Throwable;

class ConfigurationController extends Controller
{
    protected $config;
    protected $themeManager;
    protected $backupService;

    public function __construct(ConfigurationService $config, ThemeManager $themeManager, DatabaseBackupService $backupService)
    {
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->backupService = $backupService;
    }

    public function index()
    {
        $data['title'] = 'Application Settings';

        $data['general'] = Configuration::first();

        $data['timezone'] = json_decode(file_get_contents(resource_path('views/backend/setting/timezone.json')));
        
        // Get cron job settings dynamically
        $data['cronJobs'] = $this->getCronJobs();
        
        // Get dynamic performance tips based on codebase analysis
        $data['performanceTips'] = $this->getPerformanceTips();
        
        // Get database backups
        $data['backups'] = $this->backupService->listBackups();
        
        // Get dynamic seeder count
        $data['seederCount'] = $this->getSeederCount();
        
        return view('backend.setting.index')->with($data);
    }

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

    /**
     * Get dynamic performance tips based on codebase analysis
     */
    protected function getPerformanceTips()
    {
        $tips = [
            'database' => [],
            'server' => [],
            'code' => []
        ];

        // Database Optimization Tips (Dynamic Analysis)
        $nPlusOneAnalysis = $this->analyzeNPlusOneQueries();
        if (!empty($nPlusOneAnalysis)) {
            $tips['database'][] = [
                'tip' => __('Use eager loading (with()) to prevent N+1 query problems'),
                'example' => $nPlusOneAnalysis['example'],
                'priority' => 'high',
                'detected' => $nPlusOneAnalysis['detected'],
                'details' => $nPlusOneAnalysis['details'] ?? null
            ];
        }

        $indexAnalysis = $this->analyzeDatabaseIndexes();
        if (!empty($indexAnalysis)) {
            $tips['database'][] = [
                'tip' => __('Add database indexes on frequently queried columns'),
                'example' => $indexAnalysis['example'],
                'priority' => 'medium',
                'detected' => $indexAnalysis['detected'],
                'details' => $indexAnalysis['details'] ?? null
            ];
        }

        $cacheAnalysis = $this->analyzeCacheUsage();
        if (!empty($cacheAnalysis)) {
            $tips['database'][] = [
                'tip' => __('Use query caching for expensive queries'),
                'example' => $cacheAnalysis['example'],
                'priority' => 'medium',
                'detected' => $cacheAnalysis['detected'],
                'details' => $cacheAnalysis['details'] ?? null
            ];
        }

        $paginationAnalysis = $this->analyzePaginationUsage();
        if (!empty($paginationAnalysis)) {
            $tips['database'][] = [
                'tip' => __('Use pagination for large datasets'),
                'example' => $paginationAnalysis['example'],
                'priority' => 'high',
                'detected' => $paginationAnalysis['detected'],
                'details' => $paginationAnalysis['details'] ?? null
            ];
        }

        // Server Configuration Tips (Dynamic Detection)
        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status() !== false;
        $tips['server'][] = [
            'tip' => __('Enable OPcache in production (PHP 7.0+)'),
            'example' => __('opcache.enable=1 in php.ini'),
            'priority' => $opcacheEnabled ? 'low' : 'high',
            'detected' => $opcacheEnabled,
            'status' => $opcacheEnabled ? __('Enabled') : __('Not Enabled')
        ];

        $cacheDriver = config('cache.default', 'file');
        $tips['server'][] = [
            'tip' => __('Use Redis or Memcached for session and cache storage'),
            'example' => __('CACHE_DRIVER=redis, SESSION_DRIVER=redis in .env'),
            'priority' => in_array($cacheDriver, ['redis', 'memcached']) ? 'low' : 'medium',
            'detected' => in_array($cacheDriver, ['redis', 'memcached']),
            'status' => __('Current') . ': ' . strtoupper($cacheDriver)
        ];

        $tips['server'][] = [
            'tip' => __('Enable HTTP/2 and Gzip compression'),
            'example' => __('Configure in web server (Apache/Nginx)'),
            'priority' => 'medium',
            'detected' => null // Can't detect from PHP
        ];

        $assetUrl = config('app.asset_url');
        $tips['server'][] = [
            'tip' => __('Use CDN for static assets (CSS, JS, images)'),
            'example' => __('ASSET_URL=https://cdn.yourdomain.com in .env'),
            'priority' => !empty($assetUrl) ? 'low' : 'low',
            'detected' => !empty($assetUrl),
            'status' => !empty($assetUrl) ? __('Configured') : __('Not Configured')
        ];

        // Code Optimization Tips (Based on Application Structure)
        $queueConnection = config('queue.default', 'sync');
        $tips['code'][] = [
            'tip' => __('Use queues for heavy/long-running tasks'),
            'example' => __('dispatch(new HeavyJob($data))'),
            'priority' => $queueConnection === 'sync' ? 'high' : 'low',
            'detected' => $queueConnection !== 'sync',
            'status' => __('Current') . ': ' . strtoupper($queueConnection)
        ];

        $cacheAnalysis = $this->analyzeCacheUsage();
        if (!empty($cacheAnalysis)) {
            $tips['code'][] = [
                'tip' => __('Cache expensive computations and API calls'),
                'example' => $cacheAnalysis['example'],
                'priority' => 'medium',
                'detected' => $cacheAnalysis['detected'],
                'details' => $cacheAnalysis['details'] ?? null
            ];
        }

        $middlewareCount = count(config('app.middleware', []));
        $tips['code'][] = [
            'tip' => __('Minimize middleware usage where possible'),
            'example' => __('Apply middleware only to routes that need it'),
            'priority' => $middlewareCount > 10 ? 'medium' : 'low',
            'detected' => $middlewareCount <= 10
        ];

        $chunkingAnalysis = $this->analyzeChunkingUsage();
        if (!empty($chunkingAnalysis)) {
            $tips['code'][] = [
                'tip' => __('Use chunking for processing large datasets'),
                'example' => $chunkingAnalysis['example'],
                'priority' => 'medium',
                'detected' => $chunkingAnalysis['detected'],
                'details' => $chunkingAnalysis['details'] ?? null
            ];
        }

        return $tips;
    }

    /**
     * Analyze N+1 query risks by scanning models and controllers
     */
    protected function analyzeNPlusOneQueries()
    {
        $models = $this->scanModels();
        $controllers = $this->scanControllers();
        
        $riskyPatterns = [];
        $examples = [];
        
        // Find models with relationships
        foreach ($models as $model => $relationships) {
            if (empty($relationships)) continue;
            
            // Check if controllers use this model without eager loading
            foreach ($controllers as $controller => $queries) {
                foreach ($queries as $query) {
                    // Check if model is used but relationships aren't eager loaded
                    if (strpos($query, $model) !== false && 
                        strpos($query, '->with(') === false &&
                        strpos($query, '::with(') === false) {
                        $riskyPatterns[] = [
                            'model' => class_basename($model),
                            'controller' => class_basename($controller),
                            'relationships' => implode(', ', array_slice($relationships, 0, 3))
                        ];
                        
                        if (count($examples) < 3) {
                            $relList = implode('", "', array_slice($relationships, 0, 3));
                            $examples[] = class_basename($model) . '::with("' . $relList . '")->get()';
                        }
                    }
                }
            }
        }
        
        return [
            'detected' => empty($riskyPatterns),
            'example' => !empty($examples) ? __('Example: :example', ['example' => $examples[0]]) : __('Example: Signal::with("pair", "time", "market")->get()'),
            'details' => !empty($riskyPatterns) ? __('Found :count potential N+1 risks', ['count' => count($riskyPatterns)]) : null
        ];
    }

    /**
     * Analyze database indexes by checking migrations and schema
     */
    protected function analyzeDatabaseIndexes()
    {
        $migrations = glob(database_path('migrations/*.php'));
        $indexedColumns = [];
        $commonColumns = ['user_id', 'status', 'is_published', 'created_at', 'is_current'];
        
        foreach ($migrations as $migration) {
            $content = file_get_contents($migration);
            // Extract index definitions
            preg_match_all('/\$table->(index|unique)\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);
            if (!empty($matches[2])) {
                $indexedColumns = array_merge($indexedColumns, $matches[2]);
            }
        }
        
        $missingIndexes = array_diff($commonColumns, $indexedColumns);
        
        return [
            'detected' => empty($missingIndexes),
            'example' => !empty($missingIndexes) 
                ? __('Columns to index: :columns', ['columns' => implode(', ', array_slice($missingIndexes, 0, 5))])
                : __('All common columns are indexed'),
            'details' => !empty($missingIndexes) 
                ? __(':count columns may need indexes', ['count' => count($missingIndexes)])
                : null
        ];
    }

    /**
     * Analyze cache usage patterns in codebase
     */
    protected function analyzeCacheUsage()
    {
        $files = array_merge(
            glob(app_path('**/*.php')),
            glob(base_path('main/addons/**/app/**/*.php'))
        );
        
        $cacheUsageCount = 0;
        $totalFiles = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            $totalFiles++;
            $content = file_get_contents($file);
            if (preg_match('/Cache::(remember|get|put)|cache\(\)->(remember|get|put)/', $content)) {
                $cacheUsageCount++;
            }
        }
        
        $usageRate = $totalFiles > 0 ? ($cacheUsageCount / $totalFiles) * 100 : 0;
        
        return [
            'detected' => $usageRate > 5, // More than 5% of files use cache
            'example' => __('Cache::remember("key", 3600, function() { return Model::get(); })'),
            'details' => __('Cache used in :count/:total files (:percent%)', [
                'count' => $cacheUsageCount,
                'total' => $totalFiles,
                'percent' => round($usageRate, 1)
            ])
        ];
    }

    /**
     * Analyze pagination usage
     */
    protected function analyzePaginationUsage()
    {
        $files = array_merge(
            glob(app_path('Http/Controllers/**/*.php')),
            glob(base_path('main/addons/**/app/Http/Controllers/**/*.php'))
        );
        
        $paginationCount = 0;
        $allCount = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            $content = file_get_contents($file);
            if (preg_match('/->(all|get)\(\)/', $content)) {
                $allCount++;
            }
            if (preg_match('/->(paginate|simplePaginate)\(/', $content)) {
                $paginationCount++;
            }
        }
        
        return [
            'detected' => $paginationCount > 0,
            'example' => __('Model::paginate(20) instead of Model::all()'),
            'details' => __('Found :paginate pagination usages vs :all ->all() calls', [
                'paginate' => $paginationCount,
                'all' => $allCount
            ])
        ];
    }

    /**
     * Analyze chunking usage
     */
    protected function analyzeChunkingUsage()
    {
        $files = array_merge(
            glob(app_path('**/*.php')),
            glob(base_path('main/addons/**/app/**/*.php'))
        );
        
        $chunkingCount = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            $content = file_get_contents($file);
            if (preg_match('/->chunk\(/', $content)) {
                $chunkingCount++;
            }
        }
        
        return [
            'detected' => $chunkingCount > 0,
            'example' => __('Model::chunk(100, function($items) { ... })'),
            'details' => __('Found :count chunking usages', ['count' => $chunkingCount])
        ];
    }

    /**
     * Scan models to find relationships
     */
    protected function scanModels()
    {
        $models = [];
        $modelFiles = array_merge(
            glob(app_path('Models/*.php')),
            glob(base_path('main/addons/**/app/Models/*.php'))
        );
        
        foreach ($modelFiles as $file) {
            if (!is_file($file)) continue;
            $content = file_get_contents($file);
            $className = $this->extractClassName($file, $content);
            if (!$className) continue;
            
            // Extract relationship methods
            preg_match_all('/public function (\w+)\(\)\s*\{[^}]*return \$this->(hasMany|belongsTo|hasOne|belongsToMany)\(/', $content, $matches);
            $relationships = $matches[1] ?? [];
            
            if (!empty($relationships)) {
                $models[$className] = $relationships;
            }
        }
        
        return $models;
    }

    /**
     * Scan controllers for query patterns
     */
    protected function scanControllers()
    {
        $controllers = [];
        $controllerFiles = array_merge(
            glob(app_path('Http/Controllers/**/*.php')),
            glob(base_path('main/addons/**/app/Http/Controllers/**/*.php'))
        );
        
        foreach ($controllerFiles as $file) {
            if (!is_file($file)) continue;
            $content = file_get_contents($file);
            $className = $this->extractClassName($file, $content);
            if (!$className) continue;
            
            // Extract model queries
            preg_match_all('/(\w+)::(all|get|find|where|paginate|first)\(/', $content, $matches);
            $queries = $matches[0] ?? [];
            
            if (!empty($queries)) {
                $controllers[$className] = $queries;
            }
        }
        
        return $controllers;
    }

    /**
     * Extract class name from file content
     */
    protected function extractClassName($file, $content)
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch) &&
            preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }
        return null;
    }

    public function ConfigurationUpdate(ConfigurationRequest $request)
    {

        $isSuccess = $this->config->general($request);

        if ($isSuccess['type'] == 'success')
            return back()->with('success', $isSuccess['message']);
    }


    public function cacheClear()
    {

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');

        return back()->with('success', 'Caches cleared successfully!');
    }

    /**
     * Performance optimization actions
     */
    public function performanceOptimize(Request $request)
    {
        $action = $request->input('action');
        $results = [];
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            switch ($action) {
                case 'config:cache':
                    Artisan::call('config:cache');
                    $results[] = ['type' => 'success', 'message' => __('Configuration cached successfully!')];
                    break;

                case 'route:cache':
                    Artisan::call('route:cache');
                    $results[] = ['type' => 'success', 'message' => __('Routes cached successfully!')];
                    break;

                case 'view:cache':
                    Artisan::call('view:cache');
                    $results[] = ['type' => 'success', 'message' => __('Views cached successfully!')];
                    break;

                case 'composer:optimize':
                    // Optimize Composer autoloader (like WordPress plugins do)
                    $composerPath = base_path('composer.json');
                    if (file_exists($composerPath)) {
                        $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                        $basePath = base_path();
                        
                        // Check if shell_exec is allowed
                        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
                            $output = shell_exec("cd {$basePath} && {$phpPath} composer dump-autoload --optimize --no-dev 2>&1");
                            if ($output !== null) {
                                $results[] = ['type' => 'success', 'message' => __('Composer autoloader optimized successfully!')];
                            } else {
                                $results[] = ['type' => 'warning', 'message' => __('Composer optimization attempted. Please verify manually.')];
                            }
                        } else {
                            // Fallback: try via Artisan if composer command exists
                            try {
                                Artisan::call('composer:dump-autoload');
                                $results[] = ['type' => 'success', 'message' => __('Composer autoloader optimized!')];
                            } catch (\Exception $e) {
                                $results[] = ['type' => 'info', 'message' => __('Please run manually: composer dump-autoload --optimize --no-dev')];
                            }
                        }
                    } else {
                        $results[] = ['type' => 'error', 'message' => __('composer.json not found')];
                    }
                    break;

                case 'opcache:reset':
                    // Reset OPcache (like WordPress plugins do)
                    if (function_exists('opcache_reset')) {
                        if (opcache_reset()) {
                            $results[] = ['type' => 'success', 'message' => __('OPcache reset successfully!')];
                        } else {
                            $results[] = ['type' => 'warning', 'message' => __('OPcache reset failed or not enabled')];
                        }
                    } else {
                        $results[] = ['type' => 'info', 'message' => __('OPcache extension not available')];
                    }
                    break;

                case 'optimize':
                    $results[] = ['type' => 'info', 'message' => __('Starting full optimization...')];
                    
                    // 1. Clear all caches first
                    Artisan::call('cache:clear');
                    $results[] = ['type' => 'success', 'message' => __('✓ Application cache cleared')];
                    
                    Artisan::call('config:clear');
                    $results[] = ['type' => 'success', 'message' => __('✓ Config cache cleared')];
                    
                    Artisan::call('route:clear');
                    $results[] = ['type' => 'success', 'message' => __('✓ Route cache cleared')];
                    
                    Artisan::call('view:clear');
                    $results[] = ['type' => 'success', 'message' => __('✓ View cache cleared')];
                    
                    // 2. Cache Laravel components
                    Artisan::call('config:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Configuration cached')];
                    
                    Artisan::call('route:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Routes cached')];
                    
                    Artisan::call('view:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Views cached')];
                    
                    // 3. Optimize Composer autoloader
                    $composerPath = base_path('composer.json');
                    if (file_exists($composerPath) && function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
                        $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                        $basePath = base_path();
                        $output = @shell_exec("cd {$basePath} && {$phpPath} composer dump-autoload --optimize --no-dev 2>&1");
                        if ($output !== null) {
                            $results[] = ['type' => 'success', 'message' => __('✓ Composer autoloader optimized')];
                        }
                    }
                    
                    // 4. Reset OPcache if available
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        $results[] = ['type' => 'success', 'message' => __('✓ OPcache reset')];
                    }
                    
                    $results[] = ['type' => 'success', 'message' => __('All optimizations completed successfully!')];
                    break;

                default:
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => __('Invalid action specified.')], 400);
                    }
                    return back()->with('error', __('Invalid action specified.'));
            }

            // Format results for display
            $messages = array_map(function($result) {
                return $result['message'];
            }, $results);
            
            $message = implode('<br>', $messages);
            $type = collect($results)->contains('type', 'error') ? 'error' : 'success';

            if ($isAjax) {
                return response()->json([
                    'success' => $type === 'success',
                    'message' => $message,
                    'type' => $type
                ]);
            }

            return back()->with($type, $message);
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('Optimization failed: :error', ['error' => $e->getMessage()])
                ], 500);
            }
            return back()->with('error', __('Optimization failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Performance cache clearing actions
     */
    public function performanceClear(Request $request)
    {
        $action = $request->input('action');
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            switch ($action) {
                case 'cache:clear':
                    Artisan::call('cache:clear');
                    $message = __('Application cache cleared successfully!');
                    break;

                case 'optimize:clear':
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    $message = __('All caches cleared successfully!');
                    break;

                default:
                    if ($isAjax) {
                        return response()->json(['success' => false, 'message' => __('Invalid action specified.')], 400);
                    }
                    return back()->with('error', __('Invalid action specified.'));
            }

            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cache clearing failed: :error', ['error' => $e->getMessage()])
                ], 500);
            }
            return back()->with('error', __('Cache clearing failed: :error', ['error' => $e->getMessage()]));
        }
    }

    public function manageTheme()
    {
        $data['title'] = 'Manage Theme';
        $data['themes'] = $this->themeManager->list();
        $data['backendThemes'] = $this->themeManager->listBackend();
        return view('backend.setting.theme')->with($data);
    }

    public function themeUpdate(Request $request, $name = null)
    {
        $general = Configuration::first();

        // Get theme name from route parameter or request
        $themeName = $name ?? $request->input('name') ?? $request->input('theme');
        
        if (!$themeName) {
            return redirect()->back()->with('error', 'Theme name is required.');
        }

        $general->theme = $themeName;
        $general->color = $request->input('color', '#9c0ac');

        $general->save();

        return redirect()->back()->with('success', 'Template Activated successfully');
    }

    public function themeColor(Request $request)
    {
        
        $general = Configuration::first();

        $general->theme = $request->theme;
        $general->color = $request->color;

        $general->save();


        return response()->json(['success' => true]);
    }

    /**
     * Upload theme ZIP file
     */
    public function themeUpload(Request $request)
    {
        $validated = $request->validate([
            'theme_package' => ['required', 'file', 'mimes:zip', 'max:10240'], // Max 10MB
        ]);

        try {
            $result = $this->themeManager->upload($request->file('theme_package'));

            return redirect()
                ->route('admin.manage.theme')
                ->with('success', __('Theme :theme installed successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * Access page builder from Manage Theme (backward compatibility)
     */
    public function themePageBuilder()
    {
        // Redirect to theme builder edit route
        return redirect()->route('admin.page-builder.themes.edit');
    }

    /**
     * Download theme template
     */
    public function themeDownloadTemplate()
    {
        try {
            $zipPath = $this->themeManager->downloadTemplate();
            $filename = 'theme-template-' . date('Y-m-d') . '.zip';

            return Response::download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * Delete theme
     */
    public function themeDelete(Request $request, string $themeName)
    {
        try {
            $result = $this->themeManager->delete($themeName);

            return redirect()
                ->route('admin.manage.theme')
                ->with('success', __('Theme :theme deleted successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * Update backend theme
     */
    public function backendThemeUpdate(Request $request, $name = null)
    {
        $general = Configuration::first();

        // Get theme name from route parameter or request
        $themeName = $name ?? $request->input('name') ?? $request->input('theme');
        
        if (!$themeName) {
            return redirect()->back()->with('error', 'Theme name is required.');
        }

        $general->backend_theme = $themeName;
        $general->save();

        return redirect()->back()->with('success', 'Backend theme activated successfully');
    }

    /**
     * Deactivate all frontend themes
     */
    public function themeDeactivate()
    {
        try {
            $general = Configuration::first();
            
            if (!$general) {
                return redirect()->back()->with('error', 'Configuration not found.');
            }

            $general->theme = null;
            $general->save();

            return redirect()->back()->with('success', 'All frontend themes have been deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate themes: ' . $e->getMessage());
        }
    }

    /**
     * Re-seed database with demo data
     */
    public function reseedDatabase(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(300); // 5 minutes timeout

            $output = [];
            
            // Run database seeder
            Artisan::call('db:seed', [
                '--force' => true
            ]);
            
            $output[] = Artisan::output();
            
            // Clear all caches
            Artisan::call('optimize:clear');
            
            $message = 'Database re-seeded successfully! All demo data has been restored.';
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            $errorMessage = 'Failed to re-seed database: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Full database reset and reseed (DANGEROUS)
     */
    public function resetDatabase(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if ($request->confirm !== 'RESET') {
                $errorMessage = 'Please type RESET to confirm database reset.';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            set_time_limit(600); // 10 minutes timeout

            // Wipe database
            Artisan::call('db:wipe', ['--force' => true]);
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            
            // Seed database
            Artisan::call('db:seed', ['--force' => true]);
            
            // Clear caches
            Artisan::call('optimize:clear');

            $message = 'Database reset complete! Please login again.';
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('admin.login')
                ]);
            }

            return redirect()->route('admin.login')->with('success', $message);
            
        } catch (\Exception $e) {
            $errorMessage = 'Failed to reset database: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(300);
            
            $name = $request->backup_name ?? 'backup_' . date('Y-m-d_H-i-s');
            $result = $this->backupService->createBackup($name);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Backup failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Load backup state
     */
    public function loadBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if (empty($request->backup_file)) {
                $errorMessage = 'Please select a backup file';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            set_time_limit(600);
            
            $result = $this->backupService->loadBackup($request->backup_file);
            
            if ($result['type'] === 'success') {
                $message = 'Database restored successfully! Please login again.';
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Restore failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if (empty($request->backup_file)) {
                $errorMessage = 'Please select a backup file';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $result = $this->backupService->deleteBackup($request->backup_file);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Delete failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Save backup as factory state
     */
    public function saveAsFactoryState(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            $result = $this->backupService->saveAsFactoryState($request->backup_file ?? null);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Save failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Load factory state
     */
    public function loadFactoryState(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(600);

            $result = $this->backupService->loadFactoryState();

            if ($result['type'] === 'success') {
                $message = 'Restored to factory state! Please login again.';
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return redirect()->back()->with($result['type'], $result['message']);

        } catch (\Exception $e) {
            $errorMessage = 'Factory restore failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Get dynamic seeder count from DatabaseSeeder
     * Counts all seeders by parsing the source code
     */
    protected function getSeederCount(): int
    {
        try {
            $seederFile = database_path('seeders/DatabaseSeeder.php');
            
            if (!file_exists($seederFile)) {
                return 0;
            }

            $content = file_get_contents($seederFile);
            
            // Count all Seeder::class and RolePermission::class occurrences
            // This includes both main array and conditional seeders
            $count = substr_count($content, 'Seeder::class') + substr_count($content, 'RolePermission::class');
            
            return $count;
        } catch (\Exception $e) {
            // If file read fails, return 0
            return 0;
        }
    }
}
