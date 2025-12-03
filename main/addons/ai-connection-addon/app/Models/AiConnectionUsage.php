<?php

namespace Addons\AiConnectionAddon\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiConnectionUsage extends Model
{
    use HasFactory;

    protected $table = 'ai_connection_usage';

    public $timestamps = false; // Only created_at

    protected $fillable = [
        'connection_id',
        'feature',
        'tokens_used',
        'cost',
        'success',
        'response_time_ms',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'connection_id' => 'integer',
        'tokens_used' => 'integer',
        'cost' => 'decimal:6',
        'success' => 'boolean',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the connection this usage belongs to
     */
    public function connection()
    {
        return $this->belongsTo(AiConnection::class, 'connection_id');
    }

    /**
     * Scope: Successful requests only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: Failed requests only
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: By feature
     */
    public function scopeByFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope: By connection
     */
    public function scopeByConnection($query, $connectionId)
    {
        return $query->where('connection_id', $connectionId);
    }

    /**
     * Scope: Recent logs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Today's usage
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Create usage log
     */
    public static function log(
        int $connectionId,
        string $feature,
        int $tokensUsed,
        float $cost,
        bool $success,
        int $responseTimeMs = null,
        string $errorMessage = null
    ): self {
        return static::create([
            'connection_id' => $connectionId,
            'feature' => $feature,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'success' => $success,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }

    /**
     * Get total cost for a period
     */
    public static function getTotalCost($connectionId = null, $days = 30): float
    {
        $query = static::query()
            ->where('created_at', '>=', now()->subDays($days));

        if ($connectionId) {
            $query->where('connection_id', $connectionId);
        }

        return (float) $query->sum('cost');
    }

    /**
     * Get total tokens for a period
     */
    public static function getTotalTokens($connectionId = null, $days = 30): int
    {
        $query = static::query()
            ->where('created_at', '>=', now()->subDays($days));

        if ($connectionId) {
            $query->where('connection_id', $connectionId);
        }

        return (int) $query->sum('tokens_used');
    }

    /**
     * Get usage by feature
     */
    public static function getUsageByFeature($days = 30): array
    {
        return static::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('feature, COUNT(*) as count, SUM(tokens_used) as tokens, SUM(cost) as cost')
            ->groupBy('feature')
            ->get()
            ->toArray();
    }

    /**
     * Get average response time
     */
    public static function getAverageResponseTime($connectionId = null, $days = 7): float
    {
        $query = static::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('response_time_ms');

        if ($connectionId) {
            $query->where('connection_id', $connectionId);
        }

        return (float) $query->avg('response_time_ms');
    }
}

