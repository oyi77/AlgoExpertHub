<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\Execution;

use Tests\TestCase;
use Addons\TradingManagement\Modules\Execution\Jobs\ExecutionJob;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaperTradingTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Create minimal ExecutionConnection for tests.
     */
    protected function createPaperConnection(int $userId): ExecutionConnection
    {
        return ExecutionConnection::create([
            'user_id' => $userId,
            'name' => 'Test Paper Connection',
            'type' => 'crypto',
            'exchange_name' => 'binance',
            'credentials' => json_encode(['api_key' => 'test']),
            'status' => 'active',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    protected function createUser(float $balance = 100000): User
    {
        return User::create([
            'username' => 'testuser' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'phone' => '1234567890' . rand(10, 99),
            'password' => bcrypt('password'),
            'balance' => $balance,
            'status' => 1,
            'is_email_verified' => 1,
            'ref_id' => 0,
        ]);
    }

    public function test_paper_trade_creates_internal_trade_record(): void
    {
        $user = $this->createUser(100000);
        $connection = $this->createPaperConnection($user->id);

        $executionData = [
            'bot_id' => 1,
            'user_id' => $user->id,
            'symbol' => 'BTC/USDT',
            'direction' => 'buy',
            'quantity' => 0.1,
            'entry_price' => 50000,
            'stop_loss' => 49000,
            'take_profit' => 52000,
            'is_paper_trading' => true,
            'connection_id' => $connection->id,
        ];

        $job = new ExecutionJob($executionData);
        $job->handle();

        // Verify internal_trade table (table is sp_internal_trades based on migration)
        $trade = \Illuminate\Support\Facades\DB::table('internal_trades')
            ->where('user_id', $user->id)
            ->where('is_paper', true)
            ->latest()
            ->first();

        $this->assertNotNull($trade, 'Paper trade should be created');
        $this->assertEquals('BTC/USDT', $trade->symbol);
        $this->assertEquals('buy', $trade->direction);
        $this->assertTrue((bool)$trade->is_paper);
    }

    public function test_paper_trade_does_not_affect_balance(): void
    {
        $user = $this->createUser(100000);
        $connection = $this->createPaperConnection($user->id);
        $originalBalance = $user->balance;

        $executionData = [
            'user_id' => $user->id,
            'symbol' => 'ETH/USDT',
            'direction' => 'sell',
            'quantity' => 1.0,
            'is_paper_trading' => true,
            'connection_id' => $connection->id,
        ];

        $job = new ExecutionJob($executionData);
        $job->handle();

        $user->refresh();
        $this->assertEquals($originalBalance, $user->balance);
    }
}
