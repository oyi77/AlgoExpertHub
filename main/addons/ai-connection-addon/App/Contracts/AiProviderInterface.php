<?php

namespace Addons\AiConnectionAddon\App\Contracts;

use Addons\AiConnectionAddon\App\Models\AiConnection;

interface AiProviderInterface
{
    /**
     * Execute AI call
     *
     * @param AiConnection $connection Connection to use
     * @param string $prompt Prompt to send
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Result with response, tokens_used, cost
     */
    public function execute(AiConnection $connection, string $prompt, array $options = []): array;

    /**
     * Test connection
     *
     * @param AiConnection $connection Connection to test
     * @return array Result with success, message
     */
    public function test(AiConnection $connection): array;

    /**
     * Get available models for this provider
     *
     * @param AiConnection $connection Connection to use
     * @return array List of available models
     */
    public function getAvailableModels(AiConnection $connection): array;

    /**
     * Estimate cost for given tokens
     *
     * @param int $tokens Number of tokens
     * @param string $model Model name
     * @return float Estimated cost in USD
     */
    public function estimateCost(int $tokens, string $model): float;

    /**
     * Get provider name
     *
     * @return string Provider name
     */
    public function getName(): string;

    /**
     * Get provider slug
     *
     * @return string Provider slug
     */
    public function getSlug(): string;
}

