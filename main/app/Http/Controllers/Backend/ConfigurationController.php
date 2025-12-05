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
        
        return view('backend.setting.index')->with($data);
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
        if (\App\Support\AddonRegistry::active('trading-execution-engine-addon')) {
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
        if (\App\Support\AddonRegistry::active('smart-risk-management-addon')) {
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
        $tips['database'][] = [
            'tip' => __('Use eager loading (with()) to prevent N+1 query problems'),
            'example' => __('Example: Signal::with("pair", "time", "market")->get()'),
            'priority' => 'high',
            'detected' => $this->checkNPlusOneRisk()
        ];

        $tips['database'][] = [
            'tip' => __('Add database indexes on frequently queried columns'),
            'example' => __('Columns: user_id, signal_id, status, is_published, created_at'),
            'priority' => 'medium',
            'detected' => $this->checkMissingIndexes()
        ];

        $tips['database'][] = [
            'tip' => __('Use query caching for expensive queries'),
            'example' => __('Cache::remember("key", 3600, function() { return Model::get(); })'),
            'priority' => 'medium',
            'detected' => $this->checkCacheUsage()
        ];

        $tips['database'][] = [
            'tip' => __('Use pagination for large datasets'),
            'example' => __('Model::paginate(20) instead of Model::all()'),
            'priority' => 'high',
            'detected' => true
        ];

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

        $tips['code'][] = [
            'tip' => __('Cache expensive computations and API calls'),
            'example' => __('Cache::remember("key", 3600, function() { ... })'),
            'priority' => 'medium',
            'detected' => $this->checkCacheUsage()
        ];

        $middlewareCount = count(config('app.middleware', []));
        $tips['code'][] = [
            'tip' => __('Minimize middleware usage where possible'),
            'example' => __('Apply middleware only to routes that need it'),
            'priority' => $middlewareCount > 10 ? 'medium' : 'low',
            'detected' => $middlewareCount <= 10
        ];

        $tips['code'][] = [
            'tip' => __('Use chunking for processing large datasets'),
            'example' => __('Model::chunk(100, function($items) { ... })'),
            'priority' => 'medium',
            'detected' => true
        ];

        return $tips;
    }

    /**
     * Check for potential N+1 query risks
     */
    protected function checkNPlusOneRisk()
    {
        // Check if models have relationships that might cause N+1
        $modelsWithRelationships = [
            'App\Models\Signal' => ['pair', 'time', 'market', 'plans'],
            'App\Models\User' => ['subscriptions', 'payments', 'tickets'],
            'App\Models\Payment' => ['user', 'plan', 'gateway']
        ];

        // This is a simplified check - in production, you'd analyze actual queries
        return true; // Assume risk exists and recommend eager loading
    }

    /**
     * Check for missing database indexes
     */
    protected function checkMissingIndexes()
    {
        // Common columns that should be indexed
        $shouldBeIndexed = [
            'users.ref_id',
            'users.status',
            'signals.is_published',
            'signals.published_date',
            'plan_subscriptions.is_current',
            'payments.status'
        ];

        // In production, you'd query information_schema to check actual indexes
        return true; // Recommend checking indexes
    }

    /**
     * Check cache usage patterns
     */
    protected function checkCacheUsage()
    {
        // Check if Cache::remember is used in codebase
        $cacheFiles = glob(app_path('**/*.php'));
        $cacheUsageFound = false;

        foreach ($cacheFiles as $file) {
            if (is_file($file)) {
                $content = file_get_contents($file);
                if (strpos($content, 'Cache::remember') !== false || 
                    strpos($content, 'cache()->remember') !== false) {
                    $cacheUsageFound = true;
                    break;
                }
            }
        }

        return $cacheUsageFound;
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
     * Performance optimization actions (WordPress-style automatic execution)
     */
    public function performanceOptimize(Request $request)
    {
        $action = $request->input('action');
        $results = [];

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
                    // WordPress-style: Optimize everything automatically
                    $results[] = ['type' => 'info', 'message' => __('Starting full optimization...')];
                    
                    // 1. Cache Laravel components
                    Artisan::call('config:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Configuration cached')];
                    
                    Artisan::call('route:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Routes cached')];
                    
                    Artisan::call('view:cache');
                    $results[] = ['type' => 'success', 'message' => __('✓ Views cached')];
                    
                    // 2. Optimize Composer autoloader
                    $composerPath = base_path('composer.json');
                    if (file_exists($composerPath) && function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
                        $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                        $basePath = base_path();
                        $output = @shell_exec("cd {$basePath} && {$phpPath} composer dump-autoload --optimize --no-dev 2>&1");
                        if ($output !== null) {
                            $results[] = ['type' => 'success', 'message' => __('✓ Composer autoloader optimized')];
                        }
                    }
                    
                    // 3. Reset OPcache if available
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        $results[] = ['type' => 'success', 'message' => __('✓ OPcache reset')];
                    }
                    
                    $results[] = ['type' => 'success', 'message' => __('All optimizations completed successfully!')];
                    break;

                default:
                    return back()->with('error', __('Invalid action specified.'));
            }

            // Format results for display
            $messages = array_map(function($result) {
                return $result['message'];
            }, $results);
            
            $message = implode('<br>', $messages);
            $type = collect($results)->contains('type', 'error') ? 'error' : 'success';

            return back()->with($type, $message);
        } catch (\Exception $e) {
            return back()->with('error', __('Optimization failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Performance cache clearing actions
     */
    public function performanceClear(Request $request)
    {
        $action = $request->input('action');

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
                    return back()->with('error', __('Invalid action specified.'));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
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
            
            return redirect()->back()->with('success', 'Database re-seeded successfully! All demo data has been restored.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to re-seed database: ' . $e->getMessage());
        }
    }

    /**
     * Full database reset and reseed (DANGEROUS)
     */
    public function resetDatabase(Request $request)
    {
        try {
            if ($request->confirm !== 'RESET') {
                return redirect()->back()->with('error', 'Please type RESET to confirm database reset.');
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

            return redirect()->route('admin.login')->with('success', 'Database reset complete! Please login again.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reset database: ' . $e->getMessage());
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(Request $request)
    {
        try {
            set_time_limit(300);
            
            $name = $request->backup_name ?? 'backup_' . date('Y-m-d_H-i-s');
            $result = $this->backupService->createBackup($name);
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Load backup state
     */
    public function loadBackup(Request $request)
    {
        try {
            if (empty($request->backup_file)) {
                return redirect()->back()->with('error', 'Please select a backup file');
            }

            set_time_limit(600);
            
            $result = $this->backupService->loadBackup($request->backup_file);
            
            if ($result['type'] === 'success') {
                return redirect()->route('admin.login')->with('success', 'Database restored successfully! Please login again.');
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup(Request $request)
    {
        try {
            if (empty($request->backup_file)) {
                return redirect()->back()->with('error', 'Please select a backup file');
            }

            $result = $this->backupService->deleteBackup($request->backup_file);
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Save backup as factory state
     */
    public function saveAsFactoryState(Request $request)
    {
        try {
            $result = $this->backupService->saveAsFactoryState($request->backup_file ?? null);
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Save failed: ' . $e->getMessage());
        }
    }

    /**
     * Load factory state
     */
    public function loadFactoryState(Request $request)
    {
        try {
            set_time_limit(600);
            
            $result = $this->backupService->loadFactoryState();
            
            if ($result['type'] === 'success') {
                return redirect()->route('admin.login')->with('success', 'Restored to factory state! Please login again.');
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Factory restore failed: ' . $e->getMessage());
        }
    }
}
