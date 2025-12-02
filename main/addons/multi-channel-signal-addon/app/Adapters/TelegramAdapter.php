<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramAdapter extends BaseChannelAdapter
{
    protected const API_BASE_URL = 'https://api.telegram.org/bot';
    protected $lastUpdateId = null;

    public function connect(ChannelSource $channelSource): bool
    {
        try {
            $this->channelSource = $channelSource;
            $this->config = $channelSource->config ?? [];

            if (!$this->validateBotToken()) {
                return false;
            }

            $response = $this->makeApiRequest('getMe');

            if ($response && isset($response['ok']) && $response['ok']) {
                $this->connected = true;
                $this->lastUpdateId = $this->getConfig('last_update_id', 0);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError("Failed to connect to Telegram: " . $e->getMessage());
            return false;
        }
    }

    public function fetchMessages(): Collection
    {
        if (!$this->connected) {
            $this->connect($this->channelSource);
        }

        $messages = collect();

        try {
            $response = $this->makeApiRequest('getUpdates', [
                'offset' => $this->lastUpdateId + 1,
                'timeout' => 10,
                'allowed_updates' => ['message', 'channel_post']
            ]);

            if (!$response || !isset($response['ok']) || !$response['ok']) {
                return $messages;
            }

            $updates = $response['result'] ?? [];

            foreach ($updates as $update) {
                $messageText = null;
                $chatId = null;
                $messageId = null;

                if (isset($update['channel_post'])) {
                    $message = $update['channel_post'];
                    $messageText = $message['text'] ?? $message['caption'] ?? null;
                    $chatId = $message['chat']['id'] ?? null;
                    $messageId = $message['message_id'] ?? null;
                } elseif (isset($update['message'])) {
                    $message = $update['message'];
                    $messageText = $message['text'] ?? $message['caption'] ?? null;
                    $chatId = $message['chat']['id'] ?? null;
                    $messageId = $message['message_id'] ?? null;
                }

                if ($messageText && $chatId) {
                    $targetChatId = $this->getConfig('chat_id');
                    if ($targetChatId && $chatId != $targetChatId) {
                        continue;
                    }

                    $messages->push([
                        'text' => $messageText,
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                        'update_id' => $update['update_id'],
                        'timestamp' => $message['date'] ?? now()->timestamp,
                    ]);
                }

                if (isset($update['update_id'])) {
                    $this->lastUpdateId = max($this->lastUpdateId ?? 0, $update['update_id']);
                }
            }

            if ($this->lastUpdateId) {
                $config = $this->channelSource->config;
                $config['last_update_id'] = $this->lastUpdateId;
                $this->channelSource->update(['config' => $config]);
            }

        } catch (\Exception $e) {
            $this->logError("Failed to fetch Telegram messages: " . $e->getMessage());
        }

        return $messages;
    }

    public function validateConfig(array $config): bool
    {
        if (empty($config['bot_token'])) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get(self::API_BASE_URL . $config['bot_token'] . '/getMe');
            
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['ok']) && $data['ok'] === true;
            }
        } catch (\Exception $e) {
            Log::error("Telegram bot token validation failed: " . $e->getMessage());
        }

        return false;
    }

    public function getType(): string
    {
        return 'telegram';
    }

    protected function validateBotToken(): bool
    {
        $botToken = $this->getConfig('bot_token');
        
        if (empty($botToken)) {
            $this->logError("Bot token not configured");
            return false;
        }

        return $this->validateConfig(['bot_token' => $botToken]);
    }

    protected function makeApiRequest(string $method, array $params = []): ?array
    {
        $botToken = $this->getConfig('bot_token');
        
        if (empty($botToken)) {
            return null;
        }

        try {
            $url = self::API_BASE_URL . $botToken . '/' . $method;
            $response = Http::timeout(10)->post($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 429) {
                $retryAfter = $response->header('Retry-After', 60);
                Log::warning("Telegram API rate limit hit, waiting {$retryAfter} seconds");
                $this->rateLimit((int) $retryAfter);
                $response = Http::timeout(10)->post($url, $params);
                if ($response->successful()) {
                    return $response->json();
                }
            }

            $this->logError("Telegram API request failed: " . $response->body());
            return null;

        } catch (\Exception $e) {
            $this->logError("Telegram API request exception: " . $e->getMessage());
            return null;
        }
    }

    public function setWebhook(string $webhookUrl): bool
    {
        $response = $this->makeApiRequest('setWebhook', ['url' => $webhookUrl]);
        return $response && isset($response['ok']) && $response['ok'];
    }

    public function removeWebhook(): bool
    {
        $response = $this->makeApiRequest('deleteWebhook');
        return $response && isset($response['ok']) && $response['ok'];
    }

    public function getChat($chatId): ?array
    {
        $response = $this->makeApiRequest('getChat', ['chat_id' => $chatId]);
        if ($response && isset($response['ok']) && $response['ok']) {
            return $response['result'];
        }
        return null;
    }
}
