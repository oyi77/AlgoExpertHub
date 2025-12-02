<?php

namespace Addons\MultiChannelSignalAddon\App\Parsers;

use Addons\MultiChannelSignalAddon\App\Contracts\MessageParserInterface;
use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Addons\MultiChannelSignalAddon\App\Models\ChannelSource;
use Illuminate\Support\Collection;

class ParsingPipeline
{
    protected $parsers;
    protected ?ChannelSource $channelSource = null;

    public function __construct(?ChannelSource $channelSource = null)
    {
        $this->channelSource = $channelSource;
        $this->parsers = collect();
        $this->registerDefaultParsers();
    }

    protected function registerDefaultParsers(): void
    {
        $preference = $this->channelSource?->parser_preference ?? 'auto';
        
        // Register parsers based on channel preference
        if ($preference === 'pattern' || $preference === 'auto') {
            // Register advanced pattern parser first (highest priority)
            $this->register(new AdvancedPatternParser($this->channelSource));
            
            // Register basic regex parser as fallback
            $this->register(new RegexMessageParser());
        }
        
        if ($preference === 'ai' || $preference === 'auto') {
            // Register AI parsers for each active configuration
            $aiConfigs = \Addons\MultiChannelSignalAddon\App\Models\AiConfiguration::getActive();
            foreach ($aiConfigs as $config) {
                $this->register(new AiMessageParser($config));
            }
        }
    }

    public function register(MessageParserInterface $parser): void
    {
        $this->parsers->push($parser);
        $this->parsers = $this->parsers->sortByDesc(function ($parser) {
            return $parser->getPriority();
        })->values();
    }

    public function parse(string $message): ?ParsedSignalData
    {
        $bestParse = null;
        $highestConfidence = 0;

        foreach ($this->parsers as $parser) {
            if (!$parser->canParse($message)) {
                continue;
            }

            $parsed = $parser->parse($message);

            if ($parsed && $parsed->confidence > $highestConfidence) {
                $bestParse = $parsed;
                $highestConfidence = $parsed->confidence;
            }
        }

        return $bestParse;
    }

    public function getParsers(): Collection
    {
        return $this->parsers;
    }
}
