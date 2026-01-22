<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot\DemoMode;

use Tests\TestCase;
use Addons\TradingManagement\Modules\PaperTrading\Services\PaperTradingService;
use Addons\TradingManagement\Modules\PaperTrading\Models\VirtualPortfolio;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected PaperTradingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaperTradingService::class);
    }

    public function test_demo_trade_creates_virtual_portfolio(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        $result = $this->service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'buy',
            0.1,
            50000
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('virtual_portfolios', [
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
        ]);

        $portfolio = VirtualPortfolio::where('user_id', $user->id)
            ->where('exchange_connection_id', $connection->id)
            ->first();

        $this->assertNotNull($portfolio);
        $this->assertEquals(10000.0, $portfolio->initial_balance);
    }

    public function test_demo_trade_deducts_virtual_balance(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        // Create portfolio with 10000 balance
        $portfolio = VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 10000,
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'market_type' => 'crypto',
            'currency' => 'USDT',
            'is_active' => true,
        ]);

        $result = $this->service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'buy',
            0.1,
            50000 // Cost: 5000
        );

        $this->assertTrue($result['success']);

        $portfolio->refresh();
        $this->assertEquals(5000, $portfolio->current_balance);
        $this->assertEquals(-5000, $portfolio->pnl);
    }

    public function test_demo_trade_does_not_affect_real_balance(): void
    {
        $user = User::factory()->create(['balance' => 100000]);
        $connection = $this->createExecutionConnection($user, 'crypto');
        $originalBalance = $user->balance;

        $this->service->executeTrade(
            $user,
            $connection,
            'ETH/USDT',
            'sell',
            1.0,
            3000
        );

        $user->refresh();
        $this->assertEquals($originalBalance, $user->balance);
    }

    public function test_paper_trading_service_get_balance(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        // Create portfolio
        VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 15000,
            'initial_balance' => 15000,
            'current_balance' => 15000,
            'market_type' => 'crypto',
            'currency' => 'USDT',
            'is_active' => true,
        ]);

        $balance = $this->service->getBalance($user, $connection);

        $this->assertEquals(15000, $balance);
    }

    public function test_paper_trading_service_reset_portfolio(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        // Create portfolio with some balance
        VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 5000,
            'initial_balance' => 5000,
            'current_balance' => 5000,
            'market_type' => 'crypto',
            'currency' => 'USDT',
            'is_active' => true,
        ]);

        // Reset portfolio
        $this->service->resetPortfolio($user, $connection, 10000);

        $portfolio = VirtualPortfolio::where('user_id', $user->id)
            ->where('exchange_connection_id', $connection->id)
            ->first();

        $this->assertEquals(10000, $portfolio->current_balance);
        $this->assertEquals(10000, $portfolio->initial_balance);
        $this->assertEquals(0, $portfolio->pnl);
    }

    public function test_paper_trading_service_get_portfolio_summary(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        $summary = $this->service->getPortfolioSummary($user, $connection);

        $this->assertArrayHasKey('portfolio_id', $summary);
        $this->assertArrayHasKey('balance', $summary);
        $this->assertArrayHasKey('initial_balance', $summary);
        $this->assertArrayHasKey('pnl', $summary);
        $this->assertArrayHasKey('open_trades', $summary);
        $this->assertArrayHasKey('closed_trades', $summary);
        $this->assertArrayHasKey('market_type', $summary);
    }

    public function test_insufficient_virtual_balance_rejects_trade(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        // Create portfolio with very low balance
        VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 100,
            'initial_balance' => 100,
            'current_balance' => 100,
            'market_type' => 'crypto',
            'currency' => 'USDT',
            'is_active' => true,
        ]);

        $result = $this->service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'buy',
            1.0, // Would cost 50000
            50000
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient', $result['message']);
    }

    public function test_sell_trade_does_not_deduct_balance(): void
    {
        $user = User::factory()->create();
        $connection = $this->createExecutionConnection($user, 'crypto');

        // Create portfolio with balance
        VirtualPortfolio::create([
            'user_id' => $user->id,
            'exchange_connection_id' => $connection->id,
            'balance' => 10000,
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'market_type' => 'crypto',
            'currency' => 'USDT',
            'is_active' => true,
        ]);

        $result = $this->service->executeTrade(
            $user,
            $connection,
            'BTC/USDT',
            'sell',
            0.1,
            50000
        );

        $this->assertTrue($result['success']);

        // For sell trades, balance should not be deducted (we're selling what we have)
        $portfolio = VirtualPortfolio::where('user_id', $user->id)
            ->where('exchange_connection_id', $connection->id)
            ->first();

        $this->assertEquals(10000, $portfolio->current_balance);
    }

    /**
     * Helper method to create an ExecutionConnection without using factory.
     */
    private function createExecutionConnection(User $user, string $type): ExecutionConnection
    {
        return ExecutionConnection::create([
            'user_id' => $user->id,
            'admin_id' => null,
            'name' => 'Test ' . ucfirst($type) . ' Connection',
            'type' => $type,
            'exchange_name' => $type === 'crypto' ? 'binance' : 'metatrader',
            'credentials' => encrypt(json_encode(['api_key' => 'test', 'api_secret' => 'test'])),
            'status' => 'active',
            'is_active' => true,
            'is_admin_owned' => false,
            'last_error' => null,
            'last_tested_at' => now(),
            'last_used_at' => now(),
            'settings' => ['test_mode' => true],
            'preset_id' => null,
            'data_connection_id' => null,
            'leverage' => 1,
            'margin_call_threshold' => 0.5,
            'liquidation_threshold' => 0.2,
            'max_margin_usage_pct' => 0.8,
            'max_open_positions' => 5,
            'max_positions_per_symbol' => 2,
            'circuit_breaker_enabled' => false,
            'max_consecutive_failures' => 5,
            'consecutive_failures' => 0,
            'last_failure_at' => null,
        ]);
    }
}
