<?php

namespace Addons\OpenRouterIntegration\App\DTOs;

use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;

class OpenRouterRequest
{
    public string $model;
    public array $messages;
    public float $temperature;
    public int $maxTokens;
    public ?string $siteUrl;
    public ?string $siteName;

    public function __construct(
        string $model,
        array $messages,
        float $temperature = 0.3,
        int $maxTokens = 500,
        ?string $siteUrl = null,
        ?string $siteName = null
    ) {
        $this->model = $model;
        $this->messages = $messages;
        $this->temperature = $temperature;
        $this->maxTokens = $maxTokens;
        $this->siteUrl = $siteUrl;
        $this->siteName = $siteName;
    }

    /**
     * Create from configuration.
     */
    public static function fromConfig(
        OpenRouterConfiguration $config,
        array $messages
    ): self {
        return new self(
            $config->model_id,
            $messages,
            $config->temperature,
            $config->max_tokens,
            $config->site_url,
            $config->site_name
        );
    }

    /**
     * Convert to array for API request.
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'messages' => $this->messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];
    }

    /**
     * Get headers for API request.
     */
    public function getHeaders(string $apiKey): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->siteUrl) {
            $headers['HTTP-Referer'] = $this->siteUrl;
        }

        if ($this->siteName) {
            $headers['X-Title'] = $this->siteName;
        }

        return $headers;
    }
}

