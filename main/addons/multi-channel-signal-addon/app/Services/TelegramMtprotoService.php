<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Addons\MultiChannelSignalAddon\App\Adapters\TelegramMtprotoAdapter;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Facades\Log;

class TelegramMtprotoService
{
    /**
     * Create a new Telegram MTProto channel source.
     *
     * @param array $data
     * @return array
     */
    public function createChannel(array $data): array
    {
        try {
            // Check if MadelineProto is available
            if (!class_exists('\danog\MadelineProto\API')) {
                return [
                    'type' => 'error',
                    'message' => 'MadelineProto library not installed. Please run: composer require danog/madelineproto'
                ];
            }

            // Validate API credentials
            $adapter = new TelegramMtprotoAdapter(new ChannelSource());
            $config = [
                'api_id' => $data['api_id'],
                'api_hash' => $data['api_hash'],
                'phone_number' => $data['phone_number'] ?? null,
                'channel_username' => $data['channel_username'] ?? null,
                'channel_id' => $data['channel_id'] ?? null,
            ];

            if (!$adapter->validateConfig($config)) {
                return [
                    'type' => 'error',
                    'message' => 'Invalid API ID or API Hash'
                ];
            }

            // Create channel source (status will be 'pending' until authenticated)
            // DO NOT call startAuth() here - it will trigger MadelineProto web UI
            // Authentication will be handled separately via the authenticate route
            $channelSource = ChannelSource::create([
                'user_id' => $data['user_id'] ?? null,
                'is_admin_owned' => $data['user_id'] === null,
                'name' => $data['name'],
                'type' => 'telegram_mtproto',
                'config' => $config,
                'status' => 'pending', // Will be 'active' after authentication
                'default_plan_id' => $data['default_plan_id'] ?? null,
                'default_market_id' => $data['default_market_id'] ?? null,
                'default_timeframe_id' => $data['default_timeframe_id'] ?? null,
                'auto_publish_confidence_threshold' => $data['auto_publish_confidence_threshold'] ?? 90,
            ]);

            // Return phone_required to redirect to authentication page
            // Authentication will be handled step-by-step via our own UI
            return [
                'type' => 'phone_required',
                'message' => 'Channel created. Please authenticate your Telegram account.',
                'channel_source' => $channelSource,
                'step' => 'phone'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to create Telegram MTProto channel: " . $e->getMessage(), [
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
     * Complete authentication with verification code.
     *
     * @param ChannelSource $channelSource
     * @param string $code
     * @param string $phoneCodeHash
     * @return array
     */
    public function completeAuth(ChannelSource $channelSource, string $code, string $phoneCodeHash): array
    {
        try {
            // CRITICAL: Refresh channel source to ensure we have latest config (including phone_number)
            $channelSource->refresh();
            
            // Log config for debugging
            Log::info("completeAuth - Channel config", [
                'channel_id' => $channelSource->id,
                'has_phone_number' => isset($channelSource->config['phone_number']),
                'phone_number' => $channelSource->config['phone_number'] ?? 'NOT SET',
                'config_keys' => array_keys($channelSource->config ?? [])
            ]);
            
            $adapter = new TelegramMtprotoAdapter($channelSource);
            $result = $adapter->completeAuth($code, $phoneCodeHash);

            if ($result['type'] === 'success') {
                $channelSource->update(['status' => 'active']);
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Authentication failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete password authentication (2FA).
     *
     * @param ChannelSource $channelSource
     * @param string $password
     * @return array
     */
    public function completePasswordAuth(ChannelSource $channelSource, string $password): array
    {
        try {
            $channelSource->refresh();
            
            $adapter = new TelegramMtprotoAdapter($channelSource);
            $result = $adapter->completePasswordAuth($password);

            if ($result['type'] === 'success') {
                $config = $channelSource->config;
                unset($config['password_required']); // Remove password requirement flag
                $channelSource->update([
                    'status' => 'active',
                    'config' => $config
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Password authentication failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get dialogs (chats, channels, groups) for the authenticated user.
     *
     * @param ChannelSource $channelSource
     * @return array
     */
    public function getDialogs(ChannelSource $channelSource): array
    {
        try {
            $adapter = new TelegramMtprotoAdapter($channelSource);
            
            // For MTProto, we need to ensure $_POST is set for the adapter
            // The adapter checks $_POST to determine if it should start
            if ($channelSource->type === 'telegram_mtproto' && empty($_POST)) {
                $_POST = ['_token' => request()->header('X-CSRF-TOKEN') ?? csrf_token()];
            }
            
            if (!$adapter->connect($channelSource)) {
                return [
                    'type' => 'error',
                    'message' => 'Not authenticated. Please complete authentication first.',
                    'dialogs' => []
                ];
            }

            $dialogs = $adapter->getDialogs();

            return [
                'type' => 'success',
                'dialogs' => $dialogs
            ];

        } catch (\Exception $e) {
            Log::error("Failed to get dialogs: " . $e->getMessage(), [
                'channel_source_id' => $channelSource->id,
                'exception' => $e,
            ]);
            return [
                'type' => 'error',
                'message' => 'Failed to get dialogs: ' . $e->getMessage(),
                'dialogs' => []
            ];
        }
    }

    /**
     * Get dialogs in chunks for progressive loading.
     *
     * @param ChannelSource $channelSource
     * @param int $chunk Chunk number (0-based)
     * @param int $chunkSize Number of dialogs per chunk
     * @return array
     */
    public function getDialogsChunked(ChannelSource $channelSource, int $chunk = 0, int $chunkSize = 15): array
    {
        try {
            $adapter = new TelegramMtprotoAdapter($channelSource);
            
            // For MTProto, we need to ensure $_POST is set for the adapter
            if ($channelSource->type === 'telegram_mtproto' && empty($_POST)) {
                $_POST = ['_token' => request()->header('X-CSRF-TOKEN') ?? csrf_token()];
            }
            
            if (!$adapter->connect($channelSource)) {
                // Check if password is required
                $channelSource->refresh();
                if ($channelSource->config['password_required'] ?? false) {
                    return [
                        'type' => 'error',
                        'message' => 'Two-factor authentication password required. Please complete password authentication first.',
                        'password_required' => true,
                        'hint' => $channelSource->config['password_hint'] ?? '',
                        'dialogs' => [],
                        'has_more' => false,
                    ];
                }
                
                return [
                    'type' => 'error',
                    'message' => 'Not authenticated. Please complete authentication first.',
                    'dialogs' => [],
                    'has_more' => false,
                ];
            }

            // Get dialogs in chunks
            $result = $adapter->getDialogsChunked($chunk, $chunkSize);

            // Check if result indicates loading state
            if (isset($result['loading']) && $result['loading']) {
                return [
                    'type' => 'success', // Still success, but with loading indicator
                    'dialogs' => [],
                    'has_more' => false,
                    'total_loaded' => 0,
                    'loading' => true,
                    'message' => $result['message'] ?? 'Loading channels, please wait...',
                ];
            }

            return [
                'type' => 'success',
                'dialogs' => $result['dialogs'] ?? [],
                'has_more' => $result['has_more'] ?? false,
                'total_loaded' => $result['total_loaded'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to get dialogs chunked: " . $e->getMessage(), [
                'channel_source_id' => $channelSource->id,
                'chunk' => $chunk,
                'exception' => $e,
            ]);
            return [
                'type' => 'error',
                'message' => 'Failed to get dialogs: ' . $e->getMessage(),
                'dialogs' => [],
                'has_more' => false,
            ];
        }
    }

    /**
     * Fetch sample messages from a specific channel.
     *
     * @param ChannelSource $channelSource
     * @param string|int $channelId Channel ID or username
     * @param int $limit Number of messages to fetch
     * @return array
     */
    public function fetchSampleMessages(ChannelSource $channelSource, $channelId, int $limit = 20): array
    {
        try {
            $adapter = new TelegramMtprotoAdapter($channelSource);
            
            // For MTProto, we need to ensure $_POST is set for the adapter
            if ($channelSource->type === 'telegram_mtproto' && empty($_POST)) {
                $_POST = ['_token' => request()->header('X-CSRF-TOKEN') ?? csrf_token()];
            }
            
            if (!$adapter->connect($channelSource)) {
                return [
                    'success' => false,
                    'error' => 'Not authenticated. Please complete authentication first.',
                ];
            }

            $result = $adapter->fetchSampleMessages($channelId, $limit);

            return $result;

        } catch (\Exception $e) {
            Log::error("Failed to fetch sample messages: " . $e->getMessage(), [
                'channel_source_id' => $channelSource->id,
                'channel_id' => $channelId,
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'error' => 'Failed to fetch messages: ' . $e->getMessage(),
            ];
        }
    }
}


