<?php

namespace Addons\TradingManagement\Modules\ExpertAdvisor\Models;

use App\Models\User;
use App\Models\Admin;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ExpertAdvisor Model
 * 
 * MT4/MT5 Expert Advisor files and configurations
 */
class ExpertAdvisor extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'expert_advisors';

    public $searchable = ['name', 'description', 'author'];

    protected $fillable = [
        'user_id', 'admin_id', 'is_admin_owned',
        'name', 'description',
        'ea_type', 'ea_file_path', 'original_filename', 'file_size',
        'parameters', 'default_parameters',
        'status', 'is_template', 'visibility', 'clonable',
        'version', 'author', 'notes',
    ];

    protected $casts = [
        'is_admin_owned' => 'boolean',
        'is_template' => 'boolean',
        'clonable' => 'boolean',
        'file_size' => 'integer',
        'parameters' => 'array',
        'default_parameters' => 'array',
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

    public function tradingBots()
    {
        return $this->hasMany(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class, 'expert_advisor_id');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeTemplate($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeMt4($query)
    {
        return $query->where('ea_type', 'mt4');
    }

    public function scopeMt5($query)
    {
        return $query->where('ea_type', 'mt5');
    }

    /**
     * Helper Methods
     */
    
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isTemplate(): bool
    {
        return $this->is_template === true;
    }

    public function getFileExtension(): string
    {
        return $this->ea_type === 'mt4' ? '.ex4' : '.ex5';
    }

    public function getFullFilePath(): ?string
    {
        if (!$this->ea_file_path) {
            return null;
        }

        return storage_path('app/' . $this->ea_file_path);
    }

    /**
     * Check if EA file exists
     */
    public function fileExists(): bool
    {
        $filePath = $this->getFullFilePath();
        return $filePath && file_exists($filePath);
    }
}
