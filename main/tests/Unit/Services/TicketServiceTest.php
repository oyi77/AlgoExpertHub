<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for TicketService
 */
class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TicketService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TicketService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
