<?php

namespace Addons\SmartRiskManagement\App\Models;

use App\Models\Signal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrmPrediction extends Model
{
    use HasFactory;

    protected $table = 'srm_predictions';

    protected $fillable = [
        'execution_log_id',
        'signal_id',
        'connection_id',
        'prediction_type',
        'symbol',
        'trading_session',
        'day_of_week',
        'market_atr',
        'volatility_index',
        'signal_provider_id',
        'predicted_value',
        'confidence_score',
        'actual_value',
        'accuracy',
        'model_version',
        'model_type',
    ];

    protected $casts = [
        'market_atr' => 'decimal:4',
        'volatility_index' => 'decimal:4',
        'predicted_value' => 'decimal:4',
        'confidence_score' => 'decimal:2',
        'actual_value' => 'decimal:4',
        'accuracy' => 'decimal:2',
    ];

    /**
     * Get the execution log that this prediction is for
     */
    public function executionLog()
    {
        if (class_exists(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class)) {
            return $this->belongsTo(\Addons\TradingExecutionEngine\App\Models\ExecutionLog::class, 'execution_log_id');
        }
        return null;
    }

    /**
     * Get the signal that this prediction is for
     */
    public function signal()
    {
        return $this->belongsTo(Signal::class, 'signal_id');
    }

    /**
     * Scope for slippage predictions
     */
    public function scopeSlippage($query)
    {
        return $query->where('prediction_type', 'slippage');
    }

    /**
     * Scope for performance score predictions
     */
    public function scopePerformanceScore($query)
    {
        return $query->where('prediction_type', 'performance_score');
    }

    /**
     * Scope for lot optimization predictions
     */
    public function scopeLotOptimization($query)
    {
        return $query->where('prediction_type', 'lot_optimization');
    }

    /**
     * Scope for predictions with actual values (for accuracy calculation)
     */
    public function scopeWithActual($query)
    {
        return $query->whereNotNull('actual_value');
    }
}

