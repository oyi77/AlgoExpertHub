<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateClone extends Model
{
    use HasFactory;

    protected $table = 'template_clones';

    protected $fillable = [
        'user_id', 'template_type', 'template_id', 'original_id',
        'cloned_config', 'is_active', 'custom_name'
    ];

    protected $casts = [
        'cloned_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        switch ($this->template_type) {
            case 'bot':
                return $this->belongsTo(BotTemplate::class, 'template_id');
            case 'signal':
                return $this->belongsTo(SignalSourceTemplate::class, 'template_id');
            case 'complete':
                return $this->belongsTo(CompleteBot::class, 'template_id');
            default:
                return null;
        }
    }

    public function originalTemplate()
    {
        switch ($this->template_type) {
            case 'bot':
                return $this->belongsTo(BotTemplate::class, 'original_id');
            case 'signal':
                return $this->belongsTo(SignalSourceTemplate::class, 'original_id');
            case 'complete':
                return $this->belongsTo(CompleteBot::class, 'original_id');
            default:
                return null;
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTemplateType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($clone) {
            // Increment downloads count
            $template = $clone->template();
            if ($template) {
                $template->increment('downloads_count');
            }
        });
    }
}


