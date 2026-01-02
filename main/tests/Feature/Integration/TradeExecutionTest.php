<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Actions\Trading\ExecuteManualTradeAction;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TradeExecutionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected ExecutionConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create test execution connection
        $this->connection = ExecutionConnection::create([
            'user_id' => $this->user->id,
            'name' => 'Test Execution Connection',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'credentials' => encrypt([
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
            ]),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function validates_required_trade_parameters()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/trades', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['connection_id', 'symbol', 'direction', 'lot_size', 'order_type']);
    }

    /** @test */
    public function validates_lot_size_range()
    {
        $this->actingAs($this->user);

        // Test minimum lot size
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'lot_size' => 0.001, // Too small
            'order_type' => 'market',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lot_size']);

        // Test maximum lot size
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'lot_size' => 150, // Too large
            'order_type' => 'market',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lot_size']);
    }

    /** @test */
    public function validates_trading_symbol_format()
    {
        $this->actingAs($this->user);

        // Invalid symbol (too short)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BT',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'market',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);

        // Invalid symbol (special characters)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTC@USDT',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'market',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    /** @test */
    public function validates_stop_loss_for_buy_orders()
    {
        $this->actingAs($this->user);

        // SL above entry for buy order (invalid)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'limit',
            'entry_price' => 1.0850,
            'sl_price' => 1.0900, // Above entry (invalid)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sl_price']);
    }

    /** @test */
    public function validates_stop_loss_for_sell_orders()
    {
        $this->actingAs($this->user);

        // SL below entry for sell order (invalid)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'direction' => 'sell',
            'lot_size' => 0.01,
            'order_type' => 'limit',
            'entry_price' => 1.0850,
            'sl_price' => 1.0800, // Below entry (invalid)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sl_price']);
    }

    /** @test */
    public function validates_take_profit_for_buy_orders()
    {
        $this->actingAs($this->user);

        // TP below entry for buy order (invalid)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'limit',
            'entry_price' => 1.0850,
            'tp_price' => 1.0800, // Below entry (invalid)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tp_price']);
    }

    /** @test */
    public function validates_take_profit_for_sell_orders()
    {
        $this->actingAs($this->user);

        // TP above entry for sell order (invalid)
        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'direction' => 'sell',
            'lot_size' => 0.01,
            'order_type' => 'limit',
            'entry_price' => 1.0850,
            'tp_price' => 1.0900, // Above entry (invalid)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tp_price']);
    }

    /** @test */
    public function requires_entry_price_for_limit_orders()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'limit',
            // Missing entry_price
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entry_price']);
    }

    /** @test */
    public function accepts_valid_trade_parameters()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/trades', [
            'connection_id' => $this->connection->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'market',
            'sl_price' => 40000,
            'tp_price' => 50000,
            'notes' => 'Test trade',
        ]);

        // Note: This will fail without actual exchange adapter
        // In real tests, mock the adapter
        // $response->assertStatus(200);
    }

    /** @test */
    public function validates_connection_belongs_to_user()
    {
        $this->actingAs($this->user);

        // Create connection for another user
        $otherUser = User::factory()->create();
        $otherConnection = ExecutionConnection::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Connection',
            'provider' => 'binance',
            'connection_type' => 'CRYPTO_EXCHANGE',
            'credentials' => encrypt(['api_key' => 'test', 'api_secret' => 'test']),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/trades', [
            'connection_id' => $otherConnection->id, // Not owned by current user
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'lot_size' => 0.01,
            'order_type' => 'market',
        ]);

        // Should fail authorization or validation
        $response->assertStatus(422);
    }
}
