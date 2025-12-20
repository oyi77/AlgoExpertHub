<?php

namespace Tests\Unit\Refactoring;

use Tests\TestCase;
use App\Http\Controllers\Backend\ConfigurationController;
use App\Services\ConfigurationService;
use App\Services\ThemeManager;
use App\Services\DatabaseBackupService;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test ConfigurationController trait integration
 * 
 * Ensures all traits are properly loaded and methods are accessible
 */
class ConfigurationControllerTraitTest extends TestCase
{
    protected ConfigurationController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $configService = $this->createMock(ConfigurationService::class);
        $themeManager = $this->createMock(ThemeManager::class);
        $backupService = $this->createMock(DatabaseBackupService::class);
        
        $this->controller = new ConfigurationController(
            $configService,
            $themeManager,
            $backupService
        );
    }

    /**
     * Test that ConfigurationController uses all required traits
     */
    public function test_controller_uses_all_required_traits(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $traits = $reflection->getTraitNames();
        
        $expectedTraits = [
            'App\Http\Controllers\Backend\Traits\HandlesGeneralSettings',
            'App\Http\Controllers\Backend\Traits\HandlesSystemStatus',
            'App\Http\Controllers\Backend\Traits\HandlesPerformanceOptimization',
            'App\Http\Controllers\Backend\Traits\HandlesThemeManagement',
            'App\Http\Controllers\Backend\Traits\HandlesDatabaseBackup',
            'App\Http\Controllers\Backend\Traits\HandlesDatabaseManagement',
        ];
        
        foreach ($expectedTraits as $expectedTrait) {
            $this->assertContains(
                $expectedTrait,
                $traits,
                "Controller should use trait: {$expectedTrait}"
            );
        }
    }

    /**
     * Test that HandlesGeneralSettings methods are accessible
     */
    public function test_handles_general_settings_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'index'),
            'index() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'cacheClear'),
            'cacheClear() method should exist'
        );
    }

    /**
     * Test that HandlesSystemStatus methods are accessible
     */
    public function test_handles_system_status_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'getSystemStatus'),
            'getSystemStatus() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'streamSystemStatus'),
            'streamSystemStatus() method should exist'
        );
    }

    /**
     * Test that HandlesPerformanceOptimization methods are accessible
     */
    public function test_handles_performance_optimization_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'performanceOptimize'),
            'performanceOptimize() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'performanceClear'),
            'performanceClear() method should exist'
        );
    }

    /**
     * Test that HandlesThemeManagement methods are accessible
     */
    public function test_handles_theme_management_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'manageTheme'),
            'manageTheme() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'themeUpdate'),
            'themeUpdate() method should exist'
        );
    }

    /**
     * Test that HandlesDatabaseBackup methods are accessible
     */
    public function test_handles_database_backup_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'createBackup'),
            'createBackup() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'loadBackup'),
            'loadBackup() method should exist'
        );
    }

    /**
     * Test that HandlesDatabaseManagement methods are accessible
     */
    public function test_handles_database_management_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'reseedDatabase'),
            'reseedDatabase() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'resetDatabase'),
            'resetDatabase() method should exist'
        );
    }

    /**
     * Test that controller has required protected properties
     */
    public function test_controller_has_required_properties(): void
    {
        $reflection = new ReflectionClass($this->controller);
        
        $this->assertTrue(
            $reflection->hasProperty('config'),
            'Controller should have $config property'
        );
        
        $this->assertTrue(
            $reflection->hasProperty('themeManager'),
            'Controller should have $themeManager property'
        );
        
        $this->assertTrue(
            $reflection->hasProperty('backupService'),
            'Controller should have $backupService property'
        );
    }

    /**
     * Test that controller can be instantiated
     */
    public function test_controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(
            ConfigurationController::class,
            $this->controller,
            'Controller should be instance of ConfigurationController'
        );
    }
}

