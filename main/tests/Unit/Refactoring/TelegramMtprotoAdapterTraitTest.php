<?php

namespace Tests\Unit\Refactoring;

use Tests\TestCase;
use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use ReflectionClass;

/**
 * Test TelegramMtprotoAdapter trait integration
 * 
 * Ensures all traits are properly loaded and methods are accessible
 */
class TelegramMtprotoAdapterTraitTest extends TestCase
{
    protected TelegramMtprotoAdapter $adapter;
    protected ChannelSource $channelSource;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock ChannelSource
        $this->channelSource = $this->createMock(ChannelSource::class);
        $this->channelSource->id = 1;
        $this->channelSource->config = [
            'api_id' => '12345',
            'api_hash' => 'test_hash',
        ];
        $this->channelSource->method('isAdminOwned')->willReturn(false);
        
        $this->adapter = new TelegramMtprotoAdapter($this->channelSource);
    }

    /**
     * Test that TelegramMtprotoAdapter uses all required traits
     */
    public function test_adapter_uses_all_required_traits(): void
    {
        $reflection = new ReflectionClass($this->adapter);
        $traits = $reflection->getTraitNames();
        
        $expectedTraits = [
            'Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesConnection',
            'Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesMessages',
            'Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesAuthentication',
            'Addons\MultiChannelSignalAddon\App\Adapters\Traits\HandlesChannelManagement',
        ];
        
        foreach ($expectedTraits as $expectedTrait) {
            $this->assertContains(
                $expectedTrait,
                $traits,
                "Adapter should use trait: {$expectedTrait}"
            );
        }
    }

    /**
     * Test that HandlesConnection methods are accessible
     */
    public function test_handles_connection_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->adapter, 'connect'),
            'connect() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'disconnect'),
            'disconnect() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'validateConfig'),
            'validateConfig() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'getType'),
            'getType() method should exist'
        );
    }

    /**
     * Test that HandlesMessages methods are accessible
     */
    public function test_handles_messages_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->adapter, 'fetchMessages'),
            'fetchMessages() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'fetchSampleMessages'),
            'fetchSampleMessages() method should exist'
        );
    }

    /**
     * Test that HandlesAuthentication methods are accessible
     */
    public function test_handles_authentication_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->adapter, 'startAuth'),
            'startAuth() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'completeAuth'),
            'completeAuth() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'completePasswordAuth'),
            'completePasswordAuth() method should exist'
        );
    }

    /**
     * Test that HandlesChannelManagement methods are accessible
     */
    public function test_handles_channel_management_methods_accessible(): void
    {
        $this->assertTrue(
            method_exists($this->adapter, 'getDialogs'),
            'getDialogs() method should exist'
        );
        
        $this->assertTrue(
            method_exists($this->adapter, 'getDialogsChunked'),
            'getDialogsChunked() method should exist'
        );
    }

    /**
     * Test that adapter has required protected properties
     */
    public function test_adapter_has_required_properties(): void
    {
        $reflection = new ReflectionClass($this->adapter);
        
        $this->assertTrue(
            $reflection->hasProperty('madeline'),
            'Adapter should have $madeline property'
        );
        
        $this->assertTrue(
            $reflection->hasProperty('sessionFile'),
            'Adapter should have $sessionFile property'
        );
    }

    /**
     * Test that adapter can be instantiated
     */
    public function test_adapter_can_be_instantiated(): void
    {
        $this->assertInstanceOf(
            TelegramMtprotoAdapter::class,
            $this->adapter,
            'Adapter should be instance of TelegramMtprotoAdapter'
        );
    }

    /**
     * Test getType returns correct value
     */
    public function test_get_type_returns_correct_value(): void
    {
        $type = $this->adapter->getType();
        
        $this->assertEquals(
            'telegram_mtproto',
            $type,
            'getType() should return "telegram_mtproto"'
        );
    }

    /**
     * Test validateConfig works correctly
     */
    public function test_validate_config_works_correctly(): void
    {
        $validConfig = [
            'api_id' => '12345',
            'api_hash' => 'test_hash',
        ];
        
        $this->assertTrue(
            $this->adapter->validateConfig($validConfig),
            'validateConfig() should return true for valid config'
        );
        
        $invalidConfig = [
            'api_id' => '',
            'api_hash' => 'test_hash',
        ];
        
        $this->assertFalse(
            $this->adapter->validateConfig($invalidConfig),
            'validateConfig() should return false for invalid config'
        );
    }
}

