<?php

namespace Addons\TradingManagement\Modules\CopyTrading\Models;

use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Illuminate\Database\Eloquent\Model;

class CopyTradingExecution extends Model
{
    protected $table = 'copy_trading_executions';

    protected $fillable = [
        'subscription_id',
        'trader_execution_log_id',
        'follower_execution_log_id',
        'original_lot_size',
        'copied_lot_size',
        'multiplier_applied',
        'status',
        'error_message',
    ];

    protected $casts = [
        'original_lot_size' => 'decimal:8',
        'copied_lot_size' => 'decimal:8',
        'multiplier_applied' => 'decimal:4',
    ];

    public function subscription()
    {
        return $this->belongsTo(CopyTradingSubscription::class, 'subscription_id');
    }

    public function traderExecution()
    {
        return $this->belongsTo(ExecutionLog::class, 'trader_execution_log_id');
    }

    public function followerExecution()
    {
        return $this->belongsTo(ExecutionLog::class, 'follower_execution_log_id');
    }
}

