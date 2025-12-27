<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SignalModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for SignalModificationService
 */
class SignalModificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SignalModificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SignalModificationService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SignalModificationService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
