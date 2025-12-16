<?php

namespace Addons\TradingManagement\Tests\Unit;

use Tests\TestCase;
use Addons\TradingManagement\Modules\RiskManagement\Services\Calculators\SmartRiskCalculator;
use App\Models\Signal;
use App\Models\CurrencyPair;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SmartRiskCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SmartRiskCalculator();
    }

    public function test_calculate_position_size_increases_risk_with_high_confidence()
    {
        // Setup Signal with High AI Confidence
        $pair = CurrencyPair::factory()->create(['name' => 'EUR/USD']);
        $aiDecision = new AiDecision();
        $aiDecision->confidence = 80; // High confidence (normalized > 0 -> factor > 1)

        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->setRelation('aiDecision', $aiDecision);
        
        $signal->open_price = 1.1000;
        $signal->sl = 1.0950; // 50 pips SL
        $signal->direction = 'buy';
        // Note: calculateSLDistance uses DB defaults if relationships missing, simplified here.

        $accountInfo = ['equity' => 10000];
        $config = ['risk_per_trade_pct' => 1.0]; // Base risk 1% ($100)

        // Execute
        $result = $this->calculator->calculatePositionSize($signal, $accountInfo, $config);

        // Assert
        // Base risk = 1.0%
        // Score = 80 (from AI) -> Normalized (80-50)/50 = 0.6
        // Factor = 1.0 + (0.6 * 0.5) = 1.3
        // Adjusted Risk = 1.3%
        $this->assertEquals(1.3, $result['risk_percent']);
        $this->assertEquals(130, $result['risk_amount']);
    }

    public function test_calculate_position_size_decreases_risk_with_low_confidence()
    {
        // Setup Signal with Low AI Confidence
        $pair = CurrencyPair::factory()->create(['name' => 'EUR/USD']);
        $aiDecision = new AiDecision();
        $aiDecision->confidence = 20; // Low confidence

        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->setRelation('aiDecision', $aiDecision);
        
        $signal->open_price = 1.1000;
        $signal->sl = 1.0950;
        $signal->direction = 'buy';

        $accountInfo = ['equity' => 10000];
        $config = ['risk_per_trade_pct' => 1.0];

        // Execute
        $result = $this->calculator->calculatePositionSize($signal, $accountInfo, $config);

        // Assert
        // Score = 20 -> Normalized (20-50)/50 = -0.6
        // Factor = 1.0 + (-0.6 * 0.5) = 0.7
        // Adjusted Risk = 0.7%
        $this->assertEquals(0.7, $result['risk_percent']);
        $this->assertEquals(70, $result['risk_amount']);
    }

    public function test_calculate_position_size_uses_default_when_no_ai()
    {
        // Setup Signal with NO AI Confidence
        $pair = CurrencyPair::factory()->create(['name' => 'EUR/USD']);

        $signal = new Signal();
        $signal->pair_id = $pair->id;
        $signal->setRelation('pair', $pair);
        $signal->open_price = 1.1000;
        $signal->sl = 1.0950;
        $signal->direction = 'buy';
        $signal->auto_created = false; // Manual signal

        $accountInfo = ['equity' => 10000];
        $config = ['risk_per_trade_pct' => 1.0];

        // Execute
        $result = $this->calculator->calculatePositionSize($signal, $accountInfo, $config);

        // Assert
        // Default manual score = 80
        // Normalized (80-50)/50 = 0.6
        // Factor = 1.3
        // Expect 1.3% because manual signals are trusted by default in code logic
        $this->assertEquals(1.3, $result['risk_percent']);
    }
}
