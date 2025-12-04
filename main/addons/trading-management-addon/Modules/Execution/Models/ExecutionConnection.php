<?php

namespace Addons\TradingManagement\Modules\Execution\Models;

use App\Models\Admin;
use App\Models\User;
use App\Traits\Searchable;
use Addons\TradingManagement\Shared\Traits\HasEncryptedCredentials;
use Addons\TradingManagement\Shared\Traits\ConnectionHealthCheck;
use Addons\TradingManagement\Modules\DataProvider\Models\DataConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\Execution\Models\ExecutionLog;
use Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ExecutionConnection Model
 * 
 * Migrated from trading-execution-engine-addon
 * KEY CHANGE: Now separated from data fetching (uses DataConnection for market data)
 * 
 * Purpose: Execute trades on exchanges/brokers
 * NOT for market data fetching (that's DataConnection)
 */
class ExecutionConnection extends Model
{
    use HasFactory, 
        Searchable, 
        HasEncryptedCredentials, 
        ConnectionHealthCheck;

    protected $table = 'execution_connections';

    public $searchable = ['name', 'exchange_name'];

    protected $fillable = [
        'user_id',
        'admin_id',
        'name',
        'type',
        'exchange_name',
        'credentials',
        'status',
        'is_active',
        'is_admin_owned',
        'last_error',
        'last_tested_at',
        'last_used_at',
        'settings',
        'preset_id',
        'data_connection_id', // NEW: Link to data connection
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_admin_owned' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Relationships
     */
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function preset()
    {
        return $this->belongsTo(TradingPreset::class, 'preset_id');
    }

    public function dataConnection()
    {
        return $this->belongsTo(DataConnection::class, 'data_connection_id');
    }

    public function executionLogs()
    {
        return $this->hasMany(ExecutionLog::class);
    }

    public function positions()
    {
        return $this->hasMany(ExecutionPosition::class);
    }

    /**
     * Scopes
     */
    
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->where('is_admin_owned', false);
    }

    public function scopeAdminOwned($query)
    {
        return $query->where('is_admin_owned', true);
    }

    /**
     * Helper Methods
     */
    
    public function isAdminOwned(): bool
    {
        return $this->is_admin_owned === true;
    }

    public function hasPreset(): bool
    {
        return !is_null($this->preset_id);
    }

    public function hasDataConnection(): bool
    {
        return !is_null($this->data_connection_id);
    }
}

