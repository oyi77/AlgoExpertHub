<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

abstract class TradingBotTestCase extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Create a mock ExchangeConnection for tests.
     */
    protected function createMockExchangeConnection(array $overrides = []): MockInterface
    {
        return $this->mock(ExchangeConnection::class, function ($mock) use ($overrides) {
            $mock->shouldReceive('getAttribute')
                ->with('exchange_type')
                ->andReturn($overrides['exchange_type'] ?? 'crypto');
            
            $mock->shouldReceive('getAttribute')
                ->with('is_paper_trading')
                ->andReturn($overrides['is_paper_trading'] ?? false);
            
            $mock->shouldReceive('getAttribute')
                ->with('execution_settings')
                ->andReturn($overrides['execution_settings'] ?? []);
        });
    }
    
    /**
     * Create a TradingBot with default test values.
     */
    protected function createMockTradingBot(array $overrides = []): TradingBot
    {
        return TradingBot::factory()->create(array_merge([
            'status' => 'created',
            'is_paper_trading' => true,
        ], $overrides));
    }
    
    /**
     * Alias for clarity in tests.
     */
    protected function createTestBot(array $overrides = []): TradingBot
    {
        return $this->createMockTradingBot($overrides);
    }
}
