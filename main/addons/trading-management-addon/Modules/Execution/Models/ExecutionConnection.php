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
        // Margin and risk management fields
        'leverage',
        'margin_call_threshold',
        'liquidation_threshold',
        'max_margin_usage_pct',
        'max_open_positions',
        'max_positions_per_symbol',
        // Circuit breaker fields
        'circuit_breaker_enabled',
        'max_consecutive_failures',
        'consecutive_failures',
        'last_failure_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_admin_owned' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'settings' => 'array',
        'margin_call_threshold' => 'decimal:2',
        'liquidation_threshold' => 'decimal:2',
        'max_margin_usage_pct' => 'decimal:2',
        'circuit_breaker_enabled' => 'boolean',
        'consecutive_failures' => 'integer',
        'last_failure_at' => 'datetime',
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

    /**
     * Check if can execute trades
     */
    /**
     * Check if can execute trades
     */
    public function canExecuteTrades(): bool
    {
        if (!$this->is_active || $this->status !== 'active') {
            return false;
        }

        if ($this->circuit_breaker_enabled && $this->consecutive_failures >= $this->max_consecutive_failures) {
            // Check cooldown
            if ($this->last_failure_at && $this->last_failure_at->diffInMinutes(now()) < 15) {
                return false; // Still in cooldown
            }
            // Cooldown expired, reset counter
            $this->update(['consecutive_failures' => 0]);
        }

        return true;
    }

    /**
     * Get type label with legacy compatibility
     */
    public function getTypeAttribute($value)
    {
        // Return existing valid legacy values if present
        if ($value === 'mtapi' || $value === 'ccxt_crypto') {
            return $value;
        }

        // Fallback based on exchange_name (provider equivalent in this model)
        $provider = $this->exchange_name;
        if (in_array($provider, ['metaapi', 'mtapi', 'mtapi_grpc'])) {
            return 'mtapi';
        }

        if (in_array($provider, ['binance', 'coinbase', 'coinbasepro', 'kraken', 'bybit', 'kucoin', 'okx'])) {
            return 'ccxt_crypto';
        }
        
        // Handle mapped values
        if ($value === 'crypto') {
            return 'ccxt_crypto';
        }
        if ($value === 'fx') {
            return 'mtapi';
        }

        return $value ?? 'Unknown';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnectionFactory
    {
        return \Database\Factories\Addons\TradingManagement\Modules\Execution\Models\ExecutionConnectionFactory::new();
    }
}

