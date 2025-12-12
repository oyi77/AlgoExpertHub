<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Risk Management Configuration
    |--------------------------------------------------------------------------
    */

    'risk_management' => [
        'max_consecutive_losses' => env('TRADING_MAX_CONSECUTIVE_LOSSES', 5),
        'max_drawdown_percent' => env('TRADING_MAX_DRAWDOWN_PERCENT', 20),
        'min_position_size' => env('TRADING_MIN_POSITION_SIZE', 0.01),
        'max_position_size' => env('TRADING_MAX_POSITION_SIZE', 10.0),
        'max_position_account_percent' => env('TRADING_MAX_POSITION_ACCOUNT_PERCENT', 10),
        'circuit_breaker_reset_hours' => env('TRADING_CIRCUIT_BREAKER_RESET_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Health Configuration
    |--------------------------------------------------------------------------
    */

    'connection_health' => [
        'max_recovery_attempts' => env('TRADING_MAX_RECOVERY_ATTEMPTS', 3),
        'retry_delay_seconds' => env('TRADING_RETRY_DELAY_SECONDS', 5),
        'ping_interval_seconds' => env('TRADING_PING_INTERVAL', 60),
        'health_check_interval_seconds' => env('TRADING_HEALTH_CHECK_INTERVAL', 300),
    ],

];
