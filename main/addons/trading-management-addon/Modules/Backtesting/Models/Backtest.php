<?php

namespace Addons\TradingManagement\Modules\Backtesting\Models;

use App\Models\User;
use App\Models\Admin;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backtest extends Model
{
    use HasFactory;

    protected $table = 'backtests';

    protected $fillable = [
        'user_id',
        'admin_id',
        'name',
        'description',
        'filter_strategy_id',
        'ai_model_profile_id',
        'preset_id',
        'symbol',
        'timeframe',
        'start_date',
        'end_date',
        'initial_balance',
        'status',
        'progress_percent',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'initial_balance' => 'decimal:8',
        'progress_percent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function filterStrategy()
    {
        return $this->belongsTo(FilterStrategy::class, 'filter_strategy_id');
    }

    public function aiModelProfile()
    {
        return $this->belongsTo(AiModelProfile::class, 'ai_model_profile_id');
    }

    public function preset()
    {
        return $this->belongsTo(TradingPreset::class, 'preset_id');
    }

    public function result()
    {
        return $this->hasOne(BacktestResult::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function markAsRunning(): void
    {
        $this->forceFill([
            'status' => 'running',
            'started_at' => now(),
        ])->save();
    }

    public function markAsCompleted(): void
    {
        $this->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percent' => 100,
        ])->save();
    }

    public function markAsFailed(string $error): void
    {
        $this->forceFill([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ])->save();
    }

    public function updateProgress(int $percent): void
    {
        $this->forceFill(['progress_percent' => min($percent, 100)])->save();
    }
}

