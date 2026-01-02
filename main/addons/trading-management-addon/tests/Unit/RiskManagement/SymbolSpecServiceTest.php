<?php

namespace Tests\Unit\RiskManagement;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\SymbolSpecService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SymbolSpecServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SymbolSpecService::class);
    }

    /** @test */
    public function it_calculates_pip_size_for_standard_fx_pairs()
    {
        // Standard FX pairs (4 decimal places)
        $this->assertEquals(0.0001, $this->service->getPipSize('EURUSD'));
        $this->assertEquals(0.0001, $this->service->getPipSize('GBPUSD'));
        $this->assertEquals(0.0001, $this->service->getPipSize('AUDUSD'));
    }

    /** @test */
    public function it_calculates_pip_size_for_jpy_pairs()
    {
        // JPY pairs (2 decimal places)
        $this->assertEquals(0.01, $this->service->getPipSize('USDJPY'));
        $this->assertEquals(0.01, $this->service->getPipSize('EURJPY'));
        $this->assertEquals(0.01, $this->service->getPipSize('GBPJPY'));
    }

    /** @test */
    public function it_calculates_pip_size_for_crypto_pairs()
    {
        // Crypto pairs (varies by pair, typically 0.01 or 0.0001)
        $this->assertEquals(0.01, $this->service->getPipSize('BTCUSD'));
        $this->assertEquals(0.0001, $this->service->getPipSize('ETHUSD'));
    }

    /** @test */
    public function it_gets_contract_size_for_fx_pairs()
    {
        // Standard FX contract size
        $this->assertEquals(100000, $this->service->getContractSize('EURUSD'));
        $this->assertEquals(100000, $this->service->getContractSize('GBPUSD'));
    }

    /** @test */
    public function it_calculates_pip_value_for_fx_pair()
    {
        // For EUR/USD at 1.1000, 1.0 lot, USD account
        // Pip value = (0.0001 / 1.1000) * 100000 * 1.0 = 9.09 USD per pip
        $pipValue = $this->service->getPipValue('EURUSD', 1.0, 'USD', 1.1000);
        $this->assertGreaterThan(9.0, $pipValue);
        $this->assertLessThan(10.0, $pipValue);
    }

    /** @test */
    public function it_calculates_pip_value_for_jpy_pair()
    {
        // For USD/JPY at 110.00, 1.0 lot, USD account
        // Pip value = (0.01 / 110.00) * 100000 * 1.0 = 9.09 USD per pip
        $pipValue = $this->service->getPipValue('USDJPY', 1.0, 'USD', 110.00);
        $this->assertGreaterThan(9.0, $pipValue);
        $this->assertLessThan(10.0, $pipValue);
    }

    /** @test */
    public function it_identifies_forex_pairs()
    {
        $service = new \ReflectionClass($this->service);
        $method = $service->getMethod('isForexPair');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, 'EURUSD'));
        $this->assertTrue($method->invoke($this->service, 'GBPUSD'));
        $this->assertFalse($method->invoke($this->service, 'BTCUSD'));
    }

    /** @test */
    public function it_gets_complete_symbol_spec()
    {
        $spec = $this->service->getSymbolSpec('EURUSD', null, 'USD');

        $this->assertIsArray($spec);
        $this->assertArrayHasKey('pip_size', $spec);
        $this->assertArrayHasKey('contract_size', $spec);
        $this->assertArrayHasKey('pip_value', $spec);
        $this->assertArrayHasKey('market_type', $spec);
        $this->assertEquals(0.0001, $spec['pip_size']);
        $this->assertEquals(100000, $spec['contract_size']);
    }
}

