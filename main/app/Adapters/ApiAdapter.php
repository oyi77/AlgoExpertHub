<?php

namespace App\Adapters;

use App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiAdapter extends BaseChannelAdapter
{
    /**
     * Connect to API channel source.
     *
     * @param ChannelSource $channelSource
     * @return bool
     */
    public function connect(ChannelSource $channelSource): bool
    {
        try {
            $this->channelSource = $channelSource;
            $this->config = $channelSource->config ?? [];

            // Validate webhook URL exists
            $webhookUrl = $this->getConfig('webhook_url');
            if (empty($webhookUrl)) {
                $this->logError("Webhook URL not configured");
                return false;
            }

            $this->connected = true;
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to connect to API: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch messages from API.
     * Note: For webhook-based APIs, messages are received via webhook, not fetched.
     *
     * @return Collection
     */
    public function fetchMessages(): Collection
    {
        // For webhook-based APIs, messages are received via webhook endpoint
        // This method is kept for consistency but typically returns empty
        return collect();
    }

    /**
     * Validate channel configuration.
     *
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool
    {
        // Webhook URL is optional (system generates it)
        // But we can validate if a custom webhook URL is provided
        if (isset($config['webhook_url']) && !empty($config['webhook_url'])) {
            if (!filter_var($config['webhook_url'], FILTER_VALIDATE_URL)) {
                return false;
            }
        }

        // Validate secret key format if provided
        if (isset($config['secret_key']) && empty($config['secret_key'])) {
            return false;
        }

        return true;
    }

    /**
     * Get the adapter type.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'api';
    }

    /**
     * Verify webhook signature.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $secretKey = $this->getConfig('secret_key');
        
        if (empty($secretKey)) {
            // No secret key configured, skip verification
            return true;
        }

        // HMAC-SHA256 signature verification
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate webhook URL for this channel source.
     *
     * @return string
     */
    public function generateWebhookUrl(): string
    {
        return url('/api/webhook/channel/' . $this->channelSource->id);
    }
}

