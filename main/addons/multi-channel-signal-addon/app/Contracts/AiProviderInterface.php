<?php

namespace Addons\MultiChannelSignalAddon\App\Contracts;

use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;

interface AiProviderInterface
{
    /**
     * Get provider name.
     */
    public function getName(): string;

    /**
     * Get provider identifier.
     */
    public function getProvider(): string;

    /**
     * Parse message using AI.
     *
     * @param string $message
     * @param AiConfiguration $config
     * @return array|null Parsed data or null on failure
     */
    public function parse(string $message, AiConfiguration $config): ?array;

    /**
     * Test API connection.
     *
     * @param AiConfiguration $config
     * @return bool
     */
    public function testConnection(AiConfiguration $config): bool;
}

