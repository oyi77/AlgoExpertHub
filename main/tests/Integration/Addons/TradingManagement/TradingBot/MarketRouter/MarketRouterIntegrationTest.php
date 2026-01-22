<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot\MarketRouter;

use Tests\TestCase;
use Addons\TradingManagement\Modules\MarketRouter\MarketRouter;
use Addons\TradingManagement\Modules\MarketRouter\Services\SymbolNormalizer;
use Addons\TradingManagement\Modules\MarketRouter\Services\TradingHoursService;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketRouterIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    protected MarketRouter $router;
    protected SymbolNormalizer $symbolNormalizer;
    protected TradingHoursService $tradingHours;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->symbolNormalizer = new SymbolNormalizer();
        $this->tradingHours = new TradingHoursService();
        $this->router = new MarketRouter($this->symbolNormalizer, $this->tradingHours);
    }
    
    public function test_crypto_symbol_normalization(): void
    {
        // Test various crypto symbol formats
        $this->assertEquals('BTCUSDT', $this->router->normalizeSymbol('BTC/USDT', 'crypto'));
        $this->assertEquals('BTCUSDT', $this->router->normalizeSymbol('BTC-USDT', 'crypto'));
        $this->assertEquals('BTCUSDT', $this->router->normalizeSymbol('btc_usdt', 'crypto'));
        $this->assertEquals('ETHBTC', $this->router->normalizeSymbol('ETH/BTC', 'crypto'));
    }
    
    public function test_forex_symbol_normalization(): void
    {
        // Test various forex symbol formats
        $this->assertEquals('EURUSD', $this->router->normalizeSymbol('EUR/USD', 'forex'));
        $this->assertEquals('EURUSD', $this->router->normalizeSymbol('EUR-USD', 'forex'));
        $this->assertEquals('EURUSD', $this->router->normalizeSymbol('eur_usd', 'forex'));
        $this->assertEquals('GBPJPY', $this->router->normalizeSymbol('GBP/JPY', 'forex'));
    }
    
    public function test_crypto_market_always_open(): void
    {
        // Crypto markets are 24/7
        $this->assertTrue($this->router->isMarketOpen('crypto'));
        $this->assertTrue($this->router->isMarketOpen('crypto', 'BTC/USDT'));
        $this->assertTrue($this->router->isMarketOpen('crypto', 'ETH/USDT'));
    }
    
    public function test_forex_market_hours(): void
    {
        // Forex markets have trading hours
        $result = $this->router->isMarketOpen('forex', 'EUR/USD');
        $this->assertIsBool($result);
    }
    
    public function test_crypto_lot_size(): void
    {
        $connection = ExchangeConnection::factory()->create(['type' => 'crypto']);
        
        // Crypto lot size is 1:1
        $lotSize = $this->router->getLotSize(0.1, 'BTC/USDT', $connection);
        $this->assertEquals(0.1, $lotSize);
        
        $lotSize = $this->router->getLotSize(1.0, 'ETH/USDT', $connection);
        $this->assertEquals(1.0, $lotSize);
    }
    
    public function test_forex_lot_size(): void
    {
        $connection = ExchangeConnection::factory()->create(['type' => 'fx']);
        
        // Forex lot size: 1 lot = 100,000 units
        $lotSize = $this->router->getLotSize(100000, 'EURUSD', $connection);
        $this->assertEquals(1.0, $lotSize);
        
        $lotSize = $this->router->getLotSize(50000, 'EURUSD', $connection);
        $this->assertEquals(0.5, $lotSize);
    }
    
    public function test_adapter_routing(): void
    {
        // Test crypto adapter routing
        $cryptoConnection = ExchangeConnection::factory()->create(['type' => 'crypto']);
        $cryptoAdapter = $this->router->getAdapter($cryptoConnection);
        $this->assertInstanceOf(
            \Addons\TradingManagement\Modules\DataProvider\Adapters\CcxtAdapter::class,
            $cryptoAdapter
        );
        
        // Test forex adapter routing
        $fxConnection = ExchangeConnection::factory()->create(['type' => 'fx']);
        $fxAdapter = $this->router->getAdapter($fxConnection);
        $this->assertInstanceOf(
            \Addons\TradingManagement\Modules\DataProvider\Adapters\MetaApiAdapter::class,
            $fxAdapter
        );
    }
    
    public function test_end_to_end_crypto_workflow(): void
    {
        // End-to-end test for crypto trading
        $connection = ExchangeConnection::factory()->create(['type' => 'crypto']);
        
        // 1. Normalize symbol
        $normalizedSymbol = $this->router->normalizeSymbol('BTC/USDT', 'crypto');
        $this->assertEquals('BTCUSDT', $normalizedSymbol);
        
        // 2. Check market is open
        $this->assertTrue($this->router->isMarketOpen('crypto', $normalizedSymbol));
        
        // 3. Calculate lot size
        $lotSize = $this->router->getLotSize(0.5, $normalizedSymbol, $connection);
        $this->assertEquals(0.5, $lotSize);
        
        // 4. Get adapter
        $adapter = $this->router->getAdapter($connection);
        $this->assertNotNull($adapter);
    }
    
    public function test_end_to_end_forex_workflow(): void
    {
        // End-to-end test for forex trading
        $connection = ExchangeConnection::factory()->create(['type' => 'fx']);
        
        // 1. Normalize symbol
        $normalizedSymbol = $this->router->normalizeSymbol('EUR/USD', 'forex');
        $this->assertEquals('EURUSD', $normalizedSymbol);
        
        // 2. Check market status
        $isOpen = $this->router->isMarketOpen('forex', $normalizedSymbol);
        $this->assertIsBool($isOpen);
        
        // 3. Calculate lot size
        $lotSize = $this->router->getLotSize(100000, $normalizedSymbol, $connection);
        $this->assertEquals(1.0, $lotSize);
        
        // 4. Get adapter
        $adapter = $this->router->getAdapter($connection);
        $this->assertNotNull($adapter);
    }
}
