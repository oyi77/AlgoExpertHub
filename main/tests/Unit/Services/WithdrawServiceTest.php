<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\WithdrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for WithdrawService
 */
class WithdrawServiceTest extends TestCase
{
    use RefreshDatabase;

    private WithdrawService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WithdrawService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(WithdrawService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
