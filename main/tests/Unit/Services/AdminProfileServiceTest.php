<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AdminProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AdminProfileService
 */
class AdminProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminProfileService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(AdminProfileService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
