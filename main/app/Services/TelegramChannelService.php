<?php

namespace App\Services;

use App\Adapters\TelegramAdapter;
use App\Models\ChannelSource;
use Illuminate\Support\Facades\Log;

class TelegramChannelService
{
    /**
     * Create a new Telegram channel source.
     *
     * @param array $data
     * @return array
     */
    public function createChannel(array $data): array
    {
        try {
            // Validate bot token
            $adapter = new TelegramAdapter(new ChannelSource());
            $config = [
                'bot_token' => $data['bot_token'],
                'chat_id' => $data['chat_id'] ?? null,
                'chat_username' => $data['chat_username'] ?? null,
            ];

            if (!$adapter->validateConfig($config)) {
                return [
                    'type' => 'error',
                    'message' => 'Invalid bot token or bot token validation failed'
                ];
            }

            // Create channel source
            $channelSource = ChannelSource::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'type' => 'telegram',
                'config' => $config,
                'status' => 'active',
                'default_plan_id' => $data['default_plan_id'] ?? null,
                'default_market_id' => $data['default_market_id'] ?? null,
                'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
                'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
            ]);

            // Test connection
            $adapter = new TelegramAdapter($channelSource);
            if (!$adapter->connect($channelSource)) {
                $channelSource->update(['status' => 'error']);
                return [
                    'type' => 'error',
                    'message' => 'Failed to connect to Telegram. Please check your bot token and ensure the bot is added to the channel.'
                ];
            }

            // If chat_id or chat_username provided, verify access
            if (!empty($data['chat_id']) || !empty($data['chat_username'])) {
                $chatId = $data['chat_id'] ?? $data['chat_username'];
                $chat = $adapter->getChat($chatId);
                
                if (!$chat) {
                    return [
                        'type' => 'warning',
                        'message' => 'Channel created but could not verify channel access. Please ensure the bot is added as admin to the channel.',
                        'channel_source' => $channelSource
                    ];
                }

                // Update config with verified chat info
                $config['chat_id'] = $chat['id'];
                $config['chat_title'] = $chat['title'] ?? $chat['username'] ?? null;
                $channelSource->update(['config' => $config]);
            }

            return [
                'type' => 'success',
                'message' => 'Telegram channel created successfully',
                'channel_source' => $channelSource
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create Telegram channel: " . $e->getMessage(), [
                'exception' => $e,
                'data' => $data
            ]);

            return [
                'type' => 'error',
                'message' => 'Failed to create channel: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test bot token.
     *
     * @param string $botToken
     * @return array
     */
    public function testBotToken(string $botToken): array
    {
        try {
            $adapter = new TelegramAdapter(new ChannelSource());
            $config = ['bot_token' => $botToken];

            if ($adapter->validateConfig($config)) {
                return [
                    'type' => 'success',
                    'message' => 'Bot token is valid'
                ];
            }

            return [
                'type' => 'error',
                'message' => 'Invalid bot token'
            ];

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Error testing bot token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update webhook for channel source.
     *
     * @param ChannelSource $channelSource
     * @param string $webhookUrl
     * @return array
     */
    public function updateWebhook(ChannelSource $channelSource, string $webhookUrl): array
    {
        try {
            $adapter = new TelegramAdapter($channelSource);
            
            if (!$adapter->connect($channelSource)) {
                return [
                    'type' => 'error',
                    'message' => 'Failed to connect to Telegram'
                ];
            }

            if ($adapter->setWebhook($webhookUrl)) {
                return [
                    'type' => 'success',
                    'message' => 'Webhook updated successfully'
                ];
            }

            return [
                'type' => 'error',
                'message' => 'Failed to update webhook'
            ];

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Error updating webhook: ' . $e->getMessage()
            ];
        }
    }
}

