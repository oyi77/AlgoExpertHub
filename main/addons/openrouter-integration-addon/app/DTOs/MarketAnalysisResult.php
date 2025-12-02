<?php

namespace Addons\OpenRouterIntegration\App\DTOs;

class MarketAnalysisResult
{
    public string $alignment;
    public int $riskScore;
    public int $safetyScore;
    public string $recommendation;
    public string $reasoning;
    public array $rawResponse;

    public function __construct(
        string $alignment,
        int $riskScore,
        int $safetyScore,
        string $recommendation,
        string $reasoning,
        array $rawResponse = []
    ) {
        $this->alignment = $alignment;
        $this->riskScore = $riskScore;
        $this->safetyScore = $safetyScore;
        $this->recommendation = $recommendation;
        $this->reasoning = $reasoning;
        $this->rawResponse = $rawResponse;
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['alignment'] ?? 'unknown',
            $data['risk_score'] ?? 50,
            $data['safety_score'] ?? 50,
            $data['recommendation'] ?? 'manual_review',
            $data['reasoning'] ?? 'No analysis available',
            $data
        );
    }

    /**
     * Check if signal should be accepted.
     */
    public function shouldAccept(): bool
    {
        return $this->recommendation === 'accept';
    }

    /**
     * Check if signal should be rejected.
     */
    public function shouldReject(): bool
    {
        return $this->recommendation === 'reject';
    }

    /**
     * Check if position size should be reduced.
     */
    public function shouldSizeDown(): bool
    {
        return $this->recommendation === 'size_down';
    }

    /**
     * Check if manual review is needed.
     */
    public function needsManualReview(): bool
    {
        return $this->recommendation === 'manual_review';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'alignment' => $this->alignment,
            'risk_score' => $this->riskScore,
            'safety_score' => $this->safetyScore,
            'recommendation' => $this->recommendation,
            'reasoning' => $this->reasoning,
        ];
    }
}

