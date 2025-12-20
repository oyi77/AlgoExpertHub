<?php

namespace Tests\Unit\Refactoring;

use Tests\TestCase;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use ReflectionClass;

/**
 * Test ExchangeConnectionController trait integration
 * 
 * Ensures all traits are properly loaded and methods are accessible
 */
class ExchangeConnectionControllerTraitTest extends TestCase
{
    protected ExchangeConnectionController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $connectionService = $this->createMock(ExchangeConnectionService::class);
        
        $this->controller = new ExchangeConnectionController($connectionService);
    }

    /**
     * Test that ExchangeConnectionController uses all required traits
     */
    public function test_controller_uses_all_required_traits(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $traits = $reflection->getTraitNames();
        
        $expectedTraits = [
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCrudOperations',
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesTestingOperations',
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\ProvidesHelperMethods',
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesMetaApiOperations',
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesStreamingOperations',
            'Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\Traits\HandlesCopyTrading',
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
     * Test that HandlesCrudOperations methods are accessible
     */
    public function test_handles_crud_operations_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'index'),
            'index() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'create'),
            'create() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'store'),
            'store() method should exist'
        );
    }

    /**
     * Test that HandlesTestingOperations methods are accessible
     */
    public function test_handles_testing_operations_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'testConnection'),
            'testConnection() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'testDataFetch'),
            'testDataFetch() method should exist'
        );
    }

    /**
     * Test that HandlesMetaApiOperations methods are accessible
     */
    public function test_handles_meta_api_operations_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'addAccount'),
            'addAccount() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'getStatus'),
            'getStatus() method should exist'
        );
    }

    /**
     * Test that HandlesStreamingOperations methods are accessible
     */
    public function test_handles_streaming_operations_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'streamMarketData'),
            'streamMarketData() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'streamPositions'),
            'streamPositions() method should exist'
        );
    }

    /**
     * Test that HandlesCopyTrading methods are accessible
     */
    public function test_handles_copy_trading_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->controller, 'toggleCopyTrading'),
            'toggleCopyTrading() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->controller, 'getCopyTradingStats'),
            'getCopyTradingStats() method should exist'
        );
    }

    /**
     * Test that controller has required protected property
     */
    public function test_controller_has_required_property(): void
    {
        $reflection = new ReflectionClass($this->controller);
        
        $this->assertTrue(
            $reflection->hasProperty('connectionService'),
            'Controller should have $connectionService property'
        );
    }

    /**
     * Test that controller can be instantiated
     */
    public function test_controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(
            ExchangeConnectionController::class,
            $this->controller,
            'Controller should be instance of ExchangeConnectionController'
        );
    }
}

