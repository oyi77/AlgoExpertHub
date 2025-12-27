<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CacheManager
 */
class CacheManagerTest extends TestCase
{
    use RefreshDatabase;

    private CacheManager $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CacheManager::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CacheManager::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
