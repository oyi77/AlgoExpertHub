<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Services\BackupService;
use Addons\AlgoExpertPlus\App\Services\HealthService;
use Addons\AlgoExpertPlus\App\Services\SystemHealthService;
use Addons\AlgoExpertPlus\App\Services\DependencyService;
use App\Support\AddonRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlgoExpertPlusController extends Controller
{
    protected BackupService $backupService;
    protected HealthService $healthService;
    protected SystemHealthService $systemHealthService;
    protected DependencyService $dependencyService;

    public function __construct(
        BackupService $backupService, 
        HealthService $healthService,
        SystemHealthService $systemHealthService,
        DependencyService $dependencyService
    ) {
        $this->backupService = $backupService;
        $this->healthService = $healthService;
        $this->systemHealthService = $systemHealthService;
        $this->dependencyService = $dependencyService;
    }

    /**
     * Display the main dashboard with module status
     */
    public function index(): View
    {
        $manifest = AddonRegistry::get('algoexpert-plus-addon');
        $modules = collect($manifest['modules'] ?? []);

        // Get dependency status for each module
        $seoDep = $this->dependencyService->getModuleDependencyStatus('seo');
        $queuesDep = $this->dependencyService->getModuleDependencyStatus('queues');
        $backupDep = $this->dependencyService->getModuleDependencyStatus('backup');
        $healthDep = $this->dependencyService->getModuleDependencyStatus('health');

        $data = [
            'title' => 'AlgoExpert++',
            'modules' => [
                'seo' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'seo'))['enabled'] ?? false,
                    'name' => 'SEO Integration',
                    'description' => 'SEO tools integration for meta tags and OpenGraph',
                    'status' => $seoDep['available'],
                    'status_message' => $this->getSeoStatusMessage(),
                    'dependency' => $seoDep,
                ],
                'queues' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'queues'))['enabled'] ?? false,
                    'name' => 'Queues Dashboard',
                    'description' => 'Laravel Horizon queue monitoring dashboard',
                    'status' => $queuesDep['available'] && !$queuesDep['needs_config'],
                    'status_message' => $this->getQueuesStatusMessage(),
                    'url' => $this->getHorizonUrl(),
                    'dependency' => $queuesDep,
                ],
                'backup' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'backup'))['enabled'] ?? false,
                    'name' => 'System Backup',
                    'description' => 'Automated database and file backups',
                    'status' => $backupDep['available'],
                    'status_message' => $this->backupService->getStatus()['message'] ?? 'Checking...',
                    'dependency' => $backupDep,
                ],
                'health' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'health'))['enabled'] ?? false,
                    'name' => 'System Health',
                    'description' => 'System health monitoring and checks',
                    'status' => $healthDep['available'],
                    'status_message' => $this->healthService->getStatus()['message'] ?? 'Checking...',
                    'url' => $this->getHealthUrl(),
                    'dependency' => $healthDep,
                ],
                'i18n' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'i18n'))['enabled'] ?? false,
                    'name' => 'Internationalization',
                    'description' => 'Multi-language support',
                    'status' => true,
                ],
                'ui_components' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'ui_components'))['enabled'] ?? false,
                    'name' => 'UI Components',
                    'description' => 'Reusable UI component library',
                    'status' => false,
                ],
                'ai_tools' => [
                    'enabled' => (bool) optional($modules->firstWhere('key', 'ai_tools'))['enabled'] ?? false,
                    'name' => 'AI Tools',
                    'description' => 'AI-powered tools and utilities',
                    'status' => false,
                ],
            ],
        ];

        return view('algoexpert-plus::backend.index', $data);
    }

    /**
     * Run system backup (deprecated - use BackupController instead)
     */
    public function runBackup(): RedirectResponse
    {
        try {
            $result = $this->backupService->run();
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('success', $result['message'] ?? 'Backup started successfully');
        } catch (\Throwable $e) {
            return redirect()->route('admin.algoexpert-plus.backup.index')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Display comprehensive system health dashboard
     */
    public function systemHealth(): View
    {
        $data = [
            'title' => 'System Health Dashboard',
            'horizon_stats' => $this->systemHealthService->getHorizonStats(),
            'queue_stats' => $this->systemHealthService->getQueueStats(),
            'system_info' => $this->systemHealthService->getSystemInfo(),
            'database_stats' => $this->systemHealthService->getDatabaseStats(),
            'health_checks' => $this->systemHealthService->getHealthChecks(),
            'horizon_url' => $this->getHorizonUrl(),
            'spatie_health_url' => $this->getHealthUrl(),
        ];

        return view('algoexpert-plus::backend.system-health', $data);
    }

    /**
     * Check SEO package status
     */
    protected function checkSeoStatus(): bool
    {
        return class_exists(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class);
    }

    /**
     * Get SEO status message
     */
    protected function getSeoStatusMessage(): string
    {
        if ($this->checkSeoStatus()) {
            return 'Package installed and ready';
        }
        return 'Package not installed. Run: composer install';
    }

    /**
     * Check queues/Horizon status
     */
    protected function checkQueuesStatus(): bool
    {
        // Use config() instead of env() to get cached value, but clear cache was called
        $queueConnection = config('queue.default', env('QUEUE_CONNECTION', 'database'));
        return $queueConnection === 'redis' 
            && class_exists(\Laravel\Horizon\HorizonServiceProvider::class);
    }

    /**
     * Get queues status message
     */
    protected function getQueuesStatusMessage(): string
    {
        // Use config() to get current value (after cache clear)
        $queueConnection = config('queue.default', env('QUEUE_CONNECTION', 'database'));
        $hasRedis = $queueConnection === 'redis';
        $hasHorizon = class_exists(\Laravel\Horizon\HorizonServiceProvider::class);
        
        if ($hasRedis && $hasHorizon) {
            return 'Horizon ready (Redis queue enabled)';
        }
        
        if (!$hasHorizon) {
            return 'Package not installed. Run: composer install';
        }
        
        if (!$hasRedis) {
            return 'Requires QUEUE_CONNECTION=redis in .env';
        }
        
        return 'Configuration required';
    }

    /**
     * Get Horizon dashboard URL
     */
    protected function getHorizonUrl(): ?string
    {
        if ($this->checkQueuesStatus() && \Illuminate\Support\Facades\Route::has('horizon.index')) {
            return route('horizon.index');
        }
        return null;
    }

    /**
     * Get health dashboard URL
     */
    protected function getHealthUrl(): ?string
    {
        if ($this->healthService->isAvailable() && \Illuminate\Support\Facades\Route::has('health')) {
            return route('health');
        }
        return null;
    }

    /**
     * Install dependencies for a module
     */
    public function installDependencies(Request $request)
    {
        $module = $request->input('module');
        $action = $request->input('action'); // 'install', 'config', or 'install-all'

        // Support both AJAX and regular requests
        $isAjax = $request->expectsJson() || $request->ajax();

        if ($action === 'install-all') {
            // Install all missing packages
            $dependencies = $this->dependencyService->getModuleDependencies();
            $packages = [];
            foreach ($dependencies as $dep) {
                if (!$this->dependencyService->isPackageInstalled($dep['package'])) {
                    $packages[] = $dep['package'];
                }
            }
            
            if (empty($packages)) {
                $message = 'All packages are already installed';
                if ($isAjax) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                return redirect()->route('admin.algoexpert-plus.index')->with('info', $message);
            }
            
            $result = $this->dependencyService->installPackages($packages);
            
            if ($isAjax) {
                return response()->json($result);
            }
            
            if ($result['success']) {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('error', $result['message']);
            }
        }

        $dependencies = $this->dependencyService->getModuleDependencies();
        $dep = $dependencies[$module] ?? null;

        if (!$dep) {
            $message = 'Invalid module';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return redirect()->route('admin.algoexpert-plus.index')->with('error', $message);
        }

        if ($action === 'install') {
            $result = $this->dependencyService->installPackages([$dep['package']]);
            
            if ($isAjax) {
                return response()->json($result);
            }
            
            if ($result['success']) {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('error', $result['message']);
            }
        }

        if ($action === 'config' && $dep['config']) {
            $result = $this->dependencyService->updateEnvConfig(
                $dep['config']['env_key'],
                $dep['config']['required_value']
            );
            
            if ($isAjax) {
                return response()->json($result);
            }
            
            if ($result['success']) {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.algoexpert-plus.index')
                    ->with('error', $result['message']);
            }
        }

        $message = 'Invalid action';
        if ($isAjax) {
            return response()->json(['success' => false, 'message' => $message], 400);
        }
        return redirect()->route('admin.algoexpert-plus.index')->with('error', $message);
    }
}
