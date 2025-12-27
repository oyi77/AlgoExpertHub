<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for TemplateService
 */
class TemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TemplateService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TemplateService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
