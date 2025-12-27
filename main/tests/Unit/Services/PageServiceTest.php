<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PageService
 */
class PageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PageService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PageService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
