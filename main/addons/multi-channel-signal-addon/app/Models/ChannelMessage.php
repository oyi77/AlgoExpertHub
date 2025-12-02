<?php

namespace Addons\MultiChannelSignalAddon\App\Models;

use App\Models\Signal;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelMessage extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'channel_messages';

    public $searchable = ['id'];

    protected $fillable = [
        'channel_source_id',
        'raw_message',
        'message_hash',
        'parsed_data',
        'signal_id',
        'status',
        'confidence_score',
        'error_message',
        'processing_attempts',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'confidence_score' => 'integer',
        'processing_attempts' => 'integer',
    ];

    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class);
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public static function generateHash(string $message, ?string $timestamp = null): string
    {
        $timestamp = $timestamp ?? now()->toDateTimeString();
        $content = $message . $timestamp;

        return hash('sha256', $content);
    }

    public function setMessageHashAttribute($value): void
    {
        if (empty($value) && !empty($this->raw_message)) {
            $this->attributes['message_hash'] = self::generateHash($this->raw_message);
        } else {
            $this->attributes['message_hash'] = $value;
        }
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByChannelSource($query, int $channelSourceId)
    {
        return $query->where('channel_source_id', $channelSourceId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeDuplicate($query)
    {
        return $query->where('status', 'duplicate');
    }

    public function scopeManualReview($query)
    {
        return $query->where('status', 'manual_review');
    }

    public function markAsProcessed(?int $signalId = null): self
    {
        $this->update([
            'status' => 'processed',
            'signal_id' => $signalId ?? $this->signal_id,
        ]);

        return $this;
    }

    public function markAsFailed(?string $errorMessage = null): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processing_attempts' => $this->processing_attempts + 1,
        ]);

        return $this;
    }

    public function markAsDuplicate(): self
    {
        $this->update(['status' => 'duplicate']);

        return $this;
    }

    public function markForManualReview(?string $reason = null): self
    {
        $this->update([
            'status' => 'manual_review',
            'error_message' => $reason,
        ]);

        return $this;
    }

    public function incrementAttempts(): self
    {
        $this->increment('processing_attempts');

        return $this;
    }
}
