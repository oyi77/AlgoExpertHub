<?php

namespace Tests\Feature\Trading;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use App\Actions\Trading\ExecuteManualTradeAction;

class ManualTradeTest extends TestCase
{
    // usage of RefreshDatabase might be risky if migrations are not up to date or if using a shared DB. 
    // Given the environment, I'll avoid RefreshDatabase and use transactions if possible, or just be careful.
    // But standard Laravel tests use RefreshDatabase. I'll check if sqlite is configured in phpunit.xml later.
    // For now, I'll assume standard behavior.
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Bind the testable action
        $this->app->bind(ExecuteManualTradeAction::class, TestableExecuteManualTradeAction::class);
    }

    public function test_manual_trade_execution_flow()
    {
        // 1. Create User
        $user = User::factory()->create();
        
        // 2. Mock Connection in DB (using raw DB to avoid dependency on Addon models existence in test env if not autoloaded properly, 
        // but they should be autoloaded. I'll try to use the classes if they exist)
        
        $connectionId = \DB::table('execution_connections')->insertGetId([
            'user_id' => $user->id,
            'is_active' => 1,
            'is_admin_owned' => 0,
            'type' => 'crypto',
            'exchange_name' => 'binance',
            'credentials' => json_encode(['api_key' => 'test', 'secret' => 'test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Make Request
        $response = $this->actingAs($user)
            ->postJson('/api/user/trading-operations/manual-trade', [
                'connection_id' => $connectionId,
                'symbol' => 'BTC/USDT',
                'direction' => 'BUY',
                'lot_size' => 0.1,
                'order_type' => 'market',
            ]);

        // 4. Assertions
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Trade executed successfully',
                'data' => [
                    'symbol' => 'BTC/USDT',
                    'status' => 'SUCCESS',
                ]
            ]);
            
        // Verify Log created
        $this->assertDatabaseHas('execution_logs', [
            'connection_id' => $connectionId,
            'symbol' => 'BTC/USDT',
            'status' => 'executed'
        ]);
    }
}

// Testable Helper
class TestableExecuteManualTradeAction extends ExecuteManualTradeAction
{
    protected function getAdapter($connection)
    {
        return new class {
            public function placeOrder($symbol, $direction, $lot, $type, $entry, $sl, $tp, $notes) {
                return [
                    'success' => true,
                    'orderId' => 'TEST-ORDER-123',
                    'data' => [
                        'price' => 50000,
                        'orderId' => 'TEST-ORDER-123'
                    ]
                ];
            }
        };
    }
}
