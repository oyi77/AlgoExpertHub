<?php

namespace Addons\TradingManagement\Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateRating extends Model
{
    use HasFactory;

    protected $table = 'template_ratings';

    protected $fillable = [
        'user_id', 'template_type', 'template_id', 'rating',
        'review', 'verified_purchase', 'helpful_votes'
    ];

    protected $casts = [
        'verified_purchase' => 'boolean',
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

    public function scopeByTemplateType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeByTemplateId($query, int $id)
    {
        return $query->where('template_id', $id);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified_purchase', true);
    }

    public function scopeWithReview($query)
    {
        return $query->whereNotNull('review');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeHelpful($query)
    {
        return $query->orderBy('helpful_votes', 'desc');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($rating) {
            $rating->updateTemplateRating();
        });

        static::updated(function ($rating) {
            $rating->updateTemplateRating();
        });

        static::deleted(function ($rating) {
            $rating->updateTemplateRating();
        });
    }

    protected function updateTemplateRating()
    {
        $template = $this->template();
        if (!$template) return;

        $stats = static::where('template_type', $this->template_type)
            ->where('template_id', $this->template_id)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_ratings')
            ->first();

        $template->update([
            'avg_rating' => round($stats->avg_rating, 2),
            'total_ratings' => $stats->total_ratings,
        ]);
    }
}


