<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for UserService
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(UserService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
