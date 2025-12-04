<?php

namespace Addons\TradingManagement\Modules\DataProvider\Models;

use App\Models\Admin;
use App\Models\User;
use App\Traits\Searchable;
use Addons\TradingManagement\Shared\Traits\HasEncryptedCredentials;
use Addons\TradingManagement\Shared\Traits\ConnectionHealthCheck;
use Addons\TradingManagement\Modules\MarketData\Models\MarketData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DataConnection Model
 * 
 * Represents a connection to a data provider (mtapi.io, CCXT, custom API)
 * Used for fetching market data, NOT for trade execution
 * 
 * @property int $id
 * @property int|null $user_id
 * @property int|null $admin_id
 * @property string $name
 * @property string $type (mtapi, ccxt_crypto, custom_api)
 * @property string $provider (binance, mt4_account_123, etc.)
 * @property array $credentials (encrypted)
 * @property array|null $config
 * @property string $status (active, inactive, error, testing)
 * @property bool $is_active
 * @property bool $is_admin_owned
 * @property \Carbon\Carbon|null $last_connected_at
 * @property \Carbon\Carbon|null $last_tested_at
 * @property \Carbon\Carbon|null $last_used_at
 * @property string|null $last_error
 * @property array|null $settings
 */
class DataConnection extends Model
{
    use HasFactory, 
        Searchable, 
        HasEncryptedCredentials, 
        ConnectionHealthCheck;

    protected $table = 'data_connections';

    public $searchable = ['name', 'provider'];

    protected $fillable = [
        'user_id',
        'admin_id',
        'name',
        'type',
        'provider',
        'credentials',
        'config',
        'status',
        'is_active',
        'is_admin_owned',
        'last_connected_at',
        'last_tested_at',
        'last_used_at',
        'last_error',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_admin_owned' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'config' => 'array',
        'settings' => 'array',
        // 'credentials' handled by HasEncryptedCredentials trait
    ];

    protected $attributes = [
        'is_active' => false,
        'is_admin_owned' => false,
        'status' => 'inactive',
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

    public function marketData()
    {
        return $this->hasMany(MarketData::class);
    }

    public function logs()
    {
        return $this->hasMany(DataConnectionLog::class);
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

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId)->where('is_admin_owned', true);
    }

    public function scopeAdminOwned($query)
    {
        return $query->where('is_admin_owned', true);
    }

    public function scopeUserOwned($query)
    {
        return $query->where('is_admin_owned', false);
    }

    /**
     * Helper Methods
     */
    
    public function isAdminOwned(): bool
    {
        return $this->is_admin_owned === 1 || $this->is_admin_owned === true;
    }

    public function isMtapi(): bool
    {
        return $this->type === 'mtapi';
    }

    public function isCcxt(): bool
    {
        return $this->type === 'ccxt_crypto';
    }

    public function getOwner()
    {
        return $this->isAdminOwned() ? $this->admin : $this->user;
    }

    public function getSymbolsFromSettings(): array
    {
        return $this->settings['symbols'] ?? [];
    }

    public function getTimeframesFromSettings(): array
    {
        return $this->settings['timeframes'] ?? ['H1', 'H4', 'D1'];
    }

    /**
     * Log an action
     */
    public function logAction(string $action, string $status, ?string $message = null, ?array $metadata = null): void
    {
        $this->logs()->create([
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}

