<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompleteBot extends Model
{
    use HasFactory;

    protected $table = 'complete_bots';

    protected $fillable = [
        'user_id', 'name', 'description', 'indicators_config', 'entry_rules',
        'exit_rules', 'risk_config', 'is_public', 'is_featured', 'price',
        'downloads_count', 'avg_rating', 'total_ratings', 'backtest_id',
        'image_url', 'tags'
    ];

    protected $casts = [
        'indicators_config' => 'array',
        'entry_rules' => 'array',
        'exit_rules' => 'array',
        'risk_config' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'avg_rating' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function backtest()
    {
        return $this->hasOne(TemplateBacktest::class, 'template_id')
            ->where('template_type', 'complete');
    }

    public function ratings()
    {
        return $this->hasMany(TemplateRating::class, 'template_id')
            ->where('template_type', 'complete');
    }

    public function clones()
    {
        return $this->hasMany(TemplateClone::class, 'template_id')
            ->where('template_type', 'complete');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('downloads_count', 'desc');
    }

    public function scopeTopRated($query)
    {
        return $query->where('total_ratings', '>', 0)->orderBy('avg_rating', 'desc');
    }
}


