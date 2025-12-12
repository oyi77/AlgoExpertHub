<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    protected $fillable = [
        'metric_name',
        'metric_value',
        'metric_type',
        'tags',
        'timestamp'
    ];

    protected $casts = [
        'metric_value' => 'float',
        'tags' => 'array',
        'timestamp' => 'datetime'
    ];

    /**
     * Scope to filter by metric name
     */
    public function scopeMetric($query, string $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by tag
     */
    public function scopeWithTag($query, string $key, $value)
    {
        return $query->whereJsonContains('tags', [$key => $value]);
    }
}
