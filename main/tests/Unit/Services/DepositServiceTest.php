<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for DepositService
 */
class DepositServiceTest extends TestCase
{
    use RefreshDatabase;

    private DepositService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DepositService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DepositService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
