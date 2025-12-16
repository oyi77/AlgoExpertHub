<?php

namespace Addons\TradingManagement\Modules\FilterStrategy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FilterStrategy Model
 * 
 * Migrated from filter-strategy-addon
 * Stores technical indicator filter configurations
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $created_by_user_id
 * @property string $visibility (PRIVATE, PUBLIC_MARKETPLACE)
 * @property bool $clonable
 * @property bool $enabled
 * @property array $config (indicators + rules)
 */
class FilterStrategy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'filter_strategies';

    protected $fillable = [
        'name',
        'filter_type',
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
     * Relationships
     */
    
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tradingPresets()
    {
        // Check if trading-preset-addon exists (for backward compatibility)
        if (class_exists(\Addons\TradingPresetAddon\App\Models\TradingPreset::class)) {
            return $this->hasMany(\Addons\TradingPresetAddon\App\Models\TradingPreset::class, 'filter_strategy_id');
        }
        
        // Future: Link to risk-management module presets
        return collect();
    }

    /**
     * Scopes
     */
    
    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    public function scopePrivateForUser($query, $userId)
    {
        return $query->where('visibility', 'PRIVATE')
            ->where('created_by_user_id', $userId);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Helper Methods
     */
    
    public function getLinkedPresetsCountAttribute(): int
    {
        return $this->tradingPresets()->count();
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'PUBLIC_MARKETPLACE';
    }

    public function isClonable(): bool
    {
        return $this->clonable;
    }

    public function canEditBy($userId): bool
    {
        return $this->created_by_user_id === $userId;
    }

    public function canBeClonedBy($userId): bool
    {
        // Can't clone own strategy
        if ($this->created_by_user_id === $userId) {
            return false;
        }
        
        // Must be clonable and public
        return $this->isClonable() && $this->isPublic();
    }

    public function cloneForUser($userId): self
    {
        $cloned = $this->replicate();
        $cloned->created_by_user_id = $userId;
        $cloned->visibility = 'PRIVATE';
        $cloned->name = $this->name . ' (Copy)';
        $cloned->save();

        return $cloned;
    }

    /**
     * Check if this is a test filter (for testing bot functionality)
     */
    public function isTestFilter(): bool
    {
        return $this->filter_type === 'test';
    }

    /**
     * Check if this is a technical indicator filter
     */
    public function isTechnicalFilter(): bool
    {
        return $this->filter_type === 'technical' || $this->filter_type === null;
    }

    /**
     * Check if this filter should skip all analysis
     */
    public function shouldSkipAnalysis(): bool
    {
        return $this->filter_type === 'none' || $this->filter_type === 'test';
    }
}

