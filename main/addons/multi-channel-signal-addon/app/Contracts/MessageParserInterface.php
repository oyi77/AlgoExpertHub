<?php

namespace Addons\MultiChannelSignalAddon\App\Contracts;

use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;

interface MessageParserInterface
{
    public function canParse(string $message): bool;
    public function parse(string $message): ?ParsedSignalData;
    public function getName(): string;
    public function getPriority(): int;
}
