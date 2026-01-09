<?php

namespace Tests\Feature\TradingBot;

use Tests\TestCase;
use App\Models\User;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

/**
 * Crypto Bot Creation Test
 * 
 * Tests the creation flow for crypto exchange bots (Binance, Bybit, OKX)
 * Covers validation, credential handling, and error scenarios
 */
class CryptoBotCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TradingBotService $botService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->botService = app(TradingBotService::class);
    }

    /**
     * Test: Happy path - Create bot with valid Binance connection
     */
    public function test_can_create_bot_with_valid_binance_connection(): void
    {
        // Create active Binance connection with valid credentials
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My Binance Account',
            'provider' => 'binance',
            'exchange_name' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_api_key_123',
                'api_secret' => 'test_api_secret_456',
            ],
        ]);

        // Create trading preset
        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        // Create bot via service
        $bot = $this->botService->create([
            'name' => 'My Binance Bot',
            'description' => 'Test bot for Binance',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);

        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('My Binance Bot', $bot->name);
        $this->assertEquals($connection->id, $bot->exchange_connection_id);
        $this->assertEquals($this->user->id, $bot->user_id);
        $this->assertTrue($bot->is_paper_trading);
    }

    /**
     * Test: Happy path - Create bot with valid Bybit connection
     */
    public function test_can_create_bot_with_valid_bybit_connection(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My Bybit Account',
            'provider' => 'bybit',
            'exchange_name' => 'bybit',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_api_key_789',
                'api_secret' => 'test_api_secret_012',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $bot = $this->botService->create([
            'name' => 'My Bybit Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);

        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('My Bybit Bot', $bot->name);
    }

    /**
     * Test: Happy path - Create bot with valid OKX connection (requires passphrase)
     */
    public function test_can_create_bot_with_valid_okx_connection_with_passphrase(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My OKX Account',
            'provider' => 'okx',
            'exchange_name' => 'okx',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_api_key_okx',
                'api_secret' => 'test_api_secret_okx',
                'api_passphrase' => 'test_passphrase_okx',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $bot = $this->botService->create([
            'name' => 'My OKX Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);

        $this->assertInstanceOf(TradingBot::class, $bot);
        $this->assertEquals('My OKX Bot', $bot->name);
    }

    /**
     * Test: Failure case - Missing passphrase for OKX
     */
    public function test_cannot_create_bot_with_okx_connection_missing_passphrase(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My OKX Account',
            'provider' => 'okx',
            'exchange_name' => 'okx',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_api_key_okx',
                'api_secret' => 'test_api_secret_okx',
                // Missing api_passphrase
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API passphrase');

        $this->botService->create([
            'name' => 'My OKX Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);
    }

    /**
     * Test: Failure case - Inactive connection
     */
    public function test_cannot_create_bot_with_inactive_connection(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My Inactive Connection',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'inactive',
            'is_active' => false, // Inactive
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not active');

        $this->botService->create([
            'name' => 'My Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);
    }

    /**
     * Test: Failure case - Connection status not 'active'
     */
    public function test_cannot_create_bot_with_connection_status_not_active(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My Error Connection',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'error', // Status is error, not active
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not ready');

        $this->botService->create([
            'name' => 'My Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);
    }

    /**
     * Test: Failure case - Missing API credentials
     */
    public function test_cannot_create_bot_with_missing_api_credentials(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'name' => 'My Connection Without Credentials',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                // Missing api_key and api_secret
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('missing required API credentials');

        $this->botService->create([
            'name' => 'My Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);
    }

    /**
     * Test: Failure case - Duplicate bot name
     */
    public function test_cannot_create_bot_with_duplicate_name(): void
    {
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'is_admin_owned' => false,
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        // Create first bot
        TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Duplicate Name Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
        ]);

        // Try to create second bot with same name
        $response = $this->post(route('user.trading-management.trading-bots.store'), [
            'name' => 'Duplicate Name Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test: Failure case - Connection belongs to another user
     */
    public function test_cannot_create_bot_with_another_users_connection(): void
    {
        $otherUser = User::factory()->create();
        
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $otherUser->id, // Different user
            'is_admin_owned' => false,
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
            'trade_execution_enabled' => true,
            'credentials' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
            ],
        ]);

        $preset = TradingPreset::factory()->create([
            'enabled' => true,
            'visibility' => 'PUBLIC_MARKETPLACE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not belong to you');

        $this->botService->create([
            'name' => 'My Bot',
            'exchange_connection_id' => $connection->id,
            'trading_preset_id' => $preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ]);
    }
}

