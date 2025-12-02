<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ChannelMessage extends Model
{
    use HasFactory, Searchable;

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
        'processing_attempts'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'confidence_score' => 'integer',
        'processing_attempts' => 'integer'
    ];

    /**
     * Get the channel source that owns the message.
     */
    public function channelSource()
    {
        return $this->belongsTo(ChannelSource::class);
    }

    /**
     * Get the signal created from this message.
     */
    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    /**
     * Generate message hash from content and timestamp.
     */
    public static function generateHash($message, $timestamp = null)
    {
        $timestamp = $timestamp ?? now()->toDateTimeString();
        $content = $message . $timestamp;
        return hash('sha256', $content);
    }

    /**
     * Set message hash automatically.
     */
    public function setMessageHashAttribute($value)
    {
        if (empty($value) && !empty($this->raw_message)) {
            $this->attributes['message_hash'] = self::generateHash($this->raw_message);
        } else {
            $this->attributes['message_hash'] = $value;
        }
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by channel source.
     */
    public function scopeByChannelSource($query, $channelSourceId)
    {
        return $query->where('channel_source_id', $channelSourceId);
    }

    /**
     * Scope a query to only include pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include processed messages.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope a query to only include duplicate messages.
     */
    public function scopeDuplicate($query)
    {
        return $query->where('status', 'duplicate');
    }

    /**
     * Scope a query to only include messages needing manual review.
     */
    public function scopeManualReview($query)
    {
        return $query->where('status', 'manual_review');
    }

    /**
     * Mark message as processed.
     */
    public function markAsProcessed($signalId = null)
    {
        $this->update([
            'status' => 'processed',
            'signal_id' => $signalId ?? $this->signal_id
        ]);
        return $this;
    }

    /**
     * Mark message as failed.
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processing_attempts' => $this->processing_attempts + 1
        ]);
        return $this;
    }

    /**
     * Mark message as duplicate.
     */
    public function markAsDuplicate()
    {
        $this->update(['status' => 'duplicate']);
        return $this;
    }

    /**
     * Mark message for manual review.
     */
    public function markForManualReview($reason = null)
    {
        $this->update([
            'status' => 'manual_review',
            'error_message' => $reason
        ]);
        return $this;
    }

    /**
     * Increment processing attempts.
     */
    public function incrementAttempts()
    {
        $this->increment('processing_attempts');
        return $this;
    }
}

