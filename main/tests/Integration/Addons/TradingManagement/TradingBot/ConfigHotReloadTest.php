<?php

declare(strict_types=1);

namespace Tests\Integration\Addons\TradingManagement\TradingBot;

use Tests\TestCase;
use Addons\TradingManagement\Modules\TradingBot\Services\ConfigManager\TradingBotConfigManager;
use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigHotReloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_update_triggers_redis_message(): void
    {
        $bot = TradingBot::factory()->create(['status' => 'running']);

        // Capture Redis publish calls
        $published = null;
        Redis::shouldReceive('publish')
            ->once()
            ->andReturnUsing(function ($channel, $message) use (&$published) {
                $published = ['channel' => $channel, 'message' => $message];
            });

        // Update config
        $manager = app(TradingBotConfigManager::class);
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.03]);

        // Verify
        $this->assertNotNull($published);
        $this->assertEquals("bot:{$bot->id}:config", $published['channel']);

        $data = json_decode($published['message'], true);
        $this->assertEquals('config_updated', $data['event']);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('timestamp', $data);
    }

    public function test_cache_invalidated_on_config_update(): void
    {
        $bot = TradingBot::factory()->create();

        $manager = app(TradingBotConfigManager::class);

        // Cache config
        $config1 = $manager->getRuntimeConfig($bot);

        // Update config
        $manager->updateConfig($bot, ['risk_per_trade_pct' => 0.05]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has("bot_config:{$bot->id}"));

        // New config should reflect changes
        $config2 = $manager->getRuntimeConfig($bot);
        $this->assertEquals(0.05, $config2['risk_per_trade_pct']);
    }

    public function test_new_config_loaded(): void
    {
        $bot = TradingBot::factory()->create();

        $manager = app(TradingBotConfigManager::class);

        // Update config
        $manager->updateConfig($bot, ['max_open_trades' => 5]);

        // Verify database updated
        $bot->refresh();
        $this->assertEquals(5, $bot->tradingPreset->max_open_trades);

        // Verify runtime config includes new value
        $config = $manager->getRuntimeConfig($bot);
        $this->assertEquals(5, $config['max_open_trades']);
    }
}
