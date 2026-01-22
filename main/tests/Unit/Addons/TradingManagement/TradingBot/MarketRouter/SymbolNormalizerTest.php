<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot\MarketRouter;

use Tests\TestCase;
use Addons\TradingManagement\Modules\MarketRouter\Services\SymbolNormalizer;
use Addons\TradingManagement\Modules\MarketRouter\Exceptions\InvalidSymbolException;

class SymbolNormalizerTest extends TestCase
{
    public function test_normalize_crypto_symbol(): void
    {
        $normalizer = new SymbolNormalizer();
        
        $this->assertEquals('BTCUSDT', $normalizer->normalize('BTC/USDT', 'crypto'));
        $this->assertEquals('BTCUSDT', $normalizer->normalize('BTC-USDT', 'crypto'));
        $this->assertEquals('BTCUSDT', $normalizer->normalize('BTC_USDT', 'crypto'));
        $this->assertEquals('BTCUSDT', $normalizer->normalize('btcusdt', 'crypto'));
    }
    
    public function test_normalize_forex_symbol(): void
    {
        $normalizer = new SymbolNormalizer();
        
        $this->assertEquals('EURUSD', $normalizer->normalize('EUR/USD', 'forex'));
        $this->assertEquals('EURUSD', $normalizer->normalize('EUR-USD', 'forex'));
        $this->assertEquals('EURUSD', $normalizer->normalize('eurusd', 'forex'));
    }
    
    public function test_forex_symbol_length_validation(): void
    {
        $normalizer = new SymbolNormalizer();
        
        $this->expectException(InvalidSymbolException::class);
        $this->expectExceptionMessage("Forex symbols must be 6 characters (e.g., EURUSD)");
        
        $normalizer->normalize('EURUSDT', 'forex');
    }
    
    public function test_empty_symbol_validation(): void
    {
        $normalizer = new SymbolNormalizer();
        
        $this->expectException(InvalidSymbolException::class);
        $this->expectExceptionMessage("Symbol cannot be empty");
        
        $normalizer->normalize('', 'crypto');
    }
    
    public function test_unknown_market_type_throws_exception(): void
    {
        $normalizer = new SymbolNormalizer();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown market type: stocks");
        
        $normalizer->normalize('AAPL', 'stocks');
    }
}
