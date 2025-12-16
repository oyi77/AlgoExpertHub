<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MONITORING_ENABLED', true),

    'slow_query_threshold' => env('MONITORING_SLOW_QUERY_THRESHOLD', 100), // milliseconds

    'response_time_threshold' => env('MONITORING_RESPONSE_TIME_THRESHOLD', 200), // milliseconds

    'error_rate_threshold' => env('MONITORING_ERROR_RATE_THRESHOLD', 5), // percentage

    'cpu_load_threshold' => env('MONITORING_CPU_LOAD_THRESHOLD', 4.0),

    'memory_threshold' => env('MONITORING_MEMORY_THRESHOLD', 512), // MB

    'failed_jobs_threshold' => env('MONITORING_FAILED_JOBS_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    */

    'alerts' => [
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),
        'channels' => ['log', 'database'], // log, database, email, slack
        'check_interval' => env('MONITORING_ALERT_CHECK_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Retention
    |--------------------------------------------------------------------------
    */

    'metrics_retention_days' => env('MONITORING_METRICS_RETENTION_DAYS', 30),

];
