<?php

namespace Addons\TradingManagement\Modules\ExchangeConnection\Models;

use App\Models\Admin;
use App\Models\User;
use App\Traits\Searchable;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
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
    use HasFactory, Searchable;

    protected $table = 'execution_connections';

    public $searchable = ['name', 'provider'];

    protected $fillable = [
        'user_id',
        'admin_id',
        'is_admin_owned',
        'name',
        'connection_type',
        'provider',
        'credentials',
        'data_fetching_enabled',
        'trade_execution_enabled',
        'status',
        'is_active',
        'last_error',
        'last_tested_at',
        'last_data_fetch_at',
        'last_trade_execution_at',
        'preset_id',
        'execution_settings',
        'data_settings',
    ];

    protected $casts = [
        'is_admin_owned' => 'boolean',
        'data_fetching_enabled' => 'boolean',
        'trade_execution_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_data_fetch_at' => 'datetime',
        'last_trade_execution_at' => 'datetime',
        'credentials' => 'encrypted:array',
        'execution_settings' => 'array',
        'data_settings' => 'array',
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
        return $this->data_fetching_enabled && $this->is_active && $this->status === 'connected';
    }

    /**
     * Check if can execute trades
     */
    public function canExecuteTrades(): bool
    {
        return $this->trade_execution_enabled && $this->is_active && $this->status === 'connected';
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
}

