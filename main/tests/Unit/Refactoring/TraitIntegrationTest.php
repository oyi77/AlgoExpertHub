<?php

namespace Tests\Unit\Refactoring;

use Tests\TestCase;
use ReflectionClass;

/**
 * Integration test to ensure all refactored traits work together
 * 
 * This test verifies that the trait-based refactoring maintains
 * all functionality and doesn't break existing code
 */
class TraitIntegrationTest extends TestCase
{
    /**
     * Test that all trait files exist and are loadable
     */
    public function test_all_trait_files_exist(): void
    {
        $traitFiles = [
            // ConfigurationController traits
            app_path('Http/Controllers/Backend/Traits/HandlesGeneralSettings.php'),
            app_path('Http/Controllers/Backend/Traits/HandlesSystemStatus.php'),
            app_path('Http/Controllers/Backend/Traits/HandlesPerformanceOptimization.php'),
            app_path('Http/Controllers/Backend/Traits/HandlesThemeManagement.php'),
            app_path('Http/Controllers/Backend/Traits/HandlesDatabaseBackup.php'),
            app_path('Http/Controllers/Backend/Traits/HandlesDatabaseManagement.php'),
            
            // ExchangeConnectionController traits
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/HandlesCrudOperations.php'),
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/HandlesTestingOperations.php'),
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/ProvidesHelperMethods.php'),
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/HandlesMetaApiOperations.php'),
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/HandlesStreamingOperations.php'),
            base_path('main/addons/trading-management-addon/Modules/ExchangeConnection/Controllers/Backend/Traits/HandlesCopyTrading.php'),
            
            // TelegramMtprotoAdapter traits
            base_path('main/addons/multi-channel-signal-addon/app/Adapters/Traits/HandlesConnection.php'),
            base_path('main/addons/multi-channel-signal-addon/app/Adapters/Traits/HandlesMessages.php'),
            base_path('main/addons/multi-channel-signal-addon/app/Adapters/Traits/HandlesAuthentication.php'),
            base_path('main/addons/multi-channel-signal-addon/app/Adapters/Traits/HandlesChannelManagement.php'),
        ];
        
        foreach ($traitFiles as $traitFile) {
            $this->assertFileExists(
                $traitFile,
                "Trait file should exist: {$traitFile}"
            );
        }
    }

    /**
     * Test that all trait classes can be loaded
     */
    public function test_all_trait_classes_can_be_loaded(): void
    {
        $traitClasses = [
            // ConfigurationController traits
            \App\Http\Controllers\Backend\Traits\HandlesGeneralSettings::class,
            \App\Http\Controllers\Backend\Traits\HandlesSystemStatus::class,
            \App\Http\Controllers\Backend\Traits\HandlesPerformanceOptimization::class,
            \App\Http\Controllers\Backend\Traits\HandlesThemeManagement::class,
            \App\Http\Controllers\Backend\Traits\HandlesDatabaseBackup::class,
            \App\Http\Controllers\Backend\Traits\HandlesDatabaseManagement::class,
            
            // ExchangeConnectionController traits
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCrudOperations::class,
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesTestingOperations::class,
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\ProvidesHelperMethods::class,
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesMetaApiOperations::class,
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesStreamingOperations::class,
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCopyTrading::class,
            
            // TelegramMtprotoAdapter traits
            \Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesConnection::class,
            \Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesMessages::class,
            \Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesAuthentication::class,
            \Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesChannelManagement::class,
        ];
        
        foreach ($traitClasses as $traitClass) {
            $this->assertTrue(
                trait_exists($traitClass),
                "Trait class should exist: {$traitClass}"
            );
        }
    }

    /**
     * Test that refactored controllers can be instantiated
     */
    public function test_refactored_controllers_can_be_instantiated(): void
    {
        // Test ConfigurationController
        $configService = $this->createMock(\App\Services\ConfigurationService::class);
        $themeManager = $this->createMock(\App\Services\ThemeManager::class);
        $backupService = $this->createMock(\App\Services\DatabaseBackupService::class);
        
        $configController = new \App\Http\Controllers\Backend\ConfigurationController(
            $configService,
            $themeManager,
            $backupService
        );
        
        $this->assertInstanceOf(
            \App\Http\Controllers\Backend\ConfigurationController::class,
            $configController
        );
        
        // Test ExchangeConnectionController
        $connectionService = $this->createMock(\Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService::class);
        
        $exchangeController = new \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController(
            $connectionService
        );
        
        $this->assertInstanceOf(
            \Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class,
            $exchangeController
        );
    }

    /**
     * Test that refactored adapter can be instantiated
     */
    public function test_refactored_adapter_can_be_instantiated(): void
    {
        $channelSource = $this->createMock(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class);
        $channelSource->id = 1;
        $channelSource->config = [];
        $channelSource->method('isAdminOwned')->willReturn(false);
        
        $adapter = new \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter($channelSource);
        
        $this->assertInstanceOf(
            \Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter::class,
            $adapter
        );
    }

    /**
     * Test that no method conflicts exist between traits
     */
    public function test_no_method_conflicts_between_traits(): void
    {
        // Get all methods from ConfigurationController
        $configReflection = new ReflectionClass(\App\Http\Controllers\Backend\ConfigurationController::class);
        $configMethods = $configReflection->getMethods();
        $configMethodNames = array_map(fn($m) => $m->getName(), $configMethods);
        
        // Check for duplicate method names (excluding inherited methods)
        $uniqueMethods = array_unique($configMethodNames);
        $this->assertEquals(
            count($configMethodNames),
            count($uniqueMethods),
            'No duplicate method names should exist in ConfigurationController'
        );
        
        // Get all methods from ExchangeConnectionController
        $exchangeReflection = new ReflectionClass(\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class);
        $exchangeMethods = $exchangeReflection->getMethods();
        $exchangeMethodNames = array_map(fn($m) => $m->getName(), $exchangeMethods);
        
        // Check for duplicate method names
        $uniqueExchangeMethods = array_unique($exchangeMethodNames);
        $this->assertEquals(
            count($exchangeMethodNames),
            count($uniqueExchangeMethods),
            'No duplicate method names should exist in ExchangeConnectionController'
        );
    }
}

