<?php

namespace Tests\Unit\Addons\OpenRouterIntegration;

use Addons\OpenRouterIntegration\App\DTOs\MarketAnalysisResult;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Addons\OpenRouterIntegration\App\Services\OpenRouterMarketAnalyzer;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use App\Models\Signal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenRouterMarketAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_analysis_result_should_accept()
    {
        $result = new MarketAnalysisResult(
            'aligned',
            20,
            80,
            'accept',
            'Signal aligned with market trend'
        );

        $this->assertTrue($result->shouldAccept());
        $this->assertFalse($result->shouldReject());
        $this->assertFalse($result->shouldSizeDown());
    }

    public function test_market_analysis_result_should_reject()
    {
        $result = new MarketAnalysisResult(
            'against_trend',
            90,
            10,
            'reject',
            'Signal against market trend'
        );

        $this->assertFalse($result->shouldAccept());
        $this->assertTrue($result->shouldReject());
        $this->assertFalse($result->shouldSizeDown());
    }

    public function test_market_analysis_result_should_size_down()
    {
        $result = new MarketAnalysisResult(
            'weakly_aligned',
            60,
            40,
            'size_down',
            'Signal weakly aligned, reduce position size'
        );

        $this->assertFalse($result->shouldAccept());
        $this->assertFalse($result->shouldReject());
        $this->assertTrue($result->shouldSizeDown());
    }

    public function test_market_analysis_result_from_array()
    {
        $data = [
            'alignment' => 'aligned',
            'risk_score' => 30,
            'safety_score' => 70,
            'recommendation' => 'accept',
            'reasoning' => 'Good market conditions',
        ];

        $result = MarketAnalysisResult::fromArray($data);

        $this->assertEquals('aligned', $result->alignment);
        $this->assertEquals(30, $result->riskScore);
        $this->assertEquals(70, $result->safetyScore);
        $this->assertEquals('accept', $result->recommendation);
        $this->assertEquals('Good market conditions', $result->reasoning);
    }

    public function test_market_analysis_result_to_array()
    {
        $result = new MarketAnalysisResult(
            'aligned',
            25,
            75,
            'accept',
            'Signal looks good'
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('alignment', $array);
        $this->assertArrayHasKey('risk_score', $array);
        $this->assertArrayHasKey('safety_score', $array);
        $this->assertArrayHasKey('recommendation', $array);
        $this->assertArrayHasKey('reasoning', $array);
    }
}

