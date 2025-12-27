<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MediaService
 */
class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MediaService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(MediaService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
