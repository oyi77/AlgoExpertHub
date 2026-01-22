<?php

declare(strict_types=1);

namespace Tests\Feature\Addons\TradingManagement\TradingBot\MarketInfo;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketInfoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_get_market_hours(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'forex']));

        $response->assertOk();
        $response->assertJsonStructure([
            'is_open',
            'next_open',
        ]);
    }

    public function test_get_symbol_info(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.symbol', [
                'symbol' => 'BTC/USDT',
                'market' => 'crypto',
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'symbol',
            'normalized',
            'market_type',
        ]);
    }

    public function test_market_hours_includes_is_open_flag(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'crypto']));

        $response->assertOk();
        $response->assertJsonFragment(['is_open' => true]);
    }

    public function test_forex_market_hours_respected(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'forex']));

        $response->assertOk();
        $this->assertArrayHasKey('is_open', $response->json());
    }

    public function test_symbol_normalization_for_crypto(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.symbol', [
                'symbol' => 'BTC/USDT',
                'market' => 'crypto',
            ]));

        $response->assertOk();
        $this->assertEquals('BTCUSDT', $response->json('normalized'));
    }

    public function test_symbol_normalization_for_forex(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.symbol', [
                'symbol' => 'EUR/USD',
                'market' => 'forex',
            ]));

        $response->assertOk();
        $this->assertEquals('EURUSD', $response->json('normalized'));
    }

    public function test_guest_cannot_access_market_info_api(): void
    {
        $response = $this->get(route('api.trading-management.market-info.hours', ['market' => 'crypto']));

        $response->assertUnauthorized();
    }

    public function test_market_info_returns_market_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.symbol', [
                'symbol' => 'ETH/USDT',
                'market' => 'crypto',
            ]));

        $response->assertOk();
        $this->assertEquals('crypto', $response->json('market_type'));
    }

    public function test_next_open_time_format(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'forex']));

        $response->assertOk();
        $this->assertArrayHasKey('next_open', $response->json());
        // next_open should be a valid datetime string
        $this->assertNotFalse(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $response->json('next_open')));
    }

    public function test_unauthorized_market_request_returns_401(): void
    {
        $response = $this->get(route('api.trading-management.market-info.symbol', [
            'symbol' => 'BTC/USDT',
            'market' => 'crypto',
        ]));

        $response->assertUnauthorized();
    }

    public function test_inactive_user_cannot_access_market_api(): void
    {
        $this->user->update(['status' => 0]);

        $response = $this->actingAs($this->user)
            ->get(route('api.trading-management.market-info.hours', ['market' => 'crypto']));

        $response->assertRedirect('/user/dashboard');
    }
}
