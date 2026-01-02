<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PositionManagementService;
use App\Services\InternalBrokerService;
use App\Services\MarketDataService;
use App\Models\User;
use App\Models\InternalTrade;
use Mockery;

class PositionManagementServiceTest extends TestCase
{
    protected $positionService;
    protected $brokerService;
    protected $marketDataService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->brokerService = Mockery::mock(InternalBrokerService::class);
        $this->marketDataService = Mockery::mock(MarketDataService::class);
        
        $this->positionService = new PositionManagementService(
            $this->brokerService,
            $this->marketDataService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_user_open_positions()
    {
        $user = new User();
        $user->id = 1;
        
        $positions = collect([
            new InternalTrade(['id' => 1, 'symbol' => 'BTCUSDT'])
        ]);

        $this->brokerService->shouldReceive('getUserOpenPositions')
            ->once()
            ->with($user)
            ->andReturn($positions);

        $result = $this->positionService->getUserOpenPositions($user);
        
        $this->assertCount(1, $result);
    }
}
