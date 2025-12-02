<?php

namespace Addons\OpenRouterIntegration\App\Contracts;

use Addons\OpenRouterIntegration\App\DTOs\OpenRouterRequest;
use Addons\OpenRouterIntegration\App\DTOs\OpenRouterResponse;
use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Addons\OpenRouterIntegration\App\Models\OpenRouterModel;
use Illuminate\Support\Collection;

interface OpenRouterServiceInterface
{
    /**
     * Send request to OpenRouter API.
     */
    public function sendRequest(OpenRouterRequest $request): OpenRouterResponse;

    /**
     * Fetch and sync available models from OpenRouter API.
     */
    public function fetchAvailableModels(): Collection;

    /**
     * Test connection with given configuration.
     */
    public function testConnection(OpenRouterConfiguration $config): bool;

    /**
     * Get model information.
     */
    public function getModelInfo(string $modelId): ?OpenRouterModel;
}

