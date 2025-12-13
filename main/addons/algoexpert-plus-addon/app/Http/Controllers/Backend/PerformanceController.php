<?php

namespace Addons\AlgoExpertPlus\App\Http\Controllers\Backend;

use Addons\AlgoExpertPlus\App\Http\Controllers\Controller;
use Addons\AlgoExpertPlus\App\Services\SystemHealthService;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    protected $systemHealthService;

    public function __construct(SystemHealthService $systemHealthService)
    {
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

        $data = [
            'title' => 'Performance Settings',
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
