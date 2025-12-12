<?php

namespace Addons\TradingManagement\Tests\Unit;

use Tests\TestCase;
use Addons\TradingManagement\Modules\FilterStrategy\Services\FilterStrategyEvaluator;
use Addons\TradingManagement\Modules\FilterStrategy\Services\IndicatorService;
use Addons\TradingManagement\Modules\MarketData\Services\MarketDataService;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use App\Models\Signal;
use App\Models\CurrencyPair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Collection;

class FilterStrategyEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected $marketDataService;
    protected $indicatorService;
    protected $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marketDataService = Mockery::mock(MarketDataService::class);
        $this->indicatorService = new IndicatorService();
        $this->evaluator = new FilterStrategyEvaluator(
            $this->marketDataService,
            $this->indicatorService
        );
    }

    public function test_evaluate_returns_true_when_ema_condition_met()
    {
        // Setup Strategy
        $strategy = new FilterStrategy();
        $strategy->id = 1;
        $strategy->enabled = true;
        $strategy->config = [
            'indicators' => [
                'ema10' => ['period' => 10],
                'ema20' => ['period' => 20]
            ],
            'rules' => [
                'logic' => 'AND',
                'conditions' => [
                    [
                        'left' => 'ema10',
                        'operator' => '>',
                        'right' => 'ema20'
                    ]
                ]
            ]
        ];

        // Setup Signal
        $pair = CurrencyPair::factory()->create(['name' => 'EUR/USD']);
        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->timeframe = 'H1'; // Simplified, usually a relation

        // Mock Market Data (Simulate Uptrend)
        $candles = [];
        $basePrice = 1.1000;
        for ($i = 0; $i < 50; $i++) {
            $basePrice += 0.0005; // Increasing price
            $candles[] = [
                'timestamp' => now()->subHours(50 - $i)->timestamp,
                'open' => $basePrice,
                'high' => $basePrice + 0.0002,
                'low' => $basePrice - 0.0002,
                'close' => $basePrice,
                'volume' => 1000
            ];
        }

        // Mock MarketDataService response
        // We need to return a Collection of objects that have getCandleArray()
        $marketDataObjects = collect($candles)->map(function ($candle) {
            $obj = new \stdClass(); // Mock object
            $obj->candle = $candle;
            // Add method via magic call equivalent or just mock the method call if possible, 
            // but relying on partial mocks is complex. 
            // Better: Mock the MarketData model or use real objects if simple.
            // Let's use a real-ish object proxy
            return new class($candle) {
                public $data;
                public function __construct($data) { $this->data = $data; }
                public function getCandleArray() { return $this->data; }
            };
        });

        $this->marketDataService
            ->shouldReceive('getLatest')
            ->with('EUR/USD', 'H1', 200)
            ->andReturn($marketDataObjects);

        // Execute
        $result = $this->evaluator->evaluate($strategy, $signal);

        // Assert
        $this->assertTrue($result['pass']);
        $this->assertArrayHasKey('ema10', $result['indicators']);
        $this->assertArrayHasKey('ema20', $result['indicators']);
        // EMA10 should be > EMA20 in an uptrend
        $this->assertGreaterThan($result['indicators']['ema20'], $result['indicators']['ema10']);
    }

    public function test_evaluate_returns_false_when_condition_fails()
    {
        // Setup Strategy (Should fail if Price < EMA)
        $strategy = new FilterStrategy();
        $strategy->id = 2;
        $strategy->enabled = true;
        $strategy->config = [
            'indicators' => [
                'ema50' => ['period' => 50]
            ],
            'rules' => [
                'logic' => 'AND',
                'conditions' => [
                    [
                        'left' => 'price',
                        'operator' => '>',
                        'right' => 'ema50'
                    ]
                ]
            ]
        ];

        // Setup Signal
        $pair = CurrencyPair::factory()->create(['name' => 'GBP/USD']);
        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        
        // Mock Market Data (Simulate Downtrend)
        $candles = [];
        $basePrice = 1.3000;
        for ($i = 0; $i < 60; $i++) {
            $basePrice -= 0.0010; // Decreasing price
            $candles[] = [
                'timestamp' => now()->subHours(60 - $i)->timestamp,
                'open' => $basePrice,
                'high' => $basePrice + 0.0005,
                'low' => $basePrice - 0.0005,
                'close' => $basePrice, // Price is below historical average (EMA)
                'volume' => 1000
            ];
        }

        $marketDataObjects = collect($candles)->map(function ($candle) {
             return new class($candle) {
                public $data;
                public function __construct($data) { $this->data = $data; }
                public function getCandleArray() { return $this->data; }
            };
        });

        $this->marketDataService
            ->shouldReceive('getLatest')
            ->with('GBP/USD', 'H1', 200)
            ->andReturn($marketDataObjects);

        // Execute
        $result = $this->evaluator->evaluate($strategy, $signal);

        // Assert
        $this->assertFalse($result['pass']);
    }
}
