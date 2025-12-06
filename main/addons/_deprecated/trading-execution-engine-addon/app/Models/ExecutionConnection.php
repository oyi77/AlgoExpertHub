<?php

namespace Addons\TradingExecutionEngine\App\Models;

use App\Models\Admin;
use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ExecutionConnection extends Model
{
    use HasFactory, Searchable;

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_admin_owned' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function executionLogs()
    {
        return $this->hasMany(ExecutionLog::class);
    }

    public function positions()
    {
        return $this->hasMany(ExecutionPosition::class);
    }

    public function analytics()
    {
        return $this->hasMany(ExecutionAnalytic::class);
    }

    public function notifications()
    {
        return $this->hasMany(ExecutionNotification::class);
    }

    /**
     * Encrypt credentials before saving.
     */
    public function setCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $json = json_encode($value);
            if ($json === false) {
                \Log::error("Failed to JSON encode credentials", ['credentials' => $value]);
                throw new \RuntimeException("Failed to encode credentials to JSON");
            }
            $this->attributes['credentials'] = Crypt::encryptString($json);
        } else {
            $this->attributes['credentials'] = $value;
        }
    }

    /**
     * Decrypt credentials when retrieving.
     */
    public function getCredentialsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            // Check if value is already decrypted (for backward compatibility)
            if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error("Failed to JSON decode credentials", [
                    'json_error' => json_last_error_msg(),
                    'decrypted_length' => strlen($decrypted)
                ]);
                return [];
            }
            
            return is_array($decoded) ? $decoded : [];
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::error("Failed to decrypt credentials - DecryptException", [
                'error' => $e->getMessage(),
                'value_length' => strlen($value ?? '')
            ]);
            return [];
        } catch (\Throwable $th) {
            \Log::error("Failed to decrypt credentials", [
                'error' => $th->getMessage(),
                'value_length' => strlen($value ?? '')
            ]);
            return [];
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

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

    public function isAdminOwned(): bool
    {
        return $this->is_admin_owned === 1 || $this->is_admin_owned === true;
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    public function updateLastUsed(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    public function markAsError(?string $message = null): void
    {
        $this->forceFill([
            'status' => 'error',
            'last_error' => $message,
        ])->save();
    }

    public function markAsActive(): void
    {
        $this->forceFill([
            'status' => 'active',
            'last_error' => null,
        ])->save();
    }
}

