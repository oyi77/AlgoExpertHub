<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\ConfigurationService;
use App\Services\DatabaseBackupService;
use App\Services\ThemeManager;
use App\Http\Controllers\Backend\Traits\HandlesGeneralSettings;
use App\Http\Controllers\Backend\Traits\HandlesSystemStatus;
use App\Http\Controllers\Backend\Traits\HandlesPerformanceOptimization;
use App\Http\Controllers\Backend\Traits\HandlesThemeManagement;
use App\Http\Controllers\Backend\Traits\HandlesDatabaseBackup;
use App\Http\Controllers\Backend\Traits\HandlesDatabaseManagement;

/**
 * Configuration Controller
 * 
 * Manages application settings, system status, performance optimization, themes, and database operations
 * 
 * This controller has been refactored into traits for better organization:
 * - HandlesGeneralSettings: General configuration (index, ConfigurationUpdate, cacheClear)
 * - HandlesSystemStatus: System monitoring (getSystemStatus, streamSystemStatus, getSystemInfo, getOpcacheStatus, getProcessInfo, getHorizonStats, getQueueStats, getCronJobs, getScheduledTasksInfo)
 * - HandlesPerformanceOptimization: Performance optimization (performanceOptimize, performanceClear, performanceAssets, performanceHttp, performanceMedia, performanceCache, performanceDatabase, performancePrewarm, getPerformanceTips, analyzeNPlusOneQueries, analyzeDatabaseIndexes, analyzeCacheUsage, analyzePaginationUsage, analyzeChunkingUsage, scanModels, scanControllers, extractClassName)
 * - HandlesThemeManagement: Theme management (manageTheme, themeUpdate, themeColor, themeUpload, themePageBuilder, themeDownloadTemplate, themeDelete, backendThemeUpdate, themeDeactivate)
 * - HandlesDatabaseBackup: Database backup operations (createBackup, loadBackup, deleteBackup, saveAsFactoryState, loadFactoryState)
 * - HandlesDatabaseManagement: Database management (reseedDatabase, resetDatabase, getSeederCount)
 */
class ConfigurationController extends Controller
{
    use HandlesGeneralSettings,
        HandlesSystemStatus,
        HandlesPerformanceOptimization,
        HandlesThemeManagement,
        HandlesDatabaseBackup,
        HandlesDatabaseManagement;

    protected $config;
    protected $themeManager;
    protected $backupService;

    public function __construct(ConfigurationService $config, ThemeManager $themeManager, DatabaseBackupService $backupService)
    {
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->backupService = $backupService;
    }
}
