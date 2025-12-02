<?php

namespace Addons\FilterStrategyAddon\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FilterStrategy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'filter_strategies';

    protected $fillable = [
        'name',
        'description',
        'created_by_user_id',
        'visibility',
        'clonable',
        'enabled',
        'config',
    ];

    protected $casts = [
        'clonable' => 'boolean',
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Get the user who created this filter strategy.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get trading presets using this filter strategy.
     */
    public function tradingPresets()
    {
        if (!class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return collect();
        }
        return $this->hasMany(\Addons\TradingPresetAddon\App\Models\TradingPreset::class, 'filter_strategy_id');
    }

    /**
     * Get count of linked presets.
     */
    public function getLinkedPresetsCountAttribute(): int
    {
        return $this->tradingPresets()->count();
    }

    /**
     * Scope to get only public strategies.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    /**
     * Scope to get only private strategies for a user.
     */
    public function scopePrivateForUser($query, $userId)
    {
        return $query->where('visibility', 'PRIVATE')
            ->where('created_by_user_id', $userId);
    }

    /**
     * Scope to get enabled strategies.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Check if strategy is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'PUBLIC_MARKETPLACE';
    }

    /**
     * Check if strategy is clonable.
     */
    public function isClonable(): bool
    {
        return $this->clonable;
    }

    /**
     * Check if user can edit this strategy.
     */
    public function canEditBy($userId): bool
    {
        return $this->created_by_user_id === $userId;
    }

    /**
     * Clone this strategy for a user.
     */
    public function cloneForUser($userId): self
    {
        $cloned = $this->replicate();
        $cloned->created_by_user_id = $userId;
        $cloned->visibility = 'PRIVATE';
        $cloned->name = $this->name . ' (Copy)';
        $cloned->save();

        return $cloned;
    }
}

