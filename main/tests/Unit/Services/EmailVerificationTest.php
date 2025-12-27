<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for EmailVerification
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private EmailVerification $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmailVerification::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(EmailVerification::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
