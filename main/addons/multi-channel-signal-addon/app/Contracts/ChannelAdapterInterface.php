<?php

namespace Addons\MultiChannelSignalAddon\App\Contracts;

use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;

interface ChannelAdapterInterface
{
    public function connect(ChannelSource $channelSource): bool;
    public function disconnect(): void;
    public function fetchMessages(): Collection;
    public function validateConfig(array $config): bool;
    public function getType(): string;
}
