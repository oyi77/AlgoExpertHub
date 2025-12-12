<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MetricsCollector
{
    protected array $buffer = [];
    protected int $bufferSize = 100;

    /**
     * Increment a counter metric
     */
    public function increment(string $metric, array $tags = []): void
    {
        $this->recordMetric($metric, 1, 'counter', $tags);
    }

    /**
     * Record a gauge metric (point-in-time value)
     */
    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $this->recordMetric($metric, $value, 'gauge', $tags);
    }

    /**
     * Record a histogram metric (distribution of values)
     */
    public function histogram(string $metric, float $value, array $tags = []): void
    {
        $this->recordMetric($metric, $value, 'histogram', $tags);
    }

    /**
     * Get metrics with filters
     */
    public function getMetrics(string $metric, array $filters = []): array
    {
        $cacheKey = "metrics_{$metric}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 300, function () use ($metric, $filters) {
            $query = DB::table('system_metrics')
                ->where('metric_name', $metric);

            if (isset($filters['start_date'])) {
                $query->where('timestamp', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('timestamp', '<=', $filters['end_date']);
            }

            if (isset($filters['tags'])) {
                foreach ($filters['tags'] as $key => $value) {
                    $query->whereJsonContains('tags', [$key => $value]);
                }
            }

            return $query->orderBy('timestamp', 'desc')
                ->limit($filters['limit'] ?? 1000)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get a single metric value (most recent)
     */
    public function getMetricValue(string $metric, array $tags = []): ?float
    {
        $query = DB::table('system_metrics')
            ->where('metric_name', $metric)
            ->orderBy('timestamp', 'desc');

        if (!empty($tags)) {
            foreach ($tags as $key => $value) {
                $query->whereJsonContains('tags', [$key => $value]);
            }
        }

        $result = $query->first();

        return $result ? (float) $result->metric_value : null;
    }

    /**
     * Record a metric
     */
    protected function recordMetric(string $metric, float $value, string $type, array $tags): void
    {
        $this->buffer[] = [
            'metric_name' => $metric,
            'metric_value' => $value,
            'metric_type' => $type,
            'tags' => json_encode($tags),
            'timestamp' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    /**
     * Flush buffered metrics to database
     */
    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        try {
            DB::table('system_metrics')->insert($this->buffer);
            $this->buffer = [];
        } catch (\Exception $e) {
            \Log::error('Failed to flush metrics', [
                'error' => $e->getMessage(),
                'buffer_size' => count($this->buffer)
            ]);
        }
    }

    /**
     * Get aggregated metrics
     */
    public function getAggregated(string $metric, string $aggregation = 'avg', array $filters = []): float
    {
        $query = DB::table('system_metrics')
            ->where('metric_name', $metric);

        if (isset($filters['start_date'])) {
            $query->where('timestamp', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('timestamp', '<=', $filters['end_date']);
        }

        switch ($aggregation) {
            case 'avg':
                return (float) $query->avg('metric_value');
            case 'sum':
                return (float) $query->sum('metric_value');
            case 'min':
                return (float) $query->min('metric_value');
            case 'max':
                return (float) $query->max('metric_value');
            case 'count':
                return (float) $query->count();
            default:
                return 0.0;
        }
    }

    /**
     * Clean up old metrics
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        return DB::table('system_metrics')
            ->where('timestamp', '<', Carbon::now()->subDays($daysToKeep))
            ->delete();
    }
}
