<?php

namespace Addons\OpenRouterIntegration\App\DTOs;

class OpenRouterResponse
{
    public bool $success;
    public ?string $content;
    public ?array $rawResponse;
    public ?string $error;
    public ?string $model;
    public ?array $usage;

    public function __construct(
        bool $success,
        ?string $content = null,
        ?array $rawResponse = null,
        ?string $error = null,
        ?string $model = null,
        ?array $usage = null
    ) {
        $this->success = $success;
        $this->content = $content;
        $this->rawResponse = $rawResponse;
        $this->error = $error;
        $this->model = $model;
        $this->usage = $usage;
    }

    /**
     * Create successful response.
     */
    public static function success(
        string $content,
        array $rawResponse,
        ?string $model = null,
        ?array $usage = null
    ): self {
        return new self(
            true,
            $content,
            $rawResponse,
            null,
            $model,
            $usage
        );
    }

    /**
     * Create error response.
     */
    public static function error(string $error, ?array $rawResponse = null): self
    {
        return new self(false, null, $rawResponse, $error);
    }

    /**
     * Parse JSON content.
     */
    public function parseJson(): ?array
    {
        if (!$this->content) {
            return null;
        }

        try {
            // Try to extract JSON from markdown code blocks
            $content = $this->content;
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            // Remove any leading/trailing whitespace
            $content = trim($content);

            return json_decode($content, true);
        } catch (\Exception $e) {
            return null;
        }
    }
}

