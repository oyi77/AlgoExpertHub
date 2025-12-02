<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use App\Models\User;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageParsingPattern extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'message_parsing_patterns';

    public $searchable = ['name', 'description'];

    protected $fillable = [
        'channel_source_id',
        'user_id',
        'name',
        'description',
        'pattern_type',
        'pattern_config',
        'priority',
        'is_active',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'pattern_config' => 'array',
        'is_active' => 'boolean',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'priority' => 'integer',
    ];

    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForChannel($query, ?int $channelSourceId)
    {
        return $query->where(function ($q) use ($channelSourceId) {
            $q->where('channel_source_id', $channelSourceId)
                ->orWhereNull('channel_source_id'); // Global patterns
        });
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority')->orderByDesc('success_count');
    }

    public function incrementSuccess(): void
    {
        $this->increment('success_count');
    }

    public function incrementFailure(): void
    {
        $this->increment('failure_count');
    }

    public function getSuccessRate(): float
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 0;
        }
        return ($this->success_count / $total) * 100;
    }
}

