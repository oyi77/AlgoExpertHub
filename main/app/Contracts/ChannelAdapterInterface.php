<?php

namespace App\Contracts;

use App\Models\ChannelSource;
use Illuminate\Support\Collection;

/**
 * Interface for channel adapters.
 * 
 * All channel adapters (Telegram, API, Web Scrape, RSS) must implement this interface.
 */
interface ChannelAdapterInterface
{
    /**
     * Connect to the channel source.
     *
     * @param ChannelSource $channelSource
     * @return bool
     * @throws \Exception
     */
    public function connect(ChannelSource $channelSource): bool;

    /**
     * Disconnect from the channel source.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Fetch messages from the channel.
     *
     * @return Collection
     * @throws \Exception
     */
    public function fetchMessages(): Collection;

    /**
     * Validate the channel configuration.
     *
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    public function validateConfig(array $config): bool;

    /**
     * Get the adapter type.
     *
     * @return string
     */
    public function getType(): string;
}

