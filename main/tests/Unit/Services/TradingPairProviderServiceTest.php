<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TradingPairProviderService;
use App\Services\Trading\MarketDataService;
use Mockery;

class TradingPairProviderServiceTest extends TestCase
{
    protected $providerService;
    protected $marketDataService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->marketDataService = Mockery::mock(MarketDataService::class);
        
        $this->providerService = new TradingPairProviderService(
            $this->marketDataService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_trading_pairs_returns_formatted_data()
    {
        // Mock data
        $cryptoData = [
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'price' => 50000, 'change_24h' => 5, 'volume' => 1000000000]
        ];
        $forexData = [];
        $indicesData = [];
        $commoditiesData = [];
        $stocksData = [];

        $this->marketDataService->shouldReceive('getCryptoData')->andReturn($cryptoData);
        $this->marketDataService->shouldReceive('getForexData')->andReturn($forexData);
        $this->marketDataService->shouldReceive('getIndicesData')->andReturn($indicesData);
        $this->marketDataService->shouldReceive('getCommoditiesData')->andReturn($commoditiesData);
        $this->marketDataService->shouldReceive('getStocksData')->andReturn($stocksData);

        $result = $this->providerService->getTradingPairs('all');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('counts', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('BTCUSDT', $result['data'][0]['symbol']);
        $this->assertEquals('BTC/USDT', $result['data'][0]['displaySymbol']);
        $this->assertEquals('1.00B', $result['data'][0]['volumeDisplay']);
    }

    public function test_get_trading_pairs_filters_by_category()
    {
         // Mock data
        $cryptoData = [['symbol' => 'ETH', 'name' => 'Ethereum', 'price' => 3000, 'change_24h' => 2, 'volume' => 500000]];
        $forexData = [['symbol' => 'EURUSD', 'name' => 'Euro', 'price' => 1.1, 'change_24h' => 0.1, 'volume' => 1000]];
        
        $this->marketDataService->shouldReceive('getCryptoData')->andReturn($cryptoData);
        $this->marketDataService->shouldReceive('getForexData')->andReturn($forexData);
        $this->marketDataService->shouldReceive('getIndicesData')->andReturn([]);
        $this->marketDataService->shouldReceive('getCommoditiesData')->andReturn([]);
        $this->marketDataService->shouldReceive('getStocksData')->andReturn([]);

        $result = $this->providerService->getTradingPairs('crypto');
        
        $this->assertCount(1, $result['data']);
        $this->assertEquals('crypto', $result['data'][0]['category']);
    }
}
