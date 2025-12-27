<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AnalyticsService
 */
class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AnalyticsService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(AnalyticsService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
