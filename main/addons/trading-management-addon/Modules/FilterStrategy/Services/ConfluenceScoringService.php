<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Services;

use Illuminate\Support\Facades\Log;

/**
 * Confluence Scoring Service
 * 
 * Calculates confluence score from multiple filter strategy results
 * Higher confluence = stronger signal
 */
class ConfluenceScoringService
{
    /**
     * Calculate confluence score from multiple filter strategy results
     * 
     * @param array $filterResults Array of filter evaluation results
     * @param array|null $weights Optional custom weights for each filter (default: equal weights)
     * @return array ['score' => float, 'confidence' => float, 'breakdown' => array, 'passed_filters' => int, 'total_filters' => int]
     */
    public function calculateConfluenceScore(array $filterResults, ?array $weights = null): array
    {
        if (empty($filterResults)) {
            return [
                'score' => 0,
                'confidence' => 0,
                'breakdown' => [],
                'passed_filters' => 0,
                'total_filters' => 0,
                'reason' => 'No filter results provided',
            ];
        }

        $totalFilters = count($filterResults);
        $passedFilters = 0;
        $score = 0;
        $totalWeight = 0;
        $breakdown = [];

        // Default weights: equal weight for all filters
        if ($weights === null) {
            $weights = array_fill(0, $totalFilters, 1.0 / $totalFilters);
        }

        // Normalize weights to sum to 1.0
        $weightSum = array_sum($weights);
        if ($weightSum > 0) {
            $weights = array_map(fn($w) => $w / $weightSum, $weights);
        }

        // Calculate score for each filter
        foreach ($filterResults as $index => $result) {
            $weight = $weights[$index] ?? (1.0 / $totalFilters);
            $passed = $this->isFilterPassed($result);
            $filterScore = $this->calculateFilterScore($result);
            
            if ($passed) {
                $passedFilters++;
                $score += $weight * $filterScore;
            }
            
            $totalWeight += $weight;
            
            $breakdown[] = [
                'filter_index' => $index,
                'filter_name' => $result['filter_name'] ?? "Filter " . ($index + 1),
                'passed' => $passed,
                'score' => $filterScore,
                'weight' => $weight,
                'weighted_score' => $passed ? $weight * $filterScore : 0,
                'reason' => $result['reason'] ?? ($passed ? 'Passed' : 'Failed'),
            ];
        }

        // Normalize score to 0-100 range
        $normalizedScore = $totalWeight > 0 ? ($score / $totalWeight) * 100 : 0;
        
        // Calculate confidence based on passed filters and score
        $confidence = $this->calculateConfidence($passedFilters, $totalFilters, $normalizedScore);

        return [
            'score' => round($normalizedScore, 2),
            'confidence' => round($confidence, 2),
            'breakdown' => $breakdown,
            'passed_filters' => $passedFilters,
            'total_filters' => $totalFilters,
            'pass_rate' => $totalFilters > 0 ? round(($passedFilters / $totalFilters) * 100, 2) : 0,
        ];
    }

    /**
     * Check if a filter result indicates a pass
     */
    protected function isFilterPassed(array $result): bool
    {
        // Check various result formats
        if (isset($result['pass'])) {
            return (bool) $result['pass'];
        }
        
        if (isset($result['passed'])) {
            return (bool) $result['passed'];
        }
        
        if (isset($result['should_enter'])) {
            return (bool) $result['should_enter'];
        }
        
        if (isset($result['signal']) && in_array(strtolower($result['signal']), ['buy', 'sell'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Calculate individual filter score (0-100)
     */
    protected function calculateFilterScore(array $result): float
    {
        // If filter has a strength/confidence score, use it
        if (isset($result['strength'])) {
            return (float) $result['strength'] * 100;
        }
        
        if (isset($result['confidence'])) {
            return (float) $result['confidence'] * 100;
        }
        
        if (isset($result['score'])) {
            $score = (float) $result['score'];
            // If score is already 0-100, return as-is; otherwise normalize
            return $score > 1 ? $score : $score * 100;
        }
        
        // Default: passed filter = 100, failed = 0
        return $this->isFilterPassed($result) ? 100.0 : 0.0;
    }

    /**
     * Calculate overall confidence from confluence
     */
    protected function calculateConfidence(int $passedFilters, int $totalFilters, float $score): float
    {
        if ($totalFilters === 0) {
            return 0;
        }
        
        // Base confidence from pass rate
        $passRate = $passedFilters / $totalFilters;
        
        // Adjust confidence based on score
        // Higher score = higher confidence
        $scoreFactor = $score / 100;
        
        // Combined confidence: weighted average of pass rate and score
        $confidence = ($passRate * 0.6) + ($scoreFactor * 0.4);
        
        // Boost confidence if all filters passed
        if ($passedFilters === $totalFilters) {
            $confidence = min(1.0, $confidence * 1.1);
        }
        
        // Reduce confidence if less than 50% passed
        if ($passedFilters < ($totalFilters / 2)) {
            $confidence = $confidence * 0.7;
        }
        
        return min(1.0, max(0.0, $confidence));
    }

    /**
     * Get recommended action based on confluence score
     * 
     * @param array $confluenceResult Result from calculateConfluenceScore
     * @return array ['action' => 'strong_buy|buy|hold|sell|strong_sell', 'reason' => string]
     */
    public function getRecommendedAction(array $confluenceResult): array
    {
        $score = $confluenceResult['score'] ?? 0;
        $confidence = $confluenceResult['confidence'] ?? 0;
        $passRate = $confluenceResult['pass_rate'] ?? 0;
        
        // Strong buy: high score, high confidence, most filters passed
        if ($score >= 80 && $confidence >= 0.8 && $passRate >= 80) {
            return [
                'action' => 'strong_buy',
                'reason' => "High confluence: {$score}% score, {$confidence}% confidence, {$passRate}% filters passed",
            ];
        }
        
        // Buy: good score and confidence
        if ($score >= 60 && $confidence >= 0.6) {
            return [
                'action' => 'buy',
                'reason' => "Good confluence: {$score}% score, {$confidence}% confidence",
            ];
        }
        
        // Hold: moderate confluence
        if ($score >= 40 && $confidence >= 0.4) {
            return [
                'action' => 'hold',
                'reason' => "Moderate confluence: {$score}% score, {$confidence}% confidence",
            ];
        }
        
        // Sell: low confluence
        if ($score < 40 || $confidence < 0.4) {
            return [
                'action' => 'sell',
                'reason' => "Low confluence: {$score}% score, {$confidence}% confidence",
            ];
        }
        
        return [
            'action' => 'hold',
            'reason' => 'Insufficient data for recommendation',
        ];
    }

    /**
     * Calculate weighted confluence with priority-based weights
     * 
     * @param array $filterResults
     * @param array $priorities Array of priority levels (1-5, higher = more important)
     * @return array
     */
    public function calculateWeightedConfluence(array $filterResults, array $priorities): array
    {
        if (count($filterResults) !== count($priorities)) {
            Log::warning('ConfluenceScoringService: Mismatch between filter results and priorities', [
                'results_count' => count($filterResults),
                'priorities_count' => count($priorities),
            ]);
            // Use equal weights as fallback
            return $this->calculateConfluenceScore($filterResults);
        }
        
        // Convert priorities to weights (higher priority = higher weight)
        $maxPriority = max($priorities);
        $weights = array_map(function ($priority) use ($maxPriority) {
            return $priority / $maxPriority;
        }, $priorities);
        
        return $this->calculateConfluenceScore($filterResults, $weights);
    }
}

