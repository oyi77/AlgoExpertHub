<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MtAccount extends Model
{
    use HasFactory;

    protected $table = 'mt_accounts';

    protected $fillable = [
        'user_id',
        'admin_id',
        'execution_connection_id',
        'platform',
        'account_number',
        'server',
        'broker_name',
        'api_key',
        'account_id',
        'credentials',
        'balance',
        'equity',
        'margin',
        'free_margin',
        'currency',
        'leverage',
        'status',
        'last_synced_at',
        'last_error',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'equity' => 'decimal:2',
        'margin' => 'decimal:2',
        'free_margin' => 'decimal:2',
        'leverage' => 'integer',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'credentials' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function executionConnection()
    {
        return $this->belongsTo(ExecutionConnection::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Get credentials for API calls (decrypted).
     */
    public function getApiCredentials(): array
    {
        return [
            'api_key' => $this->api_key,
            'account_id' => $this->account_id,
        ];
    }

    /**
     * Sync account info from MT4/MT5.
     */
    public function syncAccountInfo(): bool
    {
        try {
            $adapter = app(\Addons\TradingExecutionEngine\App\Services\ConnectionService::class)
                ->getAdapter($this->executionConnection);
            
            if (!$adapter) {
                return false;
            }

            $balance = $adapter->getBalance();
            
            $this->update([
                'balance' => $balance['balance'] ?? 0,
                'equity' => $balance['equity'] ?? 0,
                'free_margin' => $balance['free_margin'] ?? 0,
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->update([
                'last_error' => $e->getMessage(),
                'status' => 'error',
            ]);
            return false;
        }
    }
}
