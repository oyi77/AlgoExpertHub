<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TradingTerminalService;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use App\Repositories\Contracts\ExchangeConnectionRepositoryInterface;
use App\Models\User;
use App\Models\InternalTrade;
use Mockery;

class TradingTerminalServiceTest extends TestCase
{

    protected $terminalService;
    protected $brokerService;
    protected $marketDataService;
    protected $exchangeRepo;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->brokerService = Mockery::mock(InternalBrokerService::class);
        $this->marketDataService = Mockery::mock(MarketDataService::class);
        $this->exchangeRepo = Mockery::mock(ExchangeConnectionRepositoryInterface::class);

        // Instantiate service
        $this->terminalService = new TradingTerminalService(
            $this->brokerService,
            $this->marketDataService,
            $this->exchangeRepo
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_place_order_demo_mode_success()
    {
        $user = new User();
        $user->id = 1;
        $user->balance = 1000;
        $user->demo_balance = 10000;
        
        $data = [
            'mode' => 'demo',
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'quantity' => 1.5,
            'sl_price' => 49000,
            'tp_price' => 51000,
        ];

        // Mock Market Data 
        $this->marketDataService->shouldReceive('getCurrentPrice')
            ->once()
            ->with('BTCUSDT')
            ->andReturn(50000);

        // Mock Internal Broker
        $trade = new InternalTrade([
            'id' => 1,
            'user_id' => $user->id,
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'quantity' => 1.5,
            'entry_price' => 50000,
            'current_price' => 50000,
            'status' => 'open',
            'pnl' => 0
        ]);
        // Allow dynamic property access on mock if needed, or just return model
        // InternalBrokerService returns a model.

        $this->brokerService->shouldReceive('placeOrder')
            ->once()
            ->with(
                Mockery::on(function($arg) use ($user) { return $arg->id === $user->id; }),
                'BTCUSDT',
                'buy',
                1.5,
                50000,
                49000,
                51000
            )
            ->andReturn($trade);

        $result = $this->terminalService->placeOrder($user, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('demo', $result['data']['mode']);
        $this->assertEquals(1.5, $result['data']['quantity']);
    }

    public function test_place_order_fails_without_price()
    {
        $user = new User();
        $user->id = 1;
        
        $data = [
            'mode' => 'demo',
            'symbol' => 'BTCUSDT',
            'direction' => 'buy',
            'quantity' => 1,
        ];

        $this->marketDataService->shouldReceive('getCurrentPrice')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to fetch current market price');

        $this->terminalService->placeOrder($user, $data);
    }
}
