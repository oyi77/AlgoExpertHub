<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ApiAdapter extends BaseChannelAdapter
{
    public function connect(ChannelSource $channelSource): bool
    {
        try {
            $this->channelSource = $channelSource;
            $this->config = $channelSource->config ?? [];

            $webhookUrl = $this->getConfig('webhook_url');
            if (empty($webhookUrl)) {
                $this->logError('Webhook URL not configured');

                return false;
            }

            $this->connected = true;

            return true;
        } catch (\Throwable $th) {
            $this->logError('Failed to connect to API: ' . $th->getMessage());

            return false;
        }
    }

    public function fetchMessages(): Collection
    {
        return collect();
    }

    public function validateConfig(array $config): bool
    {
        if (!empty($config['webhook_url']) && !filter_var($config['webhook_url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        if (array_key_exists('secret_key', $config) && $config['secret_key'] === '') {
            return false;
        }

        return true;
    }

    public function getType(): string
    {
        return 'api';
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        $secretKey = $this->getConfig('secret_key');

        if (empty($secretKey)) {
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public function generateWebhookUrl(): string
    {
        return url('/api/webhook/channel/' . $this->channelSource->id);
    }
}
