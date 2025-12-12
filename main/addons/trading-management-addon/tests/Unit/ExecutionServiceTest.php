<?php

namespace Addons\TradingManagement\Tests\Unit;

use Tests\TestCase;
use Addons\TradingManagement\Modules\Execution\Services\ExecutionService;
use Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService;
use Addons\TradingManagement\Shared\Contracts\ExchangeAdapterInterface;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use App\Models\Signal;
use App\Models\CurrencyPair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $exchangeConnectionService;
    protected $adapter;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->exchangeConnectionService = Mockery::mock(ExchangeConnectionService::class);
        $this->adapter = Mockery::mock(ExchangeAdapterInterface::class);
        
        $this->service = new ExecutionService(
            $this->exchangeConnectionService
        );
    }

    public function test_execute_order_creates_log_and_calls_adapter()
    {
        // Setup Models
        $user = User::factory()->create();
        $pair = CurrencyPair::factory()->create(['name' => 'EUR/USD']);
        
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $user->id,
            'name' => 'Demo Binace',
            'provider' => 'binance'
        ]);
        
        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->direction = 'buy';
        $signal->sl = 1.0950;
        $signal->tp = 1.1050;

        $riskCalculation = [
            'lot_size' => 0.1,
            'risk_amount' => 10,
            'risk_percent' => 1.0,
        ];

        // Mock Adapter Response
        $mockResponse = [
            'success' => true,
            'id' => '123456789', // Order ID from exchange
            'price' => 1.1001,
            'amount' => 0.1,
            'status' => 'filled',
            'symbol' => 'EUR/USD',
        ];

        // Expectations
        $this->exchangeConnectionService
            ->shouldReceive('getAdapter')
            ->once()
            ->with($connection) // Expect exact connection model
            ->andReturn($this->adapter);

        $this->adapter
            ->shouldReceive('createMarketOrder')
            ->once()
            ->with(
                'EUR/USD',
                'buy',
                0.1,
                Mockery::on(function ($params) {
                    return isset($params['stopLoss']) && isset($params['takeProfit']);
                })
            )
            ->andReturn($mockResponse);

        // Execute
        $executionLog = $this->service->executeOrder($signal, $connection, $riskCalculation);

        // Assert
        $this->assertInstanceOf(ExecutionLog::class, $executionLog);
        $this->assertEquals('FILLED', $executionLog->status);
        $this->assertEquals('123456789', $executionLog->order_id);
        $this->assertEquals(0.1, $executionLog->lot_size);
        $this->assertEquals(1.1001, $executionLog->entry_price);
        
        // Verify database state
        $this->assertDatabaseHas('execution_logs', [
            'id' => $executionLog->id,
            'status' => 'FILLED',
            'order_id' => '123456789',
        ]);
    }

    public function test_execute_order_handles_failure()
    {
        // Setup Models
        $user = User::factory()->create();
        $pair = CurrencyPair::factory()->create(['name' => 'BTC/USD']);
        
        $connection = ExchangeConnection::factory()->create([
            'user_id' => $user->id,
            'name' => 'Demo Fx',
            'provider' => 'metaapi'
        ]);
        
        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->direction = 'sell';
        $signal->sl = 50000;
        $signal->tp = 40000;

        $riskCalculation = [
            'lot_size' => 0.01,
            'risk_amount' => 50,
            'risk_percent' => 2.0,
        ];

        // Mock failure exception from adapter
        $this->exchangeConnectionService
            ->shouldReceive('getAdapter')
            ->andReturn($this->adapter);

        $this->adapter
            ->shouldReceive('createMarketOrder')
            ->andThrow(new \Exception('Insufficient funds'));

        // Execute
        $executionLog = $this->service->executeOrder($signal, $connection, $riskCalculation);

        // Assert
        $this->assertEquals('FAILED', $executionLog->status);
        $this->assertStringContainsString('Insufficient funds', $executionLog->error_message);
        
        $this->assertDatabaseHas('execution_logs', [
            'id' => $executionLog->id,
            'status' => 'FAILED',
        ]);
    }
}
