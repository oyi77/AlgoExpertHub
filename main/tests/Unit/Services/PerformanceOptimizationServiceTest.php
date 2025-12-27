<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PerformanceOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PerformanceOptimizationService
 */
class PerformanceOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PerformanceOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PerformanceOptimizationService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PerformanceOptimizationService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
