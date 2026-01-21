<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DexTraderWatchlist extends Model
{
    use HasFactory;

    protected $table = 'dex_trader_watchlist';

    protected $fillable = [
        'wallet_address',
        'platform',
        'status',
        'is_active',
        'position_count',
        'notes',
        'assigned_user_id',
        'created_by_admin_id',
        'last_polled_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_polled_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function assignedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_admin_id');
    }
}
