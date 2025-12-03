<?php

namespace Addons\SmartRiskManagement\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalProviderMetrics extends Model
{
    use HasFactory;

    protected $table = 'srm_signal_provider_metrics';

    protected $fillable = [
        'signal_provider_id',
        'signal_provider_type',
        'period_start',
        'period_end',
        'period_type',
        'total_signals',
        'winning_signals',
        'losing_signals',
        'win_rate',
        'avg_slippage',
        'max_slippage',
        'avg_latency_ms',
        'max_drawdown',
        'reward_to_risk_ratio',
        'sl_compliance_rate',
        'performance_score',
        'performance_score_previous',
        'score_trend',
        'calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'win_rate' => 'decimal:2',
        'avg_slippage' => 'decimal:4',
        'max_slippage' => 'decimal:4',
        'max_drawdown' => 'decimal:2',
        'reward_to_risk_ratio' => 'decimal:4',
        'sl_compliance_rate' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'performance_score_previous' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the latest metrics for a signal provider
     */
    public function scopeLatestForProvider($query, string $providerId, string $providerType, string $periodType = 'daily')
    {
        return $query->where('signal_provider_id', $providerId)
            ->where('signal_provider_type', $providerType)
            ->where('period_type', $periodType)
            ->orderBy('period_end', 'desc')
            ->first();
    }

    /**
     * Get metrics for a date range
     */
    public function scopeForPeriod($query, string $providerId, string $providerType, $startDate, $endDate, string $periodType = 'daily')
    {
        return $query->where('signal_provider_id', $providerId)
            ->where('signal_provider_type', $providerType)
            ->where('period_type', $periodType)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start', 'asc');
    }
}

