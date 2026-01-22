<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use Illuminate\Support\Facades\DB;

class DexCopySuitabilityService
{
    /**
     * Weights for different factors in the copy suitability score
     */
    public const WEIGHTS = [
        'win_rate' => 0.25,           // 25% weight
        'profit_factor' => 0.20,      // 20% weight
        'sharpe_ratio' => 0.15,       // 15% weight
        'max_drawdown' => 0.15,       // 15% weight (inverse - lower is better)
        'consistency' => 0.10,        // 10% weight
        'trading_age' => 0.08,        // 8% weight
        'liquidation_rate' => 0.07,   // 7% weight (inverse - lower is better)
    ];

    /**
     * Thresholds for excellent ratings
     */
    public const THRESHOLDS = [
        'win_rate' => 65,             // 65%+ win rate
        'profit_factor' => 2.0,       // 2.0+ profit factor
        'sharpe_ratio' => 1.5,        // 1.5+ sharpe ratio
        'max_drawdown' => 15,         // <15% max drawdown
        'consistency' => 70,          // 70%+ consistency score
        'liquidation_rate' => 5,      // <5% liquidation rate
    ];

    /**
     * Calculate the overall copy suitability score for a trader
     */
    public function calculateCopySuitabilityScore(int $watchlistId): array
    {
        $metrics = DB::table('dex_analytics_cache')
            ->where('watchlist_id', $watchlistId)
            ->whereIn('metric_key', [
                'win_rate',
                'profit_factor',
                'sharpe_ratio',
                'max_drawdown',
                'consistency_score',
                'liquidation_rate',
            ])
            ->get();

        $metricMap = [];
        foreach ($metrics as $metric) {
            $metricMap[$metric->metric_key] = json_decode($metric->metric_value, true);
        }

        $scores = [
            'win_rate_score' => $this->scoreWinRate($metricMap['win_rate'] ?? 0),
            'profit_factor_score' => $this->scoreProfitFactor($metricMap['profit_factor'] ?? 0),
            'sharpe_score' => $this->scoreSharpeRatio($metricMap['sharpe_ratio'] ?? 0),
            'drawdown_score' => $this->scoreMaxDrawdown($metricMap['max_drawdown'] ?? 0),
            'consistency_score' => $this->scoreConsistency($metricMap['consistency_score'] ?? 0),
            'liquidation_score' => $this->scoreLiquidationRate($metricMap['liquidation_rate'] ?? 0),
        ];

        $overallScore = $this->calculateWeightedScore($scores);

        $rating = $this->getRating($overallScore);
        $recommendation = $this->getRecommendation($rating, $scores);

        return [
            'overall_score' => round($overallScore, 1),
            'rating' => $rating,
            'recommendation' => $recommendation,
            'component_scores' => $scores,
            'is_suitable_for_copying' => $overallScore >= 50,
            'risk_level' => $this->getRiskLevel($scores),
            'strengths' => $this->identifyStrengths($scores),
            'weaknesses' => $this->identifyWeaknesses($scores),
        ];
    }

    /**
     * Calculate weighted overall score
     */
    protected function calculateWeightedScore(array $scores): float
    {
        return (
            ($scores['win_rate_score'] * self::WEIGHTS['win_rate']) +
            ($scores['profit_factor_score'] * self::WEIGHTS['profit_factor']) +
            ($scores['sharpe_score'] * self::WEIGHTS['sharpe_ratio']) +
            ($scores['drawdown_score'] * self::WEIGHTS['max_drawdown']) +
            ($scores['consistency_score'] * self::WEIGHTS['consistency']) +
            ($scores['liquidation_score'] * self::WEIGHTS['liquidation_rate'])
        );
    }

    /**
     * Score win rate (0-100)
     */
    public function scoreWinRate(float $winRate): float
    {
        if ($winRate >= self::THRESHOLDS['win_rate']) {
            return 100.0;
        }
        if ($winRate >= 50) {
            return 50 + ($winRate - 50) * 2; // Scale 50-65 to 50-100
        }
        if ($winRate >= 30) {
            return 30 + ($winRate - 30) * 1; // Scale 30-50 to 30-50
        }

        return max(0, $winRate);
    }

    /**
     * Score profit factor (0-100)
     */
    public function scoreProfitFactor(float $profitFactor): float
    {
        if ($profitFactor >= self::THRESHOLDS['profit_factor']) {
            return 100.0;
        }
        if ($profitFactor >= 1.0) {
            return 50 + ($profitFactor - 1.0) * 50; // Scale 1.0-2.0 to 50-100
        }
        if ($profitFactor >= 0.5) {
            return 25 + ($profitFactor - 0.5) * 50; // Scale 0.5-1.0 to 25-50
        }

        return max(0, $profitFactor * 50);
    }

    /**
     * Score Sharpe ratio (0-100)
     */
    public function scoreSharpeRatio(float $sharpeRatio): float
    {
        if ($sharpeRatio >= self::THRESHOLDS['sharpe_ratio']) {
            return 100.0;
        }
        if ($sharpeRatio >= 0.5) {
            return 50 + ($sharpeRatio - 0.5) * 50; // Scale 0.5-1.5 to 50-100
        }
        if ($sharpeRatio >= 0) {
            return $sharpeRatio * 100; // Scale 0-0.5 to 0-50
        }

        return max(0, 50 + $sharpeRatio * 20); // Negative sharpe gets some credit but low
    }

    /**
     * Score max drawdown (0-100, lower is better)
     */
    public function scoreMaxDrawdown(float $maxDrawdown): float
    {
        if ($maxDrawdown <= 0) {
            return 100.0;
        }
        if ($maxDrawdown <= self::THRESHOLDS['max_drawdown']) {
            return 100 - ($maxDrawdown / self::THRESHOLDS['max_drawdown']) * 20;
        }
        if ($maxDrawdown <= 30) {
            return 80 - (($maxDrawdown - 15) / 15) * 30;
        }
        if ($maxDrawdown <= 50) {
            return 50 - (($maxDrawdown - 30) / 20) * 30;
        }

        return max(0, 20 - ($maxDrawdown - 50) / 50 * 20);
    }

    /**
     * Score consistency (0-100)
     */
    public function scoreConsistency(float $consistency): float
    {
        return min(100, $consistency);
    }

    /**
     * Score liquidation rate (0-100, lower is better)
     */
    public function scoreLiquidationRate(float $liquidationRate): float
    {
        if ($liquidationRate <= self::THRESHOLDS['liquidation_rate']) {
            return 100.0;
        }
        if ($liquidationRate <= 10) {
            return 100 - ($liquidationRate - 5) * 10;
        }
        if ($liquidationRate <= 25) {
            return 50 - (($liquidationRate - 10) / 15) * 30;
        }

        return max(0, 20 - ($liquidationRate - 25) / 25 * 20);
    }

    /**
     * Get rating based on overall score
     */
    public function getRating(float $score): string
    {
        if ($score >= 80) {
            return 'A+';
        }
        if ($score >= 70) {
            return 'A';
        }
        if ($score >= 60) {
            return 'B+';
        }
        if ($score >= 50) {
            return 'B';
        }
        if ($score >= 40) {
            return 'C+';
        }
        if ($score >= 30) {
            return 'C';
        }
        if ($score >= 20) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Get human-readable rating description
     */
    public function getRatingDescription(string $rating): string
    {
        return match ($rating) {
            'A+' => 'Excellent - Highly recommended for copying',
            'A' => 'Very Good - Strong candidate for copying',
            'B+' => 'Good - Suitable for copying with monitoring',
            'B' => 'Above Average - Consider copying',
            'C+' => 'Fair - Higher risk, proceed with caution',
            'C' => 'Below Average - Not recommended for copying',
            'D' => 'Poor - High risk, avoid copying',
            'F' => 'Failing - Do not copy',
            default => 'Unknown',
        };
    }

    /**
     * Get recommendation text
     */
    public function getRecommendation(string $rating, array $scores): string
    {
        if ($rating === 'A+' || $rating === 'A') {
            return 'This trader demonstrates excellent performance metrics and is suitable for copy trading.';
        }
        if ($rating === 'B+' || $rating === 'B') {
            return 'This trader shows good performance. Consider copying with position limits.';
        }
        if ($rating === 'C+' || $rating === 'C') {
            return 'This trader has mixed results. Use with caution and small allocation.';
        }

        return 'This trader shows poor metrics. Not recommended for copy trading.';
    }

    /**
     * Get risk level based on component scores
     */
    public function getRiskLevel(array $scores): string
    {
        $drawdownScore = $scores['drawdown_score'];
        $liquidationScore = $scores['liquidation_score'];

        if ($drawdownScore >= 80 && $liquidationScore >= 80) {
            return 'low';
        }
        if ($drawdownScore >= 50 && $liquidationScore >= 50) {
            return 'medium';
        }
        if ($drawdownScore >= 30 || $liquidationScore >= 30) {
            return 'high';
        }

        return 'very_high';
    }

    /**
     * Identify trader strengths (scores >= 70)
     */
    public function identifyStrengths(array $scores): array
    {
        $strengths = [];

        if ($scores['win_rate_score'] >= 70) {
            $strengths[] = 'High win rate';
        }
        if ($scores['profit_factor_score'] >= 70) {
            $strengths[] = 'Strong profit factor';
        }
        if ($scores['sharpe_score'] >= 70) {
            $strengths[] = 'Excellent risk-adjusted returns';
        }
        if ($scores['drawdown_score'] >= 70) {
            $strengths[] = 'Low drawdowns';
        }
        if ($scores['consistency_score'] >= 70) {
            $strengths[] = 'Consistent performance';
        }
        if ($scores['liquidation_score'] >= 70) {
            $strengths[] = 'Low liquidation rate';
        }

        return $strengths;
    }

    /**
     * Identify trader weaknesses (scores < 50)
     */
    public function identifyWeaknesses(array $scores): array
    {
        $weaknesses = [];

        if ($scores['win_rate_score'] < 50) {
            $weaknesses[] = 'Low win rate';
        }
        if ($scores['profit_factor_score'] < 50) {
            $weaknesses[] = 'Weak profit factor';
        }
        if ($scores['sharpe_score'] < 50) {
            $weaknesses[] = 'Poor risk-adjusted returns';
        }
        if ($scores['drawdown_score'] < 50) {
            $weaknesses[] = 'High drawdowns';
        }
        if ($scores['consistency_score'] < 50) {
            $weaknesses[] = 'Inconsistent performance';
        }
        if ($scores['liquidation_score'] < 50) {
            $weaknesses[] = 'High liquidation rate';
        }

        return $weaknesses;
    }

    /**
     * Get recommended allocation percentage based on score
     */
    public function getRecommendedAllocation(float $overallScore): float
    {
        if ($overallScore >= 80) {
            return 10.0; // 10% of portfolio
        }
        if ($overallScore >= 70) {
            return 7.5; // 7.5%
        }
        if ($overallScore >= 60) {
            return 5.0; // 5%
        }
        if ($overallScore >= 50) {
            return 3.0; // 3%
        }
        if ($overallScore >= 40) {
            return 2.0; // 2%
        }
        if ($overallScore >= 30) {
            return 1.0; // 1%
        }

        return 0.0; // Not recommended
    }

    /**
     * Calculate copy suitability for all watched traders
     */
    public function calculateAllScores(): array
    {
        $watchlists = DB::table('dex_trader_watchlist')
            ->where('is_active', true)
            ->get(['id', 'wallet_address', 'platform']);

        $results = [];

        foreach ($watchlists as $watchlist) {
            $scores = $this->calculateCopySuitabilityScore((int) $watchlist->id);
            $results[] = [
                'watchlist_id' => $watchlist->id,
                'wallet_address' => $watchlist->wallet_address,
                'platform' => $watchlist->platform,
                'overall_score' => $scores['overall_score'],
                'rating' => $scores['rating'],
                'is_suitable' => $scores['is_suitable_for_copying'],
                'risk_level' => $scores['risk_level'],
            ];
        }

        // Sort by overall score descending
        usort($results, fn ($a, $b) => $b['overall_score'] <=> $a['overall_score']);

        return $results;
    }
}
