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
            // Register AI parsers for each enabled parsing profile
            $query = \Addons\MultiChannelSignalAddon\App\Models\AiParsingProfile::with('aiConnection')
                ->enabled()
                ->byPriority();

            // If this pipeline is for a specific channel, include both channel-specific and global profiles
            if ($this->channelSource) {
                $query->where(function ($q) {
                    $q->whereNull('channel_source_id') // Global profiles
                      ->orWhere('channel_source_id', $this->channelSource->id); // Channel-specific profiles
                });
            } else {
                // Only use global profiles if no channel specified
                $query->whereNull('channel_source_id');
            }

            $profiles = $query->get();
            
            foreach ($profiles as $profile) {
                if ($profile->isUsable()) {
                    $this->register(new AiMessageParser($profile));
                }
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
