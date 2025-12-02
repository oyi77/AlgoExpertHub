<?php

namespace App\Contracts;

use App\DTOs\ParsedSignalData;

/**
 * Interface for message parsers.
 * 
 * All message parsers must implement this interface.
 */
interface MessageParserInterface
{
    /**
     * Check if the parser can parse the given message.
     *
     * @param string $message
     * @return bool
     */
    public function canParse(string $message): bool;

    /**
     * Parse the message and extract signal data.
     *
     * @param string $message
     * @return ParsedSignalData|null Returns null if parsing fails
     */
    public function parse(string $message): ?ParsedSignalData;

    /**
     * Get the parser name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the parser priority (higher priority tried first).
     *
     * @return int
     */
    public function getPriority(): int;
}

