<?php

namespace App\Parsers;

use App\Contracts\MessageParserInterface;
use App\DTOs\ParsedSignalData;
use Illuminate\Support\Collection;

class ParsingPipeline
{
    /**
     * Registered parsers.
     *
     * @var Collection
     */
    protected $parsers;

    /**
     * ParsingPipeline constructor.
     */
    public function __construct()
    {
        $this->parsers = collect();
        $this->registerDefaultParsers();
    }

    /**
     * Register default parsers.
     *
     * @return void
     */
    protected function registerDefaultParsers(): void
    {
        $this->register(new RegexMessageParser());
    }

    /**
     * Register a parser.
     *
     * @param MessageParserInterface $parser
     * @return void
     */
    public function register(MessageParserInterface $parser): void
    {
        $this->parsers->push($parser);
        $this->parsers = $this->parsers->sortByDesc(function ($parser) {
            return $parser->getPriority();
        })->values();
    }

    /**
     * Parse a message using all registered parsers.
     *
     * @param string $message
     * @return ParsedSignalData|null
     */
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

    /**
     * Get all registered parsers.
     *
     * @return Collection
     */
    public function getParsers(): Collection
    {
        return $this->parsers;
    }
}

