<?php

declare(strict_types=1);

namespace Tests\Unit\Addons\DexAnalytics;

use Tests\TestCase;
use Addons\DexAnalyticsAddon\App\Services\DexAnalyticsNormalizationService;

class DexAnalyticsNormalizationServiceTest extends TestCase
{
    protected DexAnalyticsNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DexAnalyticsNormalizationService();
    }

    public function test_normalize_position_with_gmx_payload(): void
    {
        $payload = [
            'wallet' => '0x1234567890abcdef',
            'symbol' => 'BTC-USD',
            'side' => 'long',
            'size' => 1.5,
            'entry_price' => 45000.0,
            'mark_price' => 46000.0,
            'liquidation_price' => 42000.0,
            'unrealized_pnl' => 1500.0,
            'leverage' => 10,
            'margin' => 6750.0,
            'timestamp' => 1705843200,
        ];

        $normalized = $this->service->normalizePosition('gmx', $payload);

        $this->assertEquals('gmx', $normalized['platform']);
        $this->assertEquals('0x1234567890abcdef', $normalized['wallet_address']);
        $this->assertEquals('BTC-USD', $normalized['symbol']);
        $this->assertEquals('long', $normalized['side']);
        $this->assertEquals(1.5, $normalized['size']);
        $this->assertEquals(45000.0, $normalized['entry_price']);
        $this->assertEquals(46000.0, $normalized['mark_price']);
        $this->assertEquals(42000.0, $normalized['liquidation_price']);
        $this->assertEquals(1500.0, $normalized['unrealized_pnl']);
        $this->assertEquals(10, $normalized['leverage']);
        $this->assertEquals(6750.0, $normalized['margin']);
        $this->assertArrayHasKey('snapshot_at', $normalized);
        $this->assertArrayHasKey('raw_payload', $normalized);
    }

    public function test_normalize_position_with_alternate_field_names(): void
    {
        $payload = [
            'address' => '0xabcdef123456',
            'market' => 'ETH-PERP',
            'direction' => 'short',
            'position_size' => 10.0,
            'average_entry_price' => 2500.0,
            'price' => 2450.0,
            'liq_price' => 2800.0,
            'pnl_unrealized' => 500.0,
            'leverage' => 5,
            'collateral' => 5000.0,
            'updated_at' => '2024-01-21T12:00:00Z',
        ];

        $normalized = $this->service->normalizePosition('hyperliquid', $payload);

        $this->assertEquals('hyperliquid', $normalized['platform']);
        $this->assertEquals('0xabcdef123456', $normalized['wallet_address']);
        $this->assertEquals('ETH-PERP', $normalized['symbol']);
        $this->assertEquals('short', $normalized['side']);
        $this->assertEquals(10.0, $normalized['size']);
        $this->assertEquals(2500.0, $normalized['entry_price']);
        $this->assertEquals(2450.0, $normalized['mark_price']);
        $this->assertEquals(2800.0, $normalized['liquidation_price']);
        $this->assertEquals(500.0, $normalized['unrealized_pnl']);
    }

    public function test_normalize_positions_batch(): void
    {
        $positions = [
            ['wallet' => '0x111', 'symbol' => 'BTC-USD', 'size' => 1.0],
            ['wallet' => '0x222', 'symbol' => 'ETH-USD', 'size' => 10.0],
            ['wallet' => '0x333', 'symbol' => 'SOL-USD', 'size' => 100.0],
        ];

        $normalized = $this->service->normalizePositions($positions, 'gmx');

        $this->assertCount(3, $normalized);
        $this->assertEquals('0x111', $normalized[0]['wallet_address']);
        $this->assertEquals('0x222', $normalized[1]['wallet_address']);
        $this->assertEquals('0x333', $normalized[2]['wallet_address']);
    }

    public function test_normalize_pnl_record(): void
    {
        $payload = [
            'wallet' => '0xabc123',
            'symbol' => 'BTC-USD',
            'side' => 'long',
            'entry_price' => 40000.0,
            'exit_price' => 42000.0,
            'size' => 1.0,
            'realized_pnl' => 2000.0,
            'fees' => 50.0,
            'funding_cost' => -20.0,
            'closed_at' => 1705843200,
        ];

        $normalized = $this->service->normalizePnl('gmx', $payload);

        $this->assertEquals('gmx', $normalized['platform']);
        $this->assertEquals('0xabc123', $normalized['wallet_address']);
        $this->assertEquals('BTC-USD', $normalized['symbol']);
        $this->assertEquals('long', $normalized['side']);
        $this->assertEquals(2000.0, $normalized['realized_pnl']);
        $this->assertEquals(50.0, $normalized['fees']);
        $this->assertEquals(-20.0, $normalized['funding_cost']);
    }

    public function test_normalize_funding_record(): void
    {
        $payload = [
            'wallet' => '0xdef456',
            'symbol' => 'ETH-USD',
            'funding_rate' => 0.0001,
            'funding_payment' => -5.0,
            'position_size' => 10.0,
            'paid_at' => 1705843200,
        ];

        $normalized = $this->service->normalizeFunding('hyperliquid', $payload);

        $this->assertEquals('hyperliquid', $normalized['platform']);
        $this->assertEquals('0xdef456', $normalized['wallet_address']);
        $this->assertEquals('ETH-USD', $normalized['symbol']);
        $this->assertEquals(0.0001, $normalized['funding_rate']);
        $this->assertEquals(-5.0, $normalized['funding_payment']);
        $this->assertEquals(10.0, $normalized['position_size']);
    }

    public function test_normalize_liquidation_event(): void
    {
        $payload = [
            'wallet' => '0x789xyz',
            'symbol' => 'SOL-USD',
            'side' => 'long',
            'liquidation_price' => 95.0,
            'position_size' => 100.0,
            'loss_amount' => 500.0,
            'liquidated_at' => 1705843200,
        ];

        $normalized = $this->service->normalizeLiquidation('aster', $payload);

        $this->assertEquals('aster', $normalized['platform']);
        $this->assertEquals('0x789xyz', $normalized['wallet_address']);
        $this->assertEquals('SOL-USD', $normalized['symbol']);
        $this->assertEquals('long', $normalized['side']);
        $this->assertEquals(95.0, $normalized['liquidation_price']);
        $this->assertEquals(100.0, $normalized['position_size']);
        $this->assertEquals(500.0, $normalized['loss_amount']);
    }

    public function test_handles_missing_optional_fields(): void
    {
        $payload = [
            'wallet' => '0x123',
            'symbol' => 'BTC-USD',
        ];

        $normalized = $this->service->normalizePosition('gmx', $payload);

        $this->assertNull($normalized['side']);
        $this->assertNull($normalized['size']);
        $this->assertNull($normalized['entry_price']);
        $this->assertNull($normalized['mark_price']);
    }

    public function test_timestamp_conversion_from_unix(): void
    {
        $payload = [
            'wallet' => '0x123',
            'symbol' => 'BTC-USD',
            'timestamp' => 1705843200,
        ];

        $normalized = $this->service->normalizePosition('gmx', $payload);

        $this->assertIsString($normalized['snapshot_at']);
        $this->assertStringContainsString('2024', $normalized['snapshot_at']);
    }

    public function test_timestamp_conversion_from_string(): void
    {
        $payload = [
            'wallet' => '0x123',
            'symbol' => 'BTC-USD',
            'snapshot_at' => '2024-01-21T12:00:00Z',
        ];

        $normalized = $this->service->normalizePosition('gmx', $payload);

        $this->assertEquals('2024-01-21T12:00:00Z', $normalized['snapshot_at']);
    }
}
