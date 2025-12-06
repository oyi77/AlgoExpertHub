<?php

namespace Addons\CopyTrading\App\Models;

use Addons\CopyTrading\App\Models\CopyTradingSubscription;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyTradingSetting extends Model
{
    use HasFactory;

    protected $table = 'copy_trading_settings';

    protected $fillable = [
        'user_id',
        'admin_id',
        'is_admin_owned',
        'is_enabled',
        'min_followers_balance',
        'max_copiers',
        'risk_multiplier_default',
        'allow_manual_trades',
        'allow_auto_trades',
        'settings',
    ];

    protected $casts = [
        'is_admin_owned' => 'boolean',
        'is_enabled' => 'boolean',
        'min_followers_balance' => 'decimal:8',
        'max_copiers' => 'integer',
        'risk_multiplier_default' => 'decimal:4',
        'allow_manual_trades' => 'boolean',
        'allow_auto_trades' => 'boolean',
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

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
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

    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    public function canAcceptNewFollowers(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($this->max_copiers === null) {
            return true;
        }

        $currentFollowers = CopyTradingSubscription::where('trader_id', $this->user_id)
            ->where('is_active', true)
            ->count();

        return $currentFollowers < $this->max_copiers;
    }
}
