<?php

namespace Addons\SmartRiskManagement\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrmModelVersion extends Model
{
    use HasFactory;

    protected $table = 'srm_model_versions';

    protected $fillable = [
        'model_type',
        'version',
        'status',
        'parameters',
        'training_data_count',
        'training_date_start',
        'training_date_end',
        'accuracy',
        'mse',
        'r2_score',
        'validation_accuracy',
        'validation_mse',
        'deployed_at',
        'deprecated_at',
        'notes',
    ];

    protected $casts = [
        'parameters' => 'array',
        'training_date_start' => 'datetime',
        'training_date_end' => 'datetime',
        'deployed_at' => 'datetime',
        'deprecated_at' => 'datetime',
        'accuracy' => 'decimal:2',
        'mse' => 'decimal:6',
        'r2_score' => 'decimal:4',
        'validation_accuracy' => 'decimal:2',
        'validation_mse' => 'decimal:6',
    ];

    /**
     * Get the active model for a type
     */
    public function scopeActive($query, string $modelType)
    {
        return $query->where('model_type', $modelType)
            ->where('status', 'active')
            ->orderBy('deployed_at', 'desc')
            ->first();
    }

    /**
     * Get all models for a type
     */
    public function scopeForType($query, string $modelType)
    {
        return $query->where('model_type', $modelType)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for slippage prediction models
     */
    public function scopeSlippagePrediction($query)
    {
        return $query->where('model_type', 'slippage_prediction');
    }

    /**
     * Scope for performance score models
     */
    public function scopePerformanceScore($query)
    {
        return $query->where('model_type', 'performance_score');
    }

    /**
     * Scope for risk optimization models
     */
    public function scopeRiskOptimization($query)
    {
        return $query->where('model_type', 'risk_optimization');
    }
}

