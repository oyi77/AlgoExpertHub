<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\Plan;
use App\Models\TimeFrame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class ChannelSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo channel sources for multi-channel signal ingestion
     */
    public function run()
    {
        if (!class_exists(\Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class)) {
            $this->command->warn('Channel Source model not found. Skipping seeder.');
            return;
        }

        $modelClass = \Addons\MultiChannelSignalAddon\App\Models\ChannelSource::class;

        // Get required data
        $plans = Plan::all();
        $markets = Market::all();
        $timeframes = TimeFrame::all();

        if ($plans->isEmpty() || $markets->isEmpty() || $timeframes->isEmpty()) {
            $this->command->warn('Plans, markets, or timeframes not found. Skipping channel source seeding.');
            return;
        }

        // Check if parser_preference column exists
        $hasParserPreference = \Illuminate\Support\Facades\Schema::hasColumn('channel_sources', 'parser_preference');

        $channels = [
            [
                'user_id' => null,
                'name' => 'Premium Telegram Signals',
                'type' => 'telegram',
                'config' => [
                    'bot_token' => 'demo_bot_token_12345',
                    'channel_username' => '@premium_signals',
                    'parse_mode' => 'html',
                ],
                'status' => 'active',
                'last_processed_at' => now()->subMinutes(5),
                'error_count' => 0,
                'last_error' => null,
                'auto_publish_confidence_threshold' => 80,
                'default_plan_id' => $plans->first()->id,
                'default_market_id' => $markets->where('name', 'Forex')->first()->id ?? $markets->first()->id,
                'default_timeframe_id' => $timeframes->first()->id,
                'is_admin_owned' => true,
                'scope' => 'global',
            ],
            [
                'user_id' => null,
                'name' => 'Crypto Signal Channel',
                'type' => 'telegram',
                'config' => [
                    'bot_token' => 'demo_bot_token_67890',
                    'channel_username' => '@crypto_signals',
                    'parse_mode' => 'markdown',
                ],
                'status' => 'active',
                'last_processed_at' => now()->subMinutes(10),
                'error_count' => 0,
                'last_error' => null,
                'auto_publish_confidence_threshold' => 75,
                'default_plan_id' => $plans->skip(1)->first()->id ?? $plans->first()->id,
                'default_market_id' => $markets->where('name', 'Crypto')->first()->id ?? $markets->first()->id,
                'default_timeframe_id' => $timeframes->skip(1)->first()->id ?? $timeframes->first()->id,
                'is_admin_owned' => true,
                'scope' => 'plan',
            ],
            [
                'user_id' => null,
                'name' => 'RSS Forex News Feed',
                'type' => 'rss',
                'config' => [
                    'url' => 'https://example.com/forex-signals.xml',
                    'poll_interval' => 300, // 5 minutes
                ],
                'status' => 'active',
                'last_processed_at' => now()->subMinutes(15),
                'error_count' => 0,
                'last_error' => null,
                'auto_publish_confidence_threshold' => 70,
                'default_plan_id' => $plans->first()->id,
                'default_market_id' => $markets->where('name', 'Forex')->first()->id ?? $markets->first()->id,
                'default_timeframe_id' => $timeframes->first()->id,
                'is_admin_owned' => true,
                'scope' => 'global',
            ],
            [
                'user_id' => null,
                'name' => 'API Webhook Endpoint',
                'type' => 'api',
                'config' => [
                    'webhook_url' => '/api/webhooks/signals',
                    'api_key' => 'demo_api_key_12345',
                    'format' => 'json',
                ],
                'status' => 'active',
                'last_processed_at' => now()->subMinutes(20),
                'error_count' => 0,
                'last_error' => null,
                'auto_publish_confidence_threshold' => 85,
                'default_plan_id' => $plans->first()->id,
                'default_market_id' => $markets->first()->id,
                'default_timeframe_id' => $timeframes->first()->id,
                'is_admin_owned' => true,
                'scope' => 'global',
            ],
            [
                'user_id' => null,
                'name' => 'Web Scraper - TradingView Signals',
                'type' => 'web_scrape',
                'config' => [
                    'url' => 'https://example.com/tradingview-signals',
                    'selector' => '.signal-item',
                    'poll_interval' => 600, // 10 minutes
                ],
                'status' => 'paused',
                'last_processed_at' => now()->subHours(1),
                'error_count' => 2,
                'last_error' => 'Connection timeout',
                'auto_publish_confidence_threshold' => 65,
                'default_plan_id' => $plans->first()->id,
                'default_market_id' => $markets->first()->id,
                'default_timeframe_id' => $timeframes->first()->id,
                'is_admin_owned' => true,
                'scope' => 'plan',
            ],
        ];

        // Add parser_preference if column exists
        if ($hasParserPreference) {
            $channels[0]['parser_preference'] = 'ai';
            $channels[1]['parser_preference'] = 'regex';
            $channels[2]['parser_preference'] = 'pattern';
            $channels[3]['parser_preference'] = 'ai';
            $channels[4]['parser_preference'] = 'regex';
        }

        foreach ($channels as $channel) {
            $existing = $modelClass::where('name', $channel['name'])->first();
            
            if (!$existing) {
                $channelSource = new $modelClass();
                $channelSource->fill($channel);
                $channelSource->save();
            }
        }

        $this->command->info('Channel Sources seeded successfully!');
    }
}
