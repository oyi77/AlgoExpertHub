<?php

namespace Tests\Unit\Addons\TradingManagement\Backtesting;

use Tests\TestCase;
use App\Models\User;
use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult;
use Addons\TradingManagement\Modules\Backtesting\Jobs\RunBacktestJob;
use Addons\TradingManagement\Modules\Backtesting\Services\BacktestEngine;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class RunBacktestJobTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $preset;
    protected $backtest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->preset = TradingPreset::factory()->create([
            'created_by_user_id' => $this->user->id,
        ]);
        
        $this->backtest = Backtest::factory()->create([
            'user_id' => $this->user->id,
            'preset_id' => $this->preset->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function job_can_be_dispatched()
    {
        Queue::fake();

        RunBacktestJob::dispatch($this->backtest);

        Queue::assertPushed(RunBacktestJob::class, function ($job) {
            return $job->backtest->id === $this->backtest->id;
        });
    }

    /** @test */
    public function job_marks_backtest_as_running_when_started()
    {
        // Mock the BacktestEngine to prevent actual execution
        $this->mock(BacktestEngine::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(BacktestResult::factory()->make());
        });

        $job = new RunBacktestJob($this->backtest);
        $job->handle(app(BacktestEngine::class));

        $this->backtest->refresh();
        $this->assertEquals('completed', $this->backtest->status);
    }

    /** @test */
    public function job_marks_backtest_as_failed_on_exception()
    {
        // Mock the BacktestEngine to throw exception
        $this->mock(BacktestEngine::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andThrow(new \Exception('Test error'));
        });

        $job = new RunBacktestJob($this->backtest);
        
        try {
            $job->handle(app(BacktestEngine::class));
        } catch (\Exception $e) {
            // Expected
        }

        $this->backtest->refresh();
        $this->assertEquals('failed', $this->backtest->status);
        $this->assertNotNull($this->backtest->error_message);
    }

    /** @test */
    public function job_logs_execution_start()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Starting backtest execution', \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Backtest completed successfully', \Mockery::type('array'));

        $this->mock(BacktestEngine::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(BacktestResult::factory()->make([
                    'total_trades' => 50,
                    'net_profit' => 1000,
                    'win_rate' => 60,
                ]));
        });

        $job = new RunBacktestJob($this->backtest);
        $job->handle(app(BacktestEngine::class));
    }

    /** @test */
    public function job_logs_execution_failure()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Starting backtest execution', \Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Backtest execution failed', \Mockery::type('array'));

        $this->mock(BacktestEngine::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andThrow(new \Exception('Insufficient data'));
        });

        $job = new RunBacktestJob($this->backtest);
        
        try {
            $job->handle(app(BacktestEngine::class));
        } catch (\Exception $e) {
            // Expected
        }
    }

    /** @test */
    public function job_has_correct_timeout()
    {
        $job = new RunBacktestJob($this->backtest);
        $this->assertEquals(600, $job->timeout); // 10 minutes
    }

    /** @test */
    public function job_has_correct_retry_attempts()
    {
        $job = new RunBacktestJob($this->backtest);
        $this->assertEquals(1, $job->tries);
    }
}
