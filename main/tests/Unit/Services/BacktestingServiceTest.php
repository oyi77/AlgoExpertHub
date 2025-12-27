<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Backtest;
use App\Models\BacktestTrade;
use App\Models\Signal;
use App\Models\User;
use App\Services\BacktestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for BacktestingService
 */
class BacktestingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacktestingService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BacktestingService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_creates_backtest_with_valid_data(): void
    {
        $signal = Signal::factory()->create();
        
        $data = [
            'user_id' => $this->user->id,
            'signal_id' => $signal->id,
            'initial_balance' => 10000,
            'risk_percentage' => 2,
            'start_date' => now()->subDays(30),
            'end_date' => now(),
        ];

        $backtest = $this->service->createBacktest($data);

        $this->assertInstanceOf(Backtest::class, $backtest);
        $this->assertEquals(10000, $backtest->initial_balance);
        $this->assertDatabaseHas('backtests', ['id' => $backtest->id]);
    }

    /** @test */
    public function it_runs_backtest_simulation(): void
    {
        $backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => 10000,
            'status' => 'pending',
        ]);

        $result = $this->service->runBacktest($backtest->id);

        $this->assertEquals('completed', $result->status);
        $this->assertNotNull($result->final_balance);
    }

    /** @test */
    public function it_calculates_backtest_metrics(): void
    {
        $backtest = Backtest::factory()->create([
            'initial_balance' => 10000,
            'final_balance' => 12000,
        ]);

        BacktestTrade::factory()->count(10)->create([
            'backtest_id' => $backtest->id,
            'profit_loss' => 200,
        ]);

        $metrics = $this->service->calculateMetrics($backtest->id);

        $this->assertArrayHasKey('total_return', $metrics);
        $this->assertArrayHasKey('win_rate', $metrics);
        $this->assertArrayHasKey('profit_factor', $metrics);
    }

    /** @test */
    public function it_retrieves_backtest_trades(): void
    {
        $backtest = Backtest::factory()->create();
        BacktestTrade::factory()->count(5)->create(['backtest_id' => $backtest->id]);

        $trades = $this->service->getBacktestTrades($backtest->id);

        $this->assertCount(5, $trades);
    }

    /** @test */
    public function it_deletes_backtest_and_related_trades(): void
    {
        $backtest = Backtest::factory()->create();
        BacktestTrade::factory()->count(3)->create(['backtest_id' => $backtest->id]);

        $this->service->deleteBacktest($backtest->id);

        $this->assertDatabaseMissing('backtests', ['id' => $backtest->id]);
        $this->assertDatabaseMissing('backtest_trades', ['backtest_id' => $backtest->id]);
    }
}
