<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'platforms' => [
        'gmx' => [
            'api_url' => env('DEX_GMX_API_URL', 'https://api.gmx.io'),
            'subgraph_url' => env('DEX_GMX_SUBGRAPH_URL', 'https://subgraph.satsuma-prod.com/gmx'),
            'endpoints' => [
                'positions' => env('DEX_GMX_ENDPOINT_POSITIONS'),
                'position_history' => env('DEX_GMX_ENDPOINT_POSITION_HISTORY'),
                'funding_history' => env('DEX_GMX_ENDPOINT_FUNDING_HISTORY'),
                'liquidations' => env('DEX_GMX_ENDPOINT_LIQUIDATIONS'),
                'prices' => env('DEX_GMX_ENDPOINT_PRICES'),
            ],
            'rate_limit_per_minute' => env('DEX_GMX_RATE_LIMIT', 60),
            'timeout_seconds' => env('DEX_GMX_TIMEOUT', 15),
            'enabled' => env('DEX_GMX_ENABLED', true),
        ],
        'hyperliquid' => [
            'api_url' => env('DEX_HYPERLIQUID_API_URL', 'https://api.hyperliquid.xyz'),
            'endpoints' => [
                'positions' => env('DEX_HYPERLIQUID_ENDPOINT_POSITIONS'),
                'pnl' => env('DEX_HYPERLIQUID_ENDPOINT_PNL'),
                'funding' => env('DEX_HYPERLIQUID_ENDPOINT_FUNDING'),
                'liquidations' => env('DEX_HYPERLIQUID_ENDPOINT_LIQUIDATIONS'),
            ],
            'rate_limit_per_minute' => env('DEX_HYPERLIQUID_RATE_LIMIT', 60),
            'timeout_seconds' => env('DEX_HYPERLIQUID_TIMEOUT', 15),
            'enabled' => env('DEX_HYPERLIQUID_ENABLED', true),
        ],
        'aster' => [
            'api_url' => env('DEX_ASTER_API_URL', 'https://api.aster.com'),
            'endpoints' => [
                'positions' => env('DEX_ASTER_ENDPOINT_POSITIONS'),
                'pnl' => env('DEX_ASTER_ENDPOINT_PNL'),
                'funding' => env('DEX_ASTER_ENDPOINT_FUNDING'),
                'liquidations' => env('DEX_ASTER_ENDPOINT_LIQUIDATIONS'),
            ],
            'rate_limit_per_minute' => env('DEX_ASTER_RATE_LIMIT', 60),
            'timeout_seconds' => env('DEX_ASTER_TIMEOUT', 15),
            'api_key' => env('DEX_ASTER_API_KEY'),
            'enabled' => env('DEX_ASTER_ENABLED', true),
        ],
        'lighter' => [
            'api_url' => env('DEX_LIGHTER_API_URL', 'https://api.lighter.xyz'),
            'endpoints' => [
                'positions' => env('DEX_LIGHTER_ENDPOINT_POSITIONS'),
                'pnl' => env('DEX_LIGHTER_ENDPOINT_PNL'),
                'funding' => env('DEX_LIGHTER_ENDPOINT_FUNDING'),
                'liquidations' => env('DEX_LIGHTER_ENDPOINT_LIQUIDATIONS'),
            ],
            'rate_limit_per_minute' => env('DEX_LIGHTER_RATE_LIMIT', 60),
            'timeout_seconds' => env('DEX_LIGHTER_TIMEOUT', 15),
            'api_key' => env('DEX_LIGHTER_API_KEY'),
            'enabled' => env('DEX_LIGHTER_ENABLED', true),
        ],
        'dydx_v4' => [
            'api_url' => env('DEX_DYDX_V4_API_URL', 'https://indexer.dydx.trade'),
            'endpoints' => [
                'positions' => env('DEX_DYDX_V4_ENDPOINT_POSITIONS'),
                'fills' => env('DEX_DYDX_V4_ENDPOINT_FILLS'),
                'pnl' => env('DEX_DYDX_V4_ENDPOINT_PNL'),
                'funding' => env('DEX_DYDX_V4_ENDPOINT_FUNDING'),
                'markets' => env('DEX_DYDX_V4_ENDPOINT_MARKETS'),
            ],
            'rate_limit_per_minute' => env('DEX_DYDX_V4_RATE_LIMIT', 60),
            'timeout_seconds' => env('DEX_DYDX_V4_TIMEOUT', 15),
            'enabled' => env('DEX_DYDX_V4_ENABLED', true),
        ],
    ],

    'polling' => [
        'interval_seconds' => env('DEX_ANALYTICS_POLL_INTERVAL', 60),
        'refresh_interval_seconds' => env('DEX_ANALYTICS_REFRESH_INTERVAL', 300),
        'max_retries' => env('DEX_ANALYTICS_MAX_RETRIES', 3),
        'backoff_seconds' => env('DEX_ANALYTICS_BACKOFF_SECONDS', 10),
    ],

    'retention' => [
        'raw_days' => env('DEX_ANALYTICS_RAW_RETENTION_DAYS', 90),
        'aggregate_days' => env('DEX_ANALYTICS_AGG_RETENTION_DAYS', 0),
    ],

    'ai' => [
        'enabled' => env('DEX_ANALYTICS_AI_ENABLED', true),
        'default_connection_id' => env('DEX_ANALYTICS_AI_CONNECTION_ID'),
        'model' => env('DEX_ANALYTICS_AI_MODEL', 'gpt-4o-mini'),
    ],

    'leaderboards' => [
        'min_trades' => env('DEX_ANALYTICS_MIN_TRADES', 10),
        'min_volume' => env('DEX_ANALYTICS_MIN_VOLUME', 10000),
        'confidence_threshold' => env('DEX_ANALYTICS_CONFIDENCE_THRESHOLD', 70),
    ],
];
