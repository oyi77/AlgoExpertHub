<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TradingBotLifecycleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected ExchangeConnection $connection;
    protected TradingPreset $preset;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create test exchange connection
        $this->connection = ExchangeConnection::create([
            'user_id' => $this->user->id,
            'name' => 'Test Binance Connection',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'credentials' => encrypt([
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
            ]),
            'is_active' => true,
        ]);

        // Create test trading preset
        $this->preset = TradingPreset::create([
            'user_id' => $this->user->id,
            'name' => 'Test Preset',
            'risk_per_trade' => 1.0,
            'max_daily_trades' => 10,
            'max_daily_loss' => 100,
        ]);
    }

    /** @test */
    public function can_create_trading_bot()
    {
        $this->actingAs($this->user);

        $botData = [
            'name' => 'Test Trading Bot',
            'description' => 'A test bot for automated testing',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'is_paper_trading' => true,
        ];

        $bot = TradingBot::create(array_merge($botData, [
            'user_id' => $this->user->id,
            'status' => 'stopped',
            'is_active' => false,
        ]));

        $this->assertDatabaseHas('trading_bots', [
            'id' => $bot->id,
            'name' => 'Test Trading Bot',
            'user_id' => $this->user->id,
            'status' => 'stopped',
        ]);

        $this->assertEquals('Test Trading Bot', $bot->name);
        $this->assertEquals($this->user->id, $bot->user_id);
        $this->assertTrue($bot->isStopped());
    }

    /** @test */
    public function can_start_trading_bot()
    {
        $bot = TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'stopped',
            'is_active' => false,
        ]);

        // Start the bot
        $bot->update(['status' => 'running', 'is_active' => true]);

        $this->assertTrue($bot->fresh()->isRunning());
        $this->assertTrue($bot->fresh()->isActive());
    }

    /** @test */
    public function can_stop_trading_bot()
    {
        $bot = TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'running',
            'is_active' => true,
        ]);

        // Stop the bot
        $bot->update(['status' => 'stopped', 'is_active' => false]);

        $this->assertTrue($bot->fresh()->isStopped());
        $this->assertFalse($bot->fresh()->isActive());
    }

    /** @test */
    public function can_pause_and_resume_trading_bot()
    {
        $bot = TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'running',
            'is_active' => true,
        ]);

        // Pause the bot
        $bot->update(['status' => 'paused']);
        $this->assertTrue($bot->fresh()->isPaused());

        // Resume the bot
        $bot->update(['status' => 'running']);
        $this->assertTrue($bot->fresh()->isRunning());
    }

    /** @test */
    public function bot_requires_exchange_connection()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => null, // Missing connection
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'stopped',
        ]);
    }

    /** @test */
    public function can_delete_stopped_bot()
    {
        $bot = TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'stopped',
        ]);

        $botId = $bot->id;
        $bot->delete();

        $this->assertDatabaseMissing('trading_bots', ['id' => $botId]);
    }

    /** @test */
    public function bot_tracks_execution_statistics()
    {
        $bot = TradingBot::create([
            'user_id' => $this->user->id,
            'name' => 'Test Bot',
            'exchange_connection_id' => $this->connection->id,
            'trading_preset_id' => $this->preset->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'stopped',
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
        ]);

        // Simulate successful execution
        $bot->increment('total_executions');
        $bot->increment('successful_executions');

        $this->assertEquals(1, $bot->fresh()->total_executions);
        $this->assertEquals(1, $bot->fresh()->successful_executions);

        // Simulate failed execution
        $bot->increment('total_executions');
        $bot->increment('failed_executions');

        $this->assertEquals(2, $bot->fresh()->total_executions);
        $this->assertEquals(1, $bot->fresh()->failed_executions);
    }
}
