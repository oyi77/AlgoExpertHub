<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for ConfigurationService
 */
class ConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ConfigurationService::class);
    }

    /** @test */
    public function it_retrieves_configuration(): void
    {
        $config = Configuration::factory()->create([
            'appname' => 'Test App',
            'currency' => 'USD',
        ]);

        $result = $this->service->getConfiguration();

        $this->assertEquals('Test App', $result->appname);
        $this->assertEquals('USD', $result->currency);
    }

    /** @test */
    public function it_updates_configuration(): void
    {
        $config = Configuration::factory()->create();

        $updated = $this->service->updateConfiguration([
            'appname' => 'Updated App',
            'currency' => 'EUR',
        ]);

        $this->assertEquals('Updated App', $updated->appname);
        $this->assertEquals('EUR', $updated->currency);
    }

    /** @test */
    public function it_caches_configuration(): void
    {
        Configuration::factory()->create(['appname' => 'Cached App']);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(Configuration::first());

        $this->service->getCachedConfiguration();
    }

    /** @test */
    public function it_clears_configuration_cache_on_update(): void
    {
        $config = Configuration::factory()->create();

        Cache::shouldReceive('forget')
            ->once()
            ->with('app_configuration');

        $this->service->updateConfiguration(['appname' => 'New Name']);
    }
}
