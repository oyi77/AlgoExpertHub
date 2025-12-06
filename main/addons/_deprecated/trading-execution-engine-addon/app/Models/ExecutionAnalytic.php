<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionAnalytic extends Model
{
    use HasFactory;

    protected $table = 'execution_analytics';

    protected $fillable = [
        'connection_id',
        'user_id',
        'admin_id',
        'date',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'total_pnl',
        'win_rate',
        'profit_factor',
        'max_drawdown',
        'balance',
        'equity',
        'additional_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'total_trades' => 'integer',
        'winning_trades' => 'integer',
        'losing_trades' => 'integer',
        'total_pnl' => 'decimal:8',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'max_drawdown' => 'decimal:4',
        'balance' => 'decimal:8',
        'equity' => 'decimal:8',
        'additional_metrics' => 'array',
    ];

    public function connection()
    {
        return $this->belongsTo(ExecutionConnection::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeByConnection($query, int $connectionId)
    {
        return $query->where('connection_id', $connectionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}

