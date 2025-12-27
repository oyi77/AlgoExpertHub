<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for ReportService
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ReportService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
