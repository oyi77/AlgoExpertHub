<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for ThemeManager
 */
class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private ThemeManager $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ThemeManager::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ThemeManager::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
