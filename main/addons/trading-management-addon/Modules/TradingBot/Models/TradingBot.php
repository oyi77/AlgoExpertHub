<?php

namespace Addons\TradingManagement\Modules\TradingBot\Models;

use App\Models\User;
use App\Models\Admin;
use App\Traits\Searchable;
use Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TradingBot Model
 * 
 * Coinrule-like trading bot builder
 * Combines: Exchange Connection + Trading Preset + Filter Strategy + AI Profile
 */
class TradingBot extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'trading_bots';

    public $searchable = ['name', 'description'];

    protected $fillable = [
        'user_id', 'admin_id',
        'name', 'description',
        'exchange_connection_id', 'trading_preset_id', 'filter_strategy_id', 'ai_model_profile_id',
        'trading_mode',
        'is_active', 'is_paper_trading',
        'total_executions', 'successful_executions', 'failed_executions',
        'total_profit', 'win_rate',
        'visibility', 'clonable', 'is_default_template', 'created_by_user_id',
        'suggested_connection_type', 'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_paper_trading' => 'boolean',
        'total_executions' => 'integer',
        'successful_executions' => 'integer',
        'failed_executions' => 'integer',
        'total_profit' => 'decimal:2',
        'win_rate' => 'decimal:2',
        'clonable' => 'boolean',
        'is_default_template' => 'boolean',
        'tags' => 'array',
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

    public function exchangeConnection()
    {
        return $this->belongsTo(ExchangeConnection::class, 'exchange_connection_id');
    }

    public function tradingPreset()
    {
        return $this->belongsTo(TradingPreset::class, 'trading_preset_id');
    }

    public function filterStrategy()
    {
        return $this->belongsTo(FilterStrategy::class, 'filter_strategy_id');
    }

    public function aiModelProfile()
    {
        return $this->belongsTo(AiModelProfile::class, 'ai_model_profile_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePaperTrading($query)
    {
        return $query->where('is_paper_trading', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeDefaultTemplates($query)
    {
        return $query->where('is_default_template', true);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC_MARKETPLACE');
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', 'PRIVATE');
    }

    public function scopeClonable($query)
    {
        return $query->where('clonable', true);
    }

    public function scopeTemplates($query)
    {
        return $query->where(function ($q) {
            $q->where('is_default_template', true)
              ->orWhereNull('created_by_user_id');
        });
    }

    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Helper Methods
     */
    
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isPaperTrading(): bool
    {
        return $this->is_paper_trading;
    }

    public function getOwnerAttribute()
    {
        return $this->user_id ? $this->user : $this->admin;
    }

    /**
     * Helper Methods - Template/Visibility
     */
    
    public function isPublic(): bool
    {
        return $this->visibility === 'PUBLIC_MARKETPLACE';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'PRIVATE';
    }

    public function isClonable(): bool
    {
        return $this->clonable === true;
    }

    public function isDefaultTemplate(): bool
    {
        return $this->is_default_template === true;
    }

    public function isTemplate(): bool
    {
        return $this->is_default_template || is_null($this->created_by_user_id);
    }

    public function canBeEditedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admins can edit any
        if (isset($user->type) && $user->type === 'super') {
            return true;
        }

        // Users can edit their own
        if ($this->created_by_user_id === $user->id) {
            return true;
        }

        // Default templates cannot be edited by non-admins
        if ($this->is_default_template) {
            return false;
        }

        return false;
    }

    public function canBeClonedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // If not clonable, only creator or admin can clone
        if (!$this->isClonable()) {
            return $this->canBeEditedBy($user);
        }

        // Public templates can be cloned by anyone
        if ($this->isPublic()) {
            return true;
        }

        // Private templates only by creator/admin
        return $this->canBeEditedBy($user);
    }

    /**
     * Clone template for user
     * 
     * @param User $user
     * @param int $connectionId
     * @param array $options ['name' => string, 'is_paper_trading' => bool]
     * @return self
     */
    public function cloneForUser($user, int $connectionId, array $options = [])
    {
        // Validate clone permission
        if (!$this->canBeClonedBy($user)) {
            throw new \Exception('Bot template cannot be cloned by this user');
        }

        // Templates don't have exchange_connection_id - user must provide
        // But for existing bots with connections, we validate
        if ($this->exchange_connection_id && $this->exchange_connection_id !== $connectionId) {
            // Template has a connection but user is providing a different one - that's OK
        }

        // Validate connection belongs to user
        $connection = ExchangeConnection::findOrFail($connectionId);
        if ($connection->user_id !== $user->id) {
            throw new \Exception('Connection does not belong to user');
        }

        // Validate connection type matches suggestion (if set)
        if ($this->suggested_connection_type) {
            // Map connection_type enum (CRYPTO_EXCHANGE, FX_BROKER) to suggested type (crypto, fx)
            $connectionType = null;
            if ($connection->connection_type === 'CRYPTO_EXCHANGE') {
                $connectionType = 'crypto';
            } elseif ($connection->connection_type === 'FX_BROKER') {
                $connectionType = 'fx';
            }
            
            if ($connectionType && $this->suggested_connection_type !== 'both' && 
                $this->suggested_connection_type !== $connectionType) {
                throw new \Exception("Connection type must be {$this->suggested_connection_type} (this connection is {$connectionType})");
            }
        }

        // Clone preset if needed
        $presetId = $this->trading_preset_id;
        if ($this->tradingPreset) {
            $preset = $this->tradingPreset;
            if ($preset->isPublic() && $preset->isClonable()) {
                $clonedPreset = $preset->cloneFor($user);
                $presetId = $clonedPreset->id;
            } else {
                // Use existing preset or user's default
                $presetId = $user->default_preset_id ?? $presetId;
            }
        }

        // Clone filter if needed
        $filterId = $this->filter_strategy_id;
        if ($filterId && $this->filterStrategy) {
            $filter = $this->filterStrategy;
            if (method_exists($filter, 'isPublic') && method_exists($filter, 'isClonable')) {
                if ($filter->isPublic() && $filter->isClonable()) {
                    if (method_exists($filter, 'cloneForUser')) {
                        $clonedFilter = $filter->cloneForUser($user->id);
                        $filterId = $clonedFilter->id;
                    } elseif (method_exists($filter, 'cloneFor')) {
                        $clonedFilter = $filter->cloneFor($user);
                        $filterId = $clonedFilter->id;
                    }
                } else {
                    $filterId = null; // User can set their own
                }
            }
        }

        // Clone AI profile if needed (future implementation)
        $aiProfileId = $this->ai_model_profile_id;

        // Create cloned bot
        $clonedBot = self::create([
            'user_id' => $user->id,
            'admin_id' => null,
            'name' => $options['name'] ?? ($this->name . ' (Copy)'),
            'description' => $this->description,
            'exchange_connection_id' => $connectionId,
            'trading_preset_id' => $presetId,
            'filter_strategy_id' => $filterId,
            'ai_model_profile_id' => $aiProfileId,
            'is_active' => false, // Start inactive, user activates
            'is_paper_trading' => $options['is_paper_trading'] ?? true,
            'visibility' => 'PRIVATE',
            'clonable' => false,
            'is_default_template' => false,
            'created_by_user_id' => $user->id,
            'suggested_connection_type' => null, // Not needed for user bots
            'tags' => null, // User can set their own tags
        ]);

        return $clonedBot;
    }

    /**
     * Update statistics
     */
    public function updateStatistics()
    {
        // Calculate win rate from executions
        // This would be called after executions complete
        // For now, placeholder - will be implemented with execution logs
    }
}
