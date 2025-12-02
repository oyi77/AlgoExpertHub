<?php

namespace Addons\MultiChannelSignalAddon\App\Adapters;

use Addons\MultiChannelSignalAddon\App\Contracts\ChannelAdapterInterface;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class BaseChannelAdapter implements ChannelAdapterInterface
{
    protected ChannelSource $channelSource;

    protected array $config;

    protected bool $connected = false;

    public function __construct(ChannelSource $channelSource)
    {
        $this->channelSource = $channelSource;
        $this->config = $channelSource->config ?? [];
    }

    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    protected function setConfig(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("Channel Adapter Error [{$this->getType()}]: {$message}", array_merge([
            'channel_source_id' => $this->channelSource->id,
            'channel_source_name' => $this->channelSource->name,
        ], $context));

        if (method_exists($this->channelSource, 'incrementError')) {
            $this->channelSource->incrementError($message);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getChannelSource(): ChannelSource
    {
        return $this->channelSource;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    protected function rateLimit(int $seconds): void
    {
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    abstract public function connect(ChannelSource $channelSource): bool;

    abstract public function fetchMessages(): Collection;

    abstract public function validateConfig(array $config): bool;

    abstract public function getType(): string;
}
