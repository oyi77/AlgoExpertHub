<?php

namespace Addons\TradingManagement\Modules\TradingBot\Models;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * TradingBotExecutionLog Model
 * 
 * Tracks all lifecycle actions (start, stop, pause, resume) for audit trail
 */
class TradingBotExecutionLog extends Model
{
    use HasFactory;

    protected $table = 'trading_bot_execution_logs';

    protected $fillable = [
        'bot_id',
        'action',
        'executed_at',
        'executed_by_user_id',
        'executed_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function bot()
    {
        return $this->belongsTo(TradingBot::class, 'bot_id');
    }

    public function executedByUser()
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function executedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'executed_by_admin_id');
    }

    /**
     * Scopes
     */
    
    public function scopeForBot($query, $botId)
    {
        return $query->where('bot_id', $botId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
