<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Models;

use App\Models\Admin;
use App\Models\User;
use App\Traits\Searchable;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Shared\Traits\HasEncryptedCredentials;
use Addons\TradingManagement\Shared\Traits\ConnectionHealthCheck;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified Exchange Connection
 * 
 * Single connection for both data fetching AND trade execution
 * No more duplication!
 */
class ExchangeConnection extends Model
{
    use HasFactory, Searchable, HasEncryptedCredentials, ConnectionHealthCheck;

    protected $table = 'execution_connections';

    public $searchable = ['name', 'exchange_name'];

    protected $fillable = [
        'user_id',
        'admin_id',
        'is_admin_owned',
        'name',
        'type', // enum: 'crypto', 'fx' (legacy)
        'connection_type', // enum: 'CRYPTO_EXCHANGE', 'FX_BROKER' (new)
        'exchange_name', // provider name (legacy)
        'provider', // provider name (new: metaapi, binance, etc.)
        'credentials',
        'status', // enum: 'active', 'inactive', 'error', 'testing'
        'is_active',
        'data_fetching_enabled',
        'trade_execution_enabled',
        'copy_trading_enabled',
        'last_error',
        'last_tested_at',
        'last_used_at',
        'last_data_fetch_at',
        'last_trade_execution_at',
        'preset_id',
        'settings', // JSON field for position sizing, risk limits, etc. (legacy)
        'data_settings', // JSON field for data fetching settings
        'execution_settings', // JSON field for execution settings
    ];

    protected $casts = [
        'is_admin_owned' => 'boolean',
        'is_active' => 'boolean',
        'data_fetching_enabled' => 'boolean',
        'trade_execution_enabled' => 'boolean',
        'copy_trading_enabled' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_data_fetch_at' => 'datetime',
        'last_trade_execution_at' => 'datetime',
        // 'credentials' handled by HasEncryptedCredentials trait
        'settings' => 'array',
        'data_settings' => 'array',
        'execution_settings' => 'array',
    ];

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

    /**
     * Check if can fetch data
     */
    public function canFetchData(): bool
    {
        return $this->data_fetching_enabled && $this->is_active && $this->status === 'active';
    }

    /**
     * Check if can execute trades
     */
    public function canExecuteTrades(): bool
    {
        return $this->trade_execution_enabled && $this->is_active && $this->status === 'active';
    }

    /**
     * Check if can copy trade
     */
    public function canCopyTrade(): bool
    {
        return $this->copy_trading_enabled && $this->canExecuteTrades();
    }

    /**
     * Get connection purpose label
     */
    public function getPurposeLabel(): string
    {
        if ($this->data_fetching_enabled && $this->trade_execution_enabled) {
            return 'Data + Execution';
        } elseif ($this->data_fetching_enabled) {
            return 'Data Only';
        } elseif ($this->trade_execution_enabled) {
            return 'Execution Only';
        }
        return 'None';
    }
    
    /**
     * Get provider name (use provider field if available, otherwise exchange_name)
     */
    public function getProviderNameAttribute(): string
    {
        return $this->provider ?? $this->exchange_name ?? '';
    }

    /**
     * Get type label with legacy compatibility
     * Handles mapping for views that expect 'mtapi' or 'ccxt_crypto'
     */
    public function getTypeAttribute($value)
    {
        // Return existing valid legacy values if present
        if ($value === 'mtapi' || $value === 'ccxt_crypto') {
            return $value;
        }

        // Map based on new connection_type enum if available
        if (isset($this->attributes['connection_type'])) {
            if ($this->attributes['connection_type'] === 'CRYPTO_EXCHANGE') {
                return 'ccxt_crypto';
            }
            if ($this->attributes['connection_type'] === 'FX_BROKER') {
                return 'mtapi';
            }
        }

        // Fallback based on provider
        $provider = $this->provider ?? $this->exchange_name;
        if (in_array($provider, ['metaapi', 'mtapi', 'mtapi_grpc'])) {
            return 'mtapi';
        }

        if (in_array($provider, ['binance', 'coinbase', 'coinbasepro', 'kraken', 'bybit', 'kucoin', 'okx'])) {
            return 'ccxt_crypto';
        }
        
        // Handle mapped values from controller that might have been saved
        if ($value === 'crypto') {
            return 'ccxt_crypto';
        }
        if ($value === 'fx') {
            return 'mtapi';
        }

        return $value ?? 'Unknown';
    }
}

