<?php

namespace Tests\Unit\RiskManagement;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\PositionLimitService;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PositionLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PositionLimitService::class);
        $this->user = User::factory()->create();
        $this->connection = ExecutionConnection::factory()->create([
            'user_id' => $this->user->id,
            'max_open_positions' => 5,
            'max_positions_per_symbol' => 2,
        ]);
    }

    /** @test */
    public function it_counts_open_positions()
    {
        // Create 3 open positions
        ExecutionPosition::factory()->count(3)->create([
            'connection_id' => $this->connection->id,
            'status' => 'open',
        ]);

        $count = $this->service->getOpenPositionsCount($this->connection);

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_counts_positions_per_symbol()
    {
        // Create 2 open positions for EUR/USD
        ExecutionPosition::factory()->count(2)->create([
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'status' => 'open',
        ]);

        $count = $this->service->getOpenPositionsCount($this->connection, 'EURUSD');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_prevents_trade_when_max_positions_reached()
    {
        // Create 5 open positions (max is 5)
        ExecutionPosition::factory()->count(5)->create([
            'connection_id' => $this->connection->id,
            'status' => 'open',
        ]);

        $result = $this->service->shouldPreventTrade($this->connection, 'EURUSD');

        $this->assertTrue($result['should_prevent']);
        $this->assertStringContainsString('maximum', strtolower($result['reason']));
    }

    /** @test */
    public function it_prevents_trade_when_max_positions_per_symbol_reached()
    {
        // Create 2 open positions for EUR/USD (max per symbol is 2)
        ExecutionPosition::factory()->count(2)->create([
            'connection_id' => $this->connection->id,
            'symbol' => 'EURUSD',
            'status' => 'open',
        ]);

        $result = $this->service->shouldPreventTrade($this->connection, 'EURUSD');

        $this->assertTrue($result['should_prevent']);
        $this->assertStringContainsString('symbol', strtolower($result['reason']));
    }

    /** @test */
    public function it_allows_trade_within_limits()
    {
        // Create only 2 open positions (max is 5)
        ExecutionPosition::factory()->count(2)->create([
            'connection_id' => $this->connection->id,
            'status' => 'open',
        ]);

        $result = $this->service->shouldPreventTrade($this->connection, 'GBPUSD');

        $this->assertFalse($result['should_prevent']);
    }
}

