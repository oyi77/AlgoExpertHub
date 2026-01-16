<?php

return [
    'base_url' => env('TWELVE_DATA_BASE_URL', 'https://api.twelvedata.com/v1'),
    'cache_ttl' => env('MARKET_DATA_CACHE_TTL', 900), // 15 minutes in seconds
];
