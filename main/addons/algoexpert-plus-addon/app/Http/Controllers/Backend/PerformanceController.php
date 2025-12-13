<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Services\SystemHealthService;
use App\Services\DatabaseBackupService;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    protected $backupService;
    protected $systemHealthService;

    public function __construct(DatabaseBackupService $backupService, SystemHealthService $systemHealthService)
    {
        $this->backupService = $backupService;
        $this->systemHealthService = $systemHealthService;
    }

    /**
     * Performance optimization page
     * Includes the existing performance view from backend.setting.performance
     */
    public function index(): View
    {
        // Get seeder count
        $seederCount = 0;
        try {
            $seederFile = database_path('seeders/DatabaseSeeder.php');
            if (file_exists($seederFile)) {
                $content = file_get_contents($seederFile);
                $seederCount = substr_count($content, 'Seeder::class') + substr_count($content, 'RolePermission::class');
            }
        } catch (\Exception $e) {
            \Log::warning('Could not count seeders', ['error' => $e->getMessage()]);
        }

        // Get Horizon stats and supervisor status
        $horizonStats = $this->systemHealthService->getHorizonStats();
        $horizonSupervisorStatus = $this->systemHealthService->getHorizonSupervisorStatus();
        
        // Get Octane status
        $octaneStatus = $this->systemHealthService->getOctaneStatus();

        // Get backups with error handling
        $backups = [];
        try {
            $backups = $this->backupService->listBackups();
        } catch (\Throwable $e) {
            \Log::error('Failed to list backups in PerformanceController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue with empty backups array
        }

        $data = [
            'title' => 'Performance Settings',
            'backups' => $backups,
            'performanceTips' => [
                'database' => [],
                'server' => [],
                'code' => []
            ],
            'seederCount' => $seederCount,
            'horizonStats' => $horizonStats,
            'horizonSupervisorStatus' => $horizonSupervisorStatus,
            'octaneStatus' => $octaneStatus,
        ];

        return view('algoexpert-plus::backend.system-tools.performance', $data);
    }
}
