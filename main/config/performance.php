<?php

return [
    'frontend' => [
        'enable' => true,
        'lazy_images' => true,
        'defer_scripts' => true,
        'async_scripts' => false,
        'preload' => [
            'fonts' => [],
            'styles' => [],
            'scripts' => [],
            'dns_prefetch' => [],
        ],
        'exclusions' => [
            'scripts' => [
                // e.g. '/js/bootstrap.js'
            ],
            'routes' => [
                'admin.*',
            ],
        ],
    ],
    'media' => [
        'enable' => true,
        'compress' => true,
        'convert_webp' => true,
        'max_width' => 1920,
        'max_height' => 1920,
    ],
    'http' => [
        'enable' => true,
        'cache_headers' => [
            'enabled' => true,
            'ttl' => 3600,
        ],
        'etag' => [
            'enabled' => true,
        ],
        'whitelist' => [
            'paths' => [
                '/',
            ],
        ],
        'blacklist' => [
            'paths' => [
                '/admin',
            ],
        ],
    ],
    'cache' => [
        'enable' => true,
        'ttl_map' => [
            'dashboard.user' => 300,
        ],
        'prewarm' => [
            'enable' => true,
            'routes' => [
                'home',
                'admin.home',
            ],
        ],
    ],
    'database' => [
        'enable' => true,
        'prune_days' => 14,
        'optimize_tables' => true,
    ],
];

