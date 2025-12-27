<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for DatabaseBackupService
 */
class DatabaseBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseBackupService::class);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DatabaseBackupService::class, $this->service);
    }

    /** @test */
    public function it_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, '__construct'));
    }
}
