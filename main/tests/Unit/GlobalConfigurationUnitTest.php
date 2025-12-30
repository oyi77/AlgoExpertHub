<?php

namespace Tests\Unit;

use App\Models\GlobalConfiguration;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalConfigurationUnitTest extends TestCase
{
    public function test_get_value_uses_cache()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('global_config:test_key', 86400, \Closure::class)
            ->andReturn('cached_value');

        $value = GlobalConfiguration::getValue('test_key');

        $this->assertEquals('cached_value', $value);
    }
}
