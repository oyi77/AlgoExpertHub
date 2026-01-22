import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../../ui/Card';
import { Button } from '../../ui/Button';

// Risk metric badge component
const RiskBadge = ({ score, label }) => {
    const getColor = (s) => {
        if (s >= 80) return 'bg-[#0ecb81]/20 text-[#0ecb81]';
        if (s >= 60) return 'bg-[#3b82f6]/20 text-[#3b82f6]';
        if (s >= 40) return 'bg-[#ffd700]/20 text-[#ffd700]';
        return 'bg-[#f6465d]/20 text-[#f6465d]';
    };

    return (
        <span className={`px-2 py-1 rounded text-xs font-medium ${getColor(score)}`}>
            {score}
        </span>
    );
};

// Score progress bar
const ScoreProgressBar = ({ score, maxScore = 100, label }) => {
    const percentage = Math.min((score / maxScore) * 100, 100);
    const getColor = (p) => {
        if (p >= 80) return 'bg-[#0ecb81]';
        if (p >= 60) return 'bg-[#3b82f6]';
        if (p >= 40) return 'bg-[#ffd700]';
        return 'bg-[#f6465d]';
    };

    return (
        <div className="mb-3">
            <div className="flex justify-between text-sm mb-1">
                <span className="text-[#848e9c]">{label}</span>
                <span className="text-[#eaecef]">{score.toFixed(1)}</span>
            </div>
            <div className="h-2 bg-[#2b3139] rounded-full overflow-hidden">
                <div
                    className={`h-full rounded-full transition-all ${getColor(percentage)}`}
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
};

// Copy Suitability Card
export const CopySuitabilityCard = ({ metrics }) => {
    if (!metrics) return null;

    const {
        overall_score,
        rating,
        recommendation,
        component_scores,
        risk_level,
        strengths,
        weaknesses,
        is_suitable_for_copying,
    } = metrics;

    const getRiskColor = (level) => {
        switch (level) {
            case 'low': return 'text-[#0ecb81]';
            case 'medium': return 'text-[#3b82f6]';
            case 'high': return 'text-[#ffd700]';
            default: return 'text-[#f6465d]';
        }
    };

    return (
        <Card>
            <CardHeader className="border-b border-[#2b3139]">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-[#eaecef]">Copy Suitability Analysis</CardTitle>
                    <div className="flex items-center space-x-2">
                        <span className="text-sm text-[#848e9c]">Rating:</span>
                        <span className={`text-xl font-bold ${
                            overall_score >= 80 ? 'text-[#0ecb81]' :
                            overall_score >= 60 ? 'text-[#3b82f6]' :
                            overall_score >= 40 ? 'text-[#ffd700]' :
                            'text-[#f6465d]'
                        }`}>
                            {rating}
                        </span>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="p-6">
                {/* Overall Score */}
                <div className="mb-6">
                    <div className="flex items-center justify-center mb-4">
                        <div className="relative w-32 h-32">
                            <svg className="w-full h-full transform -rotate-90">
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    fill="none"
                                    stroke="#2b3139"
                                    strokeWidth="12"
                                />
                                <circle
                                    cx="64"
                                    cy="64"
                                    r="56"
                                    fill="none"
                                    stroke={overall_score >= 80 ? '#0ecb81' : overall_score >= 60 ? '#3b82f6' : overall_score >= 40 ? '#ffd700' : '#f6465d'}
                                    strokeWidth="12"
                                    strokeDasharray={`${(overall_score / 100) * 352} 352`}
                                    strokeLinecap="round"
                                />
                            </svg>
                            <div className="absolute inset-0 flex items-center justify-center">
                                <span className="text-3xl font-bold text-[#eaecef]">{overall_score}</span>
                            </div>
                        </div>
                    </div>
                    <p className="text-center text-[#848e9c] text-sm">{recommendation}</p>
                </div>

                {/* Component Scores */}
                <div className="mb-6">
                    <h4 className="text-sm font-medium text-[#eaecef] mb-4">Component Scores</h4>
                    <ScoreProgressBar score={component_scores.win_rate_score} label="Win Rate" />
                    <ScoreProgressBar score={component_scores.profit_factor_score} label="Profit Factor" />
                    <ScoreProgressBar score={component_scores.sharpe_score} label="Sharpe Ratio" />
                    <ScoreProgressBar score={component_scores.drawdown_score} label="Max Drawdown (inverse)" />
                    <ScoreProgressBar score={component_scores.consistency_score} label="Consistency" />
                    <ScoreProgressBar score={component_scores.liquidation_score} label="Liquidation Rate (inverse)" />
                </div>

                {/* Risk Level */}
                <div className="flex items-center justify-between p-4 bg-[#2b3139]/50 rounded-lg mb-6">
                    <span className="text-[#848e9c]">Risk Level</span>
                    <span className={`font-medium capitalize ${getRiskColor(risk_level)}`}>
                        {risk_level.replace('_', ' ')}
                    </span>
                </div>

                {/* Strengths & Weaknesses */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <h5 className="text-sm font-medium text-[#0ecb81] mb-2">Strengths</h5>
                        {strengths.length > 0 ? (
                            <ul className="space-y-1">
                                {strengths.map((s, i) => (
                                    <li key={i} className="text-sm text-[#848e9c] flex items-center">
                                        <span className="w-1.5 h-1.5 bg-[#0ecb81] rounded-full mr-2" />
                                        {s}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-[#848e9c]">No significant strengths identified</p>
                        )}
                    </div>
                    <div>
                        <h5 className="text-sm font-medium text-[#f6465d] mb-2">Weaknesses</h5>
                        {weaknesses.length > 0 ? (
                            <ul className="space-y-1">
                                {weaknesses.map((w, i) => (
                                    <li key={i} className="text-sm text-[#848e9c] flex items-center">
                                        <span className="w-1.5 h-1.5 bg-[#f6465d] rounded-full mr-2" />
                                        {w}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-[#848e9c]">No significant weaknesses identified</p>
                        )}
                    </div>
                </div>

                {/* Action */}
                <div className="mt-6 pt-4 border-t border-[#2b3139]">
                    {is_suitable_for_copying ? (
                        <Button className="w-full" variant="success">
                            <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Suitable for Copy Trading
                        </Button>
                    ) : (
                        <Button className="w-full" variant="danger" disabled>
                            <svg className="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Not Recommended for Copy Trading
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
};

// Trader Classification Card
export const TraderClassificationCard = ({ metrics }) => {
    if (!metrics) return null;

    const { pnl_category, wallet_tier, consistency_score, trading_frequency } = metrics;

    const computationService = {
        getPnlCategoryLabel: (cat) => {
            const labels = {
                extremely_profitable: 'Extremely Profitable (+$1M+)',
                highly_profitable: 'Highly Profitable (+$100K+)',
                profitable: 'Profitable (+$10K+)',
                marginally_profitable: 'Marginally Profitable (+$1K+)',
                break_even: 'Break Even',
                marginally_rekt: 'Marginally Rekt (-$1K)',
                rekt: 'Rekt (-$10K)',
                heavily_rekt: 'Heavily Rekt (-$100K)',
                completely_rekt: 'Completely Rekt (-$1M+)',
            };
            return labels[cat] || cat;
        },
        getWalletTierLabel: (tier) => {
            const labels = {
                kraken: 'Kraken ($5M+)',
                whale: 'Whale ($1M-$5M)',
                large_whale: 'Large Whale ($500K-$1M)',
                shark: 'Shark ($100K-$500K)',
                dolphin: 'Dolphin ($50K-$100K)',
                large_fish: 'Large Fish ($10K-$50K)',
                fish: 'Fish ($250-$10K)',
                shrimp: 'Shrimp ($0-$250)',
            };
            return labels[tier] || tier;
        },
    };

    const getPnlColor = (cat) => {
        if (cat.includes('profitable')) return 'text-[#0ecb81]';
        if (cat.includes('Rekt')) return 'text-[#f6465d]';
        return 'text-[#ffd700]';
    };

    return (
        <Card>
            <CardHeader className="border-b border-[#2b3139]">
                <CardTitle className="text-[#eaecef]">Trader Classification</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
                <div className="grid grid-cols-2 gap-4 mb-6">
                    {/* PNL Category */}
                    <div className="p-4 bg-[#2b3139]/50 rounded-lg text-center">
                        <p className="text-sm text-[#848e9c] mb-2">Performance Tier</p>
                        <p className={`text-lg font-bold ${getPnlColor(pnl_category)}`}>
                            {computationService.getPnlCategoryLabel(pnl_category)}
                        </p>
                    </div>

                    {/* Wallet Tier */}
                    <div className="p-4 bg-[#2b3139]/50 rounded-lg text-center">
                        <p className="text-sm text-[#848e9c] mb-2">Wallet Size</p>
                        <p className="text-lg font-bold text-[#3b82f6]">
                            {computationService.getWalletTierLabel(wallet_tier)}
                        </p>
                    </div>
                </div>

                {/* Consistency Score */}
                <div className="mb-4">
                    <div className="flex justify-between text-sm mb-2">
                        <span className="text-[#848e9c]">Consistency Score</span>
                        <span className="text-[#eaecef]">{consistency_score}%</span>
                    </div>
                    <div className="h-3 bg-[#2b3139] rounded-full overflow-hidden">
                        <div
                            className={`h-full rounded-full ${
                                consistency_score >= 70 ? 'bg-[#0ecb81]' :
                                consistency_score >= 50 ? 'bg-[#3b82f6]' :
                                consistency_score >= 30 ? 'bg-[#ffd700]' :
                                'bg-[#f6465d]'
                            }`}
                            style={{ width: `${consistency_score}%` }}
                        />
                    </div>
                </div>

                {/* Trading Frequency */}
                <div className="flex items-center justify-between p-3 bg-[#2b3139]/30 rounded-lg">
                    <span className="text-sm text-[#848e9c]">Trading Frequency</span>
                    <span className="text-sm font-medium text-[#eaecef]">
                        {trading_frequency} trades/day
                    </span>
                </div>
            </CardContent>
        </Card>
    );
};

// Advanced Metrics Card
export const AdvancedMetricsCard = ({ metrics }) => {
    if (!metrics) return null;

    const {
        sharpe_ratio,
        calmar_ratio,
        sortino_ratio,
        avg_winning_trade,
        avg_losing_trade,
        win_loss_ratio,
        avg_holding_time,
    } = metrics;

    const formatTime = (seconds) => {
        if (seconds < 60) return `${seconds}s`;
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
        return `${Math.floor(seconds / 86400)}d`;
    };

    const MetricRow = ({ label, value, subValue, inverse = false }) => (
        <div className="flex items-center justify-between py-3 border-b border-[#2b3139] last:border-0">
            <div>
                <p className="text-sm text-[#848e9c]">{label}</p>
                {subValue && <p className="text-xs text-[#6b7280]">{subValue}</p>}
            </div>
            <span className={`font-mono font-medium ${
                inverse
                    ? (value >= 0 ? 'text-[#0ecb81]' : 'text-[#f6465d]')
                    : 'text-[#eaecef]'
            }`}>
                {typeof value === 'number' ? value.toFixed(2) : value}
            </span>
        </div>
    );

    return (
        <Card>
            <CardHeader className="border-b border-[#2b3139]">
                <CardTitle className="text-[#eaecef]">Advanced Metrics</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                <div className="p-4">
                    <MetricRow
                        label="Sharpe Ratio"
                        value={sharpe_ratio}
                        subValue="Risk-adjusted return"
                        inverse={sharpe_ratio < 0}
                    />
                    <MetricRow
                        label="Calmar Ratio"
                        value={calmar_ratio}
                        subValue="Return / Max Drawdown"
                    />
                    <MetricRow
                        label="Sortino Ratio"
                        value={sortino_ratio}
                        subValue="Downside risk-adjusted"
                    />
                    <MetricRow
                        label="Win/Loss Ratio"
                        value={win_loss_ratio}
                        subValue={`Avg Win: $${avg_winning_trade} | Avg Loss: $${avg_losing_trade}`}
                    />
                    <MetricRow
                        label="Avg Holding Time"
                        value={formatTime(avg_holding_time)}
                        subValue="Average trade duration"
                    />
                </div>
            </CardContent>
        </Card>
    );
};

export default {
    CopySuitabilityCard,
    TraderClassificationCard,
    AdvancedMetricsCard,
};
