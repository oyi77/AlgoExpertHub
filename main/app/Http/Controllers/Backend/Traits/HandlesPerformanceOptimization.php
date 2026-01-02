<?php

namespace App\Http\Controllers\Backend\Traits;

use App\Helpers\NotificationHelper;
use App\Models\GlobalConfiguration;
use App\Services\PerformanceOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

trait HandlesPerformanceOptimization
{
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
                    return back()->with('notify', NotificationHelper::error(__('Invalid action specified.'), 'Error'));
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

            if ($type === 'success') {
                return back()->with('notify', NotificationHelper::success($message, 'Success'));
            } else {
                return back()->with('notify', NotificationHelper::error($message, 'Error'));
            }
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('Optimization failed: :error', ['error' => $e->getMessage()])
                ], 500);
            }
            return back()->with('notify', NotificationHelper::error(__('Optimization failed: :error', ['error' => $e->getMessage()]), 'Error'));
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
                    return back()->with('notify', NotificationHelper::error(__('Invalid action specified.'), 'Error'));
            }

            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('notify', NotificationHelper::success($message, 'Success'));
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cache clearing failed: :error', ['error' => $e->getMessage()])
                ], 500);
            }
            return back()->with('notify', NotificationHelper::error(__('Cache clearing failed: :error', ['error' => $e->getMessage()]), 'Error'));
        }
    }

    /**
     * Frontend/assets optimization toggles
     */
    public function performanceAssets(Request $request)
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $perf['frontend']['enable'] = (bool)$request->input('enable', true);
        $perf['frontend']['lazy_images'] = (bool)$request->input('lazy_images', false);
        $perf['frontend']['defer_scripts'] = (bool)$request->input('defer_scripts', false);
        $perf['frontend']['async_scripts'] = (bool)$request->input('async_scripts', false);
        $perf['frontend']['preload'] = [
            'fonts' => (array)$request->input('preload_fonts', []),
            'styles' => (array)$request->input('preload_styles', []),
            'scripts' => (array)$request->input('preload_scripts', []),
            'dns_prefetch' => (array)$request->input('dns_prefetch', []),
        ];
        GlobalConfiguration::setValue('performance', $perf, 'Performance settings');
        return response()->json(['success' => true, 'message' => __('Frontend optimization updated')]);
    }

    /**
     * HTTP caching & ETag configuration
     */
    public function performanceHttp(Request $request)
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $perf['http']['enable'] = (bool)$request->input('enable', true);
        $perf['http']['cache_headers']['enabled'] = (bool)$request->input('cache_headers', true);
        $perf['http']['cache_headers']['ttl'] = (int)$request->input('ttl', 3600);
        $perf['http']['etag']['enabled'] = (bool)$request->input('etag', true);
        $perf['http']['blacklist']['paths'] = (array)$request->input('blacklist', []);
        $perf['http']['whitelist']['paths'] = (array)$request->input('whitelist', []);
        GlobalConfiguration::setValue('performance', $perf, 'Performance settings');
        return response()->json(['success' => true, 'message' => __('HTTP caching updated')]);
    }

    /**
     * Media optimization configuration
     */
    public function performanceMedia(Request $request)
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $perf['media']['enable'] = (bool)$request->input('enable', true);
        $perf['media']['compress'] = (bool)$request->input('compress', true);
        $perf['media']['convert_webp'] = (bool)$request->input('webp', true);
        $perf['media']['max_width'] = (int)$request->input('max_width', 1920);
        $perf['media']['max_height'] = (int)$request->input('max_height', 1920);
        GlobalConfiguration::setValue('performance', $perf, 'Performance settings');
        return response()->json(['success' => true, 'message' => __('Media optimization updated')]);
    }

    /**
     * Cache configuration & prewarm trigger
     */
    public function performanceCache(Request $request)
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $perf['cache']['enable'] = (bool)$request->input('enable', true);
        $perf['cache']['ttl_map'] = (array)$request->input('ttl_map', $perf['cache']['ttl_map'] ?? []);
        $perf['cache']['prewarm']['enable'] = (bool)$request->input('prewarm', true);
        $perf['cache']['prewarm']['routes'] = (array)$request->input('prewarm_routes', $perf['cache']['prewarm']['routes'] ?? []);
        GlobalConfiguration::setValue('performance', $perf, 'Performance settings');
        return response()->json(['success' => true, 'message' => __('Cache settings updated')]);
    }

    /**
     * Database cleanup actions
     */
    public function performanceDatabase(Request $request, PerformanceOptimizationService $service)
    {
        $prune = (int)$request->input('prune_days', 14);
        $summary = $service->cleanupDatabase($prune);
        return response()->json(['success' => true, 'message' => __('Database cleaned'), 'data' => $summary]);
    }

    /**
     * Prewarm routes
     */
    public function performancePrewarm(Request $request, PerformanceOptimizationService $service)
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $routes = (array)$request->input('routes', $perf['cache']['prewarm']['routes'] ?? []);
        $results = $service->prewarmRoutes($routes);
        return response()->json(['success' => true, 'message' => __('Prewarm complete'), 'data' => $results]);
    }
}
