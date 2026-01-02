<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trading Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Trading Management addon
    |
    */

    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | Data Provider Settings
    |--------------------------------------------------------------------------
    |
    | Settings for market data fetching
    |
    */
    'data_provider' => [
        'fetch_interval' => env('TM_FETCH_INTERVAL', 5), // minutes
        'retention_days' => env('TM_DATA_RETENTION_DAYS', 365), // 1 year default
        'cache_ttl' => env('TM_CACHE_TTL', 300), // 5 minutes cache
    ],

    /*
    |--------------------------------------------------------------------------
    | mtapi.io Settings
    |--------------------------------------------------------------------------
    */
    'mtapi' => [
        'api_key' => env('MTAPI_API_KEY'),
        'base_url' => env('MTAPI_BASE_URL', 'https://api.mtapi.io'),
        'timeout' => env('MTAPI_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | MetaApi.cloud Settings
    |--------------------------------------------------------------------------
    */
    'metaapi' => [
        'api_token' => env('METAAPI_TOKEN'),
        'base_url' => env('METAAPI_BASE_URL', 'https://mt-client-api-v1.london.agiliumtrade.ai'),
        'market_data_base_url' => env('METAAPI_MARKET_DATA_BASE_URL', 'https://mt-market-data-client-api-v1.london.agiliumtrade.ai'),
        'provisioning_base_url' => env('METAAPI_PROVISIONING_BASE_URL', 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai'),
        'billing_base_url' => env('METAAPI_BILLING_BASE_URL', 'https://billing-api-v1.agiliumtrade.agiliumtrade.ai'),
        'timeout' => env('METAAPI_TIMEOUT', 30), // seconds
        'streaming' => [
            'websocket_url' => env('METAAPI_WEBSOCKET_URL', 'https://mt-client-api-v1.london.agiliumtrade.ai'), // Socket.IO URL (HTTPS, not WSS)
            'redis_prefix' => env('METAAPI_REDIS_PREFIX', 'metaapi:stream'),
            'stream_ttl' => env('METAAPI_STREAM_TTL', 60), // seconds
            'reconnect_delay' => env('METAAPI_RECONNECT_DELAY', 5), // seconds
            'max_reconnect_attempts' => env('METAAPI_MAX_RECONNECT_ATTEMPTS', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Management Settings
    |--------------------------------------------------------------------------
    */
    'risk' => [
        'default_risk_percent' => env('TM_DEFAULT_RISK_PERCENT', 1.0), // 1%
        'max_risk_percent' => env('TM_MAX_RISK_PERCENT', 5.0), // 5%
        'min_lot_size' => env('TM_MIN_LOT_SIZE', 0.01),
        'max_lot_size' => env('TM_MAX_LOT_SIZE', 10.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Position Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'check_interval' => env('TM_MONITORING_INTERVAL', 60), // seconds
        'sl_buffer_pips' => env('TM_SL_BUFFER_PIPS', 2), // 2 pips buffer
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'update_frequency' => env('TM_ANALYTICS_FREQUENCY', 'daily'), // daily, hourly
        'metrics_retention_days' => env('TM_ANALYTICS_RETENTION', 90), // 90 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Backtesting Settings
    |--------------------------------------------------------------------------
    */
    'backtesting' => [
        'max_concurrent_backtests' => env('TM_MAX_BACKTESTS', 5),
        'default_backtest_period' => env('TM_BACKTEST_PERIOD', 365), // days
    ],
];

