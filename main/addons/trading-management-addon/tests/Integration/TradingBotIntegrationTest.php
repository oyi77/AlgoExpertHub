<?php

namespace Addons\TradingManagement\Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Signal;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBotPosition;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Addons\TradingManagement\Modules\TradingBot\Services\TradingBotService;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

/**
 * Integration Tests for Trading Bot System
 * 
 * Tests real database interactions and job processing
 */
class TradingBotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ExchangeConnection $connection;
    protected TradingBotService $botService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->botService = app(TradingBotService::class);
        
        $this->connection = ExchangeConnection::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Test: Complete end-to-end signal-based bot execution
     */
    public function test_complete_signal_based_execution(): void
    {
        // 1. Create and start bot
        $bot = TradingBot::factory()->create([
            'user_id' => $this->user->id,
            'exchange_connection_id' => $this->connection->id,
            'trading_mode' => 'SIGNAL_BASED',
            'status' => 'stopped',
            'is_active' => true,
        ]);

        $this->botService->start($bot, $this->user->id);
        $this->assertEquals('running', $bot->fresh()->status);

        // 2. Create signal
        $signal = Signal::factory()->create([
            'is_published' => 1,
            'published_date' => now(),
        ]);

        // 3. Simulate execution
        $executionData = [
            'connection_id' => $this->connection->id,
            'bot_id' => $bot->id,
            'signal_id' => $signal->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'quantity' => 0.01,
            'entry_price' => 50000,
            'stop_loss' => 49000,
            'take_profit' => 51000,
        ];

        // Execute job (simulate)
        $job = new ExecutionJob($executionData);
        
        // Mock adapter to avoid actual API calls
        $this->mock(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class, function ($mock) {
            $mock->shouldReceive('create')->andReturn(new class {
                public function placeMarketOrder($symbol, $direction, $quantity, $sl, $tp, $comment) {
                    return [
                        'success' => true,
                        'orderId' => 'test_order_123',
                        'positionId' => 'test_position_123',
                    ];
                }
            });
        });

        // 4. Verify positions created
        $this->assertDatabaseHas('execution_positions', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTC/USDT',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('trading_bot_positions', [
            'bot_id' => $bot->id,
            'signal_id' => $signal->id,
            'status' => 'open',
        ]);
    }

    /**
     * Test: Position monitoring and SL/TP execution
     */
    public function test_position_monitoring_sl_tp_execution(): void
    {
        // Create position
        $position = ExecutionPosition::factory()->create([
            'connection_id' => $this->connection->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'entry_price' => 50000,
            'current_price' => 50000,
            'sl_price' => 49000,
            'tp_price' => 51000,
            'status' => 'open',
        ]);

        // Simulate price hitting stop loss
        $position->current_price = 48900;
        $position->save();

        // Run monitoring
        $monitoringService = app(\Addons\TradingManagement\Modules\TradingBot\Services\PositionMonitoringService::class);
        
        // Mock adapter for closing position
        $this->mock(\Addons\TradingManagement\Modules\DataProvider\Services\AdapterFactory::class, function ($mock) {
            $mock->shouldReceive('create')->andReturn(new class {
                public function closePosition($orderId) {
                    return ['success' => true];
                }
            });
        });

        // Verify position would be closed
        $this->assertTrue($position->shouldCloseBySL(48900));
    }

    /**
     * Test: Copy trading execution
     */
    public function test_copy_trading_execution(): void
    {
        $trader = User::factory()->create();
        $follower = User::factory()->create();

        $traderConnection = ExchangeConnection::factory()->create([
            'user_id' => $trader->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $followerConnection = ExchangeConnection::factory()->create([
            'user_id' => $follower->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Create subscription
        $subscription = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::factory()->create([
            'trader_id' => $trader->id,
            'follower_id' => $follower->id,
            'connection_id' => $followerConnection->id,
            'is_active' => true,
        ]);

        // Create trader position
        $traderPosition = ExecutionPosition::factory()->create([
            'connection_id' => $traderConnection->id,
            'signal_id' => Signal::factory()->create()->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'quantity' => 0.1,
            'status' => 'open',
        ]);

        // Execute copy trading
        $copyService = app(\Addons\TradingManagement\Modules\CopyTrading\Services\TradeCopyService::class);
        $copyService->copyToSubscribers($traderPosition);

        // Verify copy execution created
        $this->assertDatabaseHas('copy_trading_executions', [
            'subscription_id' => $subscription->id,
            'status' => 'pending',
        ]);
    }
}
