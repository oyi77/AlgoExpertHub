<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\GlobalConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GlobalConfigurationService
 */
class GlobalConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GlobalConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GlobalConfigurationService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(GlobalConfigurationService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
