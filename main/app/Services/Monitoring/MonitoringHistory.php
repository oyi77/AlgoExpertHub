<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

/**
 * MonitoringHistory service
 *
 * Stores lightweight historical snapshots for the monitoring dashboard charts.
 */
class MonitoringHistory
{
    protected int $historyHours;
    protected int $minSnapshotInterval; // seconds

    public function __construct()
    {
        $this->historyHours = (int) config('monitoring.history_hours', 24);
        $this->minSnapshotInterval = 60; // keep at most 1 snapshot per minute
    }

    /**
     * Record a snapshot of system & worker metrics
     *
     * @param array $systemMetrics  Output from SystemMonitor::collectMetrics()
     * @param array $workerMetrics  Output from WorkerManager::getAllWorkers()
     */
    public function recordSnapshot(array $systemMetrics, array $workerMetrics): void
    {
        $cacheKey = 'monitoring:history';
        $history = Cache::get($cacheKey, []);

        $lastSnapshot = end($history) ?: null;
        if ($lastSnapshot && isset($lastSnapshot['timestamp'])) {
            $lastTime = Carbon::parse($lastSnapshot['timestamp']);
            if ($lastTime->diffInSeconds(now()) < $this->minSnapshotInterval) {
                return;
            }
        }

        $system = $systemMetrics['system'] ?? [];
        $database = $systemMetrics['database'] ?? [];

        $snapshot = [
            'timestamp' => now()->toIso8601String(),
            'cpu_load_1m' => (float) ($system['cpu_load_1m'] ?? 0),
            'memory_percent' => (float) ($system['memory_usage_percent'] ?? 0),
            'memory_usage_mb' => (float) ($system['memory_usage_mb'] ?? 0),
            'disk_usage_percent' => (float) ($system['disk_usage_percent'] ?? 0),
            'db_connections' => (int) ($database['active_connections'] ?? 0),
            'slow_queries' => (int) ($database['slow_queries'] ?? 0),
            'queue_active' => (int) ($workerMetrics['queue']['active'] ?? 0),
            'queue_jobs' => (int) ($workerMetrics['queue']['total_jobs'] ?? 0),
            'bot_active' => (int) ($workerMetrics['bots']['active'] ?? 0),
            'octane_workers' => (int) ($workerMetrics['octane']['workers'] ?? 0),
        ];

        $history[] = $snapshot;
        $history = $this->trimHistory($history);

        Cache::put($cacheKey, $history, $this->historyHours * 3600);
    }

    /**
     * Retrieve history for charts
     *
     * @return array
     */
    public function getHistory(): array
    {
        $cacheKey = 'monitoring:history';
        $history = $this->trimHistory(Cache::get($cacheKey, []));

        $labels = [];
        $systemHealth = [
            'cpu' => [],
            'memory' => [],
            'disk' => [],
        ];
        $workers = [
            'queue_active' => [],
            'queue_jobs' => [],
            'bot_active' => [],
            'octane_workers' => [],
        ];
        $database = [
            'connections' => [],
            'slow_queries' => [],
        ];

        foreach ($history as $entry) {
            $labels[] = Carbon::parse($entry['timestamp'])->format('H:i');
            $systemHealth['cpu'][] = (float) ($entry['cpu_load_1m'] ?? 0);
            $systemHealth['memory'][] = (float) ($entry['memory_percent'] ?? 0);
            $systemHealth['disk'][] = (float) ($entry['disk_usage_percent'] ?? 0);

            $workers['queue_active'][] = (int) ($entry['queue_active'] ?? 0);
            $workers['queue_jobs'][] = (int) ($entry['queue_jobs'] ?? 0);
            $workers['bot_active'][] = (int) ($entry['bot_active'] ?? 0);
            $workers['octane_workers'][] = (int) ($entry['octane_workers'] ?? 0);

            $database['connections'][] = (int) ($entry['db_connections'] ?? 0);
            $database['slow_queries'][] = (int) ($entry['slow_queries'] ?? 0);
        }

        return [
            'labels' => $labels,
            'system_health' => $systemHealth,
            'worker_activity' => $workers,
            'database' => $database,
        ];
    }

    protected function trimHistory(array $history): array
    {
        $cutoff = now()->subHours($this->historyHours);
        $history = array_filter($history, function ($entry) use ($cutoff) {
            return isset($entry['timestamp']) && Carbon::parse($entry['timestamp'])->greaterThanOrEqualTo($cutoff);
        });

        // Reindex and ensure chronological order
        usort($history, function ($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });

        return array_values($history);
    }
}

