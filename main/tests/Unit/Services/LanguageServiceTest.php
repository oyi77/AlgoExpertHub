<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LanguageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for LanguageService
 */
class LanguageServiceTest extends TestCase
{
    use RefreshDatabase;

    private LanguageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LanguageService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LanguageService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
