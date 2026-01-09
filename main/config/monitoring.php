<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure thresholds for system monitoring alerts. When metrics exceed
    | these thresholds, alerts will be generated with appropriate severity.
    |
    */

    'thresholds' => [
        // CPU load averages (1-minute average)
        'cpu_critical' => env('MONITORING_CPU_CRITICAL', 4.0),
        'cpu_warning' => env('MONITORING_CPU_WARNING', 2.5),

        // Memory usage percentage
        'memory_critical' => env('MONITORING_MEMORY_CRITICAL', 90),
        'memory_warning' => env('MONITORING_MEMORY_WARNING', 85),

        // Disk usage percentage
        'disk_critical' => env('MONITORING_DISK_CRITICAL', 90),
        'disk_warning' => env('MONITORING_DISK_WARNING', 80),

        // Database slow queries count
        'slow_queries_critical' => env('MONITORING_SLOW_QUERIES_CRITICAL', 50),
        'slow_queries_warning' => env('MONITORING_SLOW_QUERIES_WARNING', 20),

        // Failed jobs count
        'failed_jobs_critical' => env('MONITORING_FAILED_JOBS_CRITICAL', 200),
        'failed_jobs_warning' => env('MONITORING_FAILED_JOBS_WARNING', 100),

        // Cache hit rate percentage (below threshold = warning)
        'cache_hit_rate_warning' => env('MONITORING_CACHE_HIT_RATE_WARNING', 60),
        'cache_hit_rate_critical' => env('MONITORING_CACHE_HIT_RATE_CRITICAL', 40),

        // Database connection count
        'db_connections_critical' => env('MONITORING_DB_CONNECTIONS_CRITICAL', 80),
        'db_connections_warning' => env('MONITORING_DB_CONNECTIONS_WARNING', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache collected metrics (in seconds) to reduce database load.
    | Recommended: 5 seconds for balance between freshness and performance.
    |
    */

    'cache_ttl' => env('MONITORING_CACHE_TTL', 5),

    /*
    |--------------------------------------------------------------------------
    | Alert Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to store alerts in cache (in seconds). Alerts are ephemeral
    | and resolved when metrics normalize.
    |
    */

    'alert_cache_ttl' => env('MONITORING_ALERT_CACHE_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Dashboard Refresh Interval
    |--------------------------------------------------------------------------
    |
    | Frontend auto-refresh interval in milliseconds. Default: 30 seconds.
    |
    */

    'refresh_interval' => env('MONITORING_REFRESH_INTERVAL', 30000),

    /*
    |--------------------------------------------------------------------------
    | Historical Data Retention
    |--------------------------------------------------------------------------
    |
    | How many hours of historical data to keep for charts. Default: 24 hours.
    |
    */

    'history_hours' => env('MONITORING_HISTORY_HOURS', 24),
];
