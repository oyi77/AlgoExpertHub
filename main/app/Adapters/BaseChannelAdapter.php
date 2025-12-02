<?php

namespace App\Adapters;

use App\Contracts\ChannelAdapterInterface;
use App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Base class for channel adapters.
 * 
 * Provides common functionality for all channel adapters.
 */
abstract class BaseChannelAdapter implements ChannelAdapterInterface
{
    /**
     * The channel source instance.
     *
     * @var ChannelSource
     */
    protected $channelSource;

    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * The connection status.
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * BaseChannelAdapter constructor.
     *
     * @param ChannelSource $channelSource
     */
    public function __construct(ChannelSource $channelSource)
    {
        $this->channelSource = $channelSource;
        $this->config = $channelSource->config ?? [];
    }

    /**
     * Get the configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setConfig(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Log an error.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("Channel Adapter Error [{$this->getType()}]: {$message}", array_merge([
            'channel_source_id' => $this->channelSource->id,
            'channel_source_name' => $this->channelSource->name,
        ], $context));

        // Update channel source error count
        $this->channelSource->incrementError($message);
    }

    /**
     * Check if connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Get the channel source.
     *
     * @return ChannelSource
     */
    public function getChannelSource(): ChannelSource
    {
        return $this->channelSource;
    }

    /**
     * Disconnect from the channel source.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->connected = false;
    }

    /**
     * Rate limit helper - delay execution.
     *
     * @param int $seconds
     * @return void
     */
    protected function rateLimit(int $seconds): void
    {
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    /**
     * Abstract method to be implemented by child classes.
     * Each adapter must implement its own connection logic.
     */
    abstract public function connect(ChannelSource $channelSource): bool;

    /**
     * Abstract method to be implemented by child classes.
     * Each adapter must implement its own message fetching logic.
     */
    abstract public function fetchMessages(): Collection;

    /**
     * Abstract method to be implemented by child classes.
     * Each adapter must implement its own config validation.
     */
    abstract public function validateConfig(array $config): bool;

    /**
     * Abstract method to be implemented by child classes.
     * Each adapter must return its type.
     */
    abstract public function getType(): string;
}

